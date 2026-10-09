<?php

namespace App\Services;

use App\Events\ImportacionProcesada;
use App\Exceptions\ImportacionRequiereDecision;
use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\Importacion;
use App\Models\ImportacionCambio;
use App\Models\JuicioEvaluativo;
use App\Models\Programa;
use App\Models\Resultado;
use App\Support\ReporteSofiaPlus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Importa el "Reporte de Juicios de Evaluación" de Sofia Plus.
 *
 * Diseño:
 *  - La interpretación del archivo (columnas por encabezado, fechas, funcionario,
 *    etc.) está en App\Support\ReporteSofiaPlus; este servicio solo escribe.
 *  - Todo el archivo se importa en UNA transacción externa, y cada fila en un
 *    SAVEPOINT propio. Así un error de SQL en una fila revierte solo esa fila.
 *    (Sin savepoint, en PostgreSQL un error aborta la transacción completa: las
 *    filas siguientes fallaban y el COMMIT final descartaba TODO, aunque la
 *    pantalla informara que se habían procesado.)
 *  - Una importación sin ningún registro válido no escribe nada en la base.
 *  - Historial: se registra qué cambió frente a la carga anterior de la ficha
 *    (importacion_cambios) y una foto con los conteos (importaciones.resumen).
 *    La primera carga de una ficha es la «carga inicial»: no hay contra qué
 *    comparar, así que solo se guarda la foto.
 *  - Política: qué hacer cuando el reporte desharía aprobaciones (APROBADO →
 *    POR EVALUAR) o trae aprendices que hoy están en otra ficha. Con CONSULTAR
 *    la importación se revierte completa y se lanza ImportacionRequiereDecision
 *    para que el usuario elija (conservar aprobados, trasladar o sobrescribir).
 */
class ImportadorJuiciosService
{
    /** Revertir todo y pedir decisión si hay aprobaciones revertidas o aprendices de otra ficha. */
    public const CONSULTAR = 'consultar';
    /** No degradar juicios aprobados ni sacar aprendices de su ficha actual. */
    public const PRESERVAR_APROBADOS = 'preservar';
    /** No degradar juicios aprobados, pero trasladar a esta ficha a quien venga de otra. */
    public const PERMITIR_TRASLADO = 'trasladar';
    /** El reporte manda en todo (incluido degradar aprobados y trasladar). */
    public const FORZAR_SOBRESCRITURA = 'forzar';

    public const POLITICAS = [self::CONSULTAR, self::PRESERVAR_APROBADOS, self::PERMITIR_TRASLADO, self::FORZAR_SOBRESCRITURA];

    private string $politica = self::CONSULTAR;

    /** @var array<string,int> código => Id_Competencia */
    private array $cacheCompetencias = [];
    /** @var array<string,int> código => Id_Resultado */
    private array $cacheResultados = [];
    /** @var array<string,int|null> documento => Id_Funcionario */
    private array $cacheFuncionarios = [];
    /** @var array<string,Aprendiz> documento => modelo */
    private array $cacheAprendices = [];
    /** @var array<int,Collection> Id_Aprendiz => juicios existentes por Id_Resultado */
    private array $cacheJuicios = [];

    /** @var array<int,array{fila:int,dato:string,error:string}> */
    private array $errores = [];
    private int $juiciosProcesados = 0;

    /** @var array<int,array<string,mixed>> cambios confirmados frente a la carga anterior */
    private array $cambios = [];
    /** @var array<int,array<string,mixed>> cambios de la fila en curso: se confirman solo si la fila no falla */
    private array $cambiosFila = [];
    /** La ficha no tenía juicios antes de esta carga: no hay contra qué comparar. */
    private bool $cargaInicial = false;
    /** @var array<string,bool> documentos de aprendices creados en esta carga */
    private array $aprendicesNuevos = [];
    /** @var array<string,bool> tipo|aprendiz|resultado de los cambios confirmados (para no repetirlos) */
    private array $clavesAnotadas = [];

    /**
     * @param  array<int,array<int,mixed>>  $filas  Filas de la hoja (Excel::toArray)
     * @param  string|null  $fichaManual  Ficha elegida en el formulario (opcional)
     * @param  string  $politica  Una de self::POLITICAS
     * @return array{status:string,message:string,procesados:int,aprendices:int,errores:array,advertencias:array,detalles:array}
     *
     * @throws \RuntimeException si el archivo no es un reporte válido o la ficha no coincide
     * @throws ImportacionRequiereDecision con CONSULTAR, si el reporte desharía aprobaciones o
     *         trae aprendices de otra ficha (la base queda como estaba)
     */
    public function procesarArchivoExcel(
        array $filas,
        ?string $fichaManual = null,
        ?Importacion $importacion = null,
        string $politica = self::CONSULTAR,
    ): array {
        if (! in_array($politica, self::POLITICAS, true)) {
            throw new \InvalidArgumentException("Política de importación desconocida: {$politica}");
        }

        $inicio = microtime(true);
        $this->reiniciar();
        $this->politica = $politica;

        // ── 1. Interpretar el archivo (sin tocar la base de datos) ────────────
        $reporte = ReporteSofiaPlus::desdeFilas($filas);
        $this->errores = $reporte->errores;

        $fichaManual = $fichaManual !== null && trim($fichaManual) !== '' ? trim($fichaManual) : null;

        if ($fichaManual && $reporte->ficha && $fichaManual !== $reporte->ficha) {
            throw new \RuntimeException(
                "El archivo corresponde a la ficha {$reporte->ficha}, pero seleccionaste la ficha {$fichaManual}. " .
                'Sube el reporte de la ficha correcta o deja el selector en «Autodetectar».'
            );
        }

        $numeroFicha = $reporte->ficha ?? $fichaManual;
        if (! $numeroFicha) {
            throw new \RuntimeException(
                'No se detectó el número de Ficha en el archivo. Selecciona la ficha manualmente o revisa el formato del Excel.'
            );
        }

        Log::info('[Importador] Archivo interpretado.', [
            'ficha' => $numeroFicha, 'registros' => count($reporte->registros), 'filas_con_error' => count($reporte->errores),
        ]);

        // Sin registros válidos no se escribe nada (ni siquiera la ficha).
        if (empty($reporte->registros)) {
            $this->cerrarImportacion($importacion, $numeroFicha, 0, $inicio);

            return $this->resultado($numeroFicha, $reporte, 0);
        }

        // ── 2. Escribir: transacción externa + savepoint por fila ─────────────
        $aprendicesProcesados = DB::transaction(function () use ($reporte, $numeroFicha, $importacion) {
            $this->cargaInicial = ! JuicioEvaluativo::whereHas('aprendiz', fn ($q) => $q->where('Id_Ficha', $numeroFicha))->exists();

            $ficha = $this->asegurarFichaYPrograma($reporte, $numeroFicha);
            $aprendicesOk = [];

            foreach ($reporte->registros as $registro) {
                $this->cambiosFila = [];

                try {
                    // Transacción anidada => SAVEPOINT: si falla, solo se revierte esta fila.
                    DB::transaction(fn () => $this->procesarRegistro($registro, $ficha));

                    $this->juiciosProcesados++;
                    $aprendicesOk[$registro['documento']] = true;
                    foreach ($this->cambiosFila as $c) {
                        $this->cambios[] = $c;
                        $this->clavesAnotadas[self::claveCambio($c)] = true;
                    }
                } catch (\Throwable $e) {
                    // Lo que esta fila creó fue revertido: las cachés en memoria ya no
                    // son confiables (podrían apuntar a registros inexistentes), y sus
                    // cambios se descartan junto con ella.
                    $this->cacheAprendices = $this->cacheCompetencias = $this->cacheResultados = [];
                    $this->cacheFuncionarios = $this->cacheJuicios = [];

                    $this->errores[] = [
                        'fila'  => $registro['fila'],
                        'dato'  => $registro['documento'],
                        'error' => $e->getMessage(),
                    ];
                    Log::warning("[Importador] Fila {$registro['fila']} omitida: " . $e->getMessage());
                }
            }

            $this->registrarAusentes($ficha, $reporte);

            // Lanzar dentro de la transacción la revierte completa: nada queda escrito.
            if ($this->politica === self::CONSULTAR && $this->requiereDecision()) {
                throw new ImportacionRequiereDecision($this->analisisParaDecidir($numeroFicha, $reporte));
            }

            $this->guardarCambios($importacion);

            return count($aprendicesOk);
        });

        // ── 3. Registro de la importación y evento (ya con todo confirmado) ───
        $this->cerrarImportacion($importacion, $numeroFicha, $aprendicesProcesados, $inicio, $reporte);

        if ($importacion) {
            ImportacionProcesada::dispatch($importacion, $aprendicesProcesados, (string) $numeroFicha, $this->errores);
        }

        Log::info("[Importador] Finalizado. Juicios: {$this->juiciosProcesados}, aprendices: {$aprendicesProcesados}, errores: " . count($this->errores));

        return $this->resultado($numeroFicha, $reporte, $aprendicesProcesados);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Escritura
    // ══════════════════════════════════════════════════════════════════════

    private function asegurarFichaYPrograma(ReporteSofiaPlus $reporte, string $numeroFicha): Ficha
    {
        $programa = Programa::firstOrCreate(
            ['Nombre' => $reporte->denominacion ?: 'PROGRAMA SOFIA PLUS'],
            [
                'Codigo'    => $reporte->codigoPrograma ?: 'S-PLUS',
                'Modalidad' => mb_strtoupper($reporte->modalidad ?: 'PRESENCIAL'),
                'Version'   => $reporte->versionPrograma ?: '1',
            ]
        );

        // Programas creados antes con datos de relleno ("S-PLUS"): completarlos.
        if ($programa->Codigo === 'S-PLUS' && $reporte->codigoPrograma) {
            $programa->update([
                'Codigo'    => $reporte->codigoPrograma,
                'Version'   => $reporte->versionPrograma ?: $programa->Version,
                'Modalidad' => $reporte->modalidad ? mb_strtoupper($reporte->modalidad) : $programa->Modalidad,
            ]);
        }

        $ficha = Ficha::find($numeroFicha);
        if (! $ficha) {
            // La jornada no viene en el reporte: se crea con el valor por defecto
            // y NO se vuelve a pisar en importaciones posteriores.
            return Ficha::create([
                'Id_Ficha'    => $numeroFicha,
                'Id_Programa' => $programa->Id_Programa,
                'Jornada'     => 'DIURNA',
            ]);
        }

        if ((int) $ficha->Id_Programa !== (int) $programa->Id_Programa) {
            $ficha->update(['Id_Programa' => $programa->Id_Programa]);
        }

        return $ficha;
    }

    /** @param  array<string,mixed>  $r  Registro normalizado de ReporteSofiaPlus */
    private function procesarRegistro(array $r, Ficha $ficha): void
    {
        $aprendiz = $this->resolverAprendiz($r, $ficha);
        $resultadoId = $this->resolverResultado($r);
        $funcionarioId = $this->resolverFuncionario($r['funcionario']);

        $estado = $r['aprobado'] ? 1 : 0;
        $juicio = $this->juiciosDe($aprendiz)->get($resultadoId);

        if ($juicio) {
            $estadoAnterior = (int) $juicio->Estado;

            if ($estadoAnterior === 1 && $estado === 0 && $this->protegeAprobados()) {
                // El usuario decidió conservar lo aprobado: el juicio queda intacto.
                $this->anotarCambio(ImportacionCambio::JUICIO_PROTEGIDO, $aprendiz, $resultadoId,
                    self::etiquetaJuicio(1), self::etiquetaJuicio(0), siempre: true);

                return;
            }

            // Sofia Plus es la única fuente de los juicios: el reporte manda.
            // (registrado_por => null limpia marcas de la antigua calificación
            // manual, que ya no existe en el sistema.)
            $juicio->fill([
                'Estado'         => $estado,
                'Id_Funcionario' => $funcionarioId,
                'registrado_por' => null,
                'Fecha'          => $r['fecha']?->toDateString(),
                'Hora'           => $r['fecha'],
            ])->save();

            if ($estadoAnterior !== $estado) {
                $revertido = $estado === 0;
                // Una aprobación revertida se anota siempre (también en la carga inicial,
                // p. ej. de un aprendiz que llega de otra ficha): es lo que pide decisión.
                $this->anotarCambio(
                    $revertido ? ImportacionCambio::JUICIO_REVERTIDO : ImportacionCambio::JUICIO_APROBADO,
                    $aprendiz, $resultadoId, self::etiquetaJuicio($estadoAnterior), self::etiquetaJuicio($estado),
                    siempre: $revertido
                );
            }

            return;
        }

        $nuevo = JuicioEvaluativo::create([
            'Id_Resultado'   => $resultadoId,
            'Id_Aprendiz'    => $aprendiz->Id_Aprendiz,
            'Estado'         => $estado,
            'Id_Funcionario' => $funcionarioId,
            'Fecha'          => $r['fecha']?->toDateString(),
            'Hora'           => $r['fecha'],
        ]);
        $this->juiciosDe($aprendiz)->put($resultadoId, $nuevo);

        // Un aprendiz nuevo ya queda registrado como tal: no se anota cada uno de sus RAP.
        if (! isset($this->aprendicesNuevos[$aprendiz->Documento])) {
            $this->anotarCambio(ImportacionCambio::JUICIO_NUEVO, $aprendiz, $resultadoId, null, self::etiquetaJuicio($estado));
        }
    }

    private function resolverAprendiz(array $r, Ficha $ficha): Aprendiz
    {
        $doc = $r['documento'];
        if (isset($this->cacheAprendices[$doc])) {
            return $this->cacheAprendices[$doc];
        }

        $datos = [
            'Tipo_Documento' => $r['tipo_documento'],
            'Nombre'         => $r['nombre'],
            'Apellido'       => $r['apellidos'],
            'Estado'         => $r['estado'],
            'Id_Ficha'       => $ficha->Id_Ficha,
        ];

        $aprendiz = Aprendiz::where('Documento', $doc)->first();

        if ($aprendiz) {
            $estadoAnterior = $aprendiz->Estado;
            $deOtraFicha = (int) $aprendiz->Id_Ficha !== (int) $ficha->Id_Ficha;

            if ($deOtraFicha && ! $this->permiteTraslado()) {
                // Se queda en su ficha actual. El estado (EN FORMACION, TRASLADADO…) es
                // el de esa ficha, así que tampoco se toma de este reporte; sus juicios sí.
                $aprendiz->fill([
                    'Tipo_Documento' => $r['tipo_documento'],
                    'Nombre'         => $r['nombre'],
                    'Apellido'       => $r['apellidos'],
                ])->save();
                $this->anotarCambio(ImportacionCambio::APRENDIZ_NO_TRASLADADO, $aprendiz, null,
                    (string) $aprendiz->Id_Ficha, (string) $ficha->Id_Ficha, siempre: true);

                return $this->cacheAprendices[$doc] = $aprendiz;
            }

            if ($deOtraFicha) {
                // El documento es único en todo el sistema: el aprendiz "se mueve".
                // Sus juicios anteriores se conservan; se avisa al usuario (también en
                // la carga inicial de la ficha).
                $this->anotarCambio(ImportacionCambio::APRENDIZ_MOVIDO, $aprendiz, null,
                    (string) $aprendiz->Id_Ficha, (string) $ficha->Id_Ficha, siempre: true);
            }
            $aprendiz->fill($datos)->save();

            if ($estadoAnterior !== $r['estado']) {
                $this->anotarCambio(ImportacionCambio::APRENDIZ_ESTADO, $aprendiz, null, $estadoAnterior, $r['estado']);
            }
        } else {
            $aprendiz = Aprendiz::create(['Documento' => $doc] + $datos);
            $this->aprendicesNuevos[$doc] = true;
            $this->anotarCambio(ImportacionCambio::APRENDIZ_NUEVO, $aprendiz, null, null, $r['estado']);
        }

        return $this->cacheAprendices[$doc] = $aprendiz;
    }

    private function resolverResultado(array $r): int
    {
        $codComp = $r['competencia_cod'];
        if (! isset($this->cacheCompetencias[$codComp])) {
            $comp = Competencia::updateOrCreate(['Codigo' => $codComp], ['Nombre' => $r['competencia_nom']]);
            $this->cacheCompetencias[$codComp] = $comp->Id_Competencia;
        }

        $codRes = $r['resultado_cod'];
        if (! isset($this->cacheResultados[$codRes])) {
            $res = Resultado::updateOrCreate(
                ['Codigo' => $codRes],
                ['Nombre' => $r['resultado_nom'], 'Id_Competencia' => $this->cacheCompetencias[$codComp]]
            );
            $this->cacheResultados[$codRes] = $res->Id_Resultado;
        }

        return $this->cacheResultados[$codRes];
    }

    /** @param  array{tipo:string,documento:string,nombre:string}|null  $f */
    private function resolverFuncionario(?array $f): ?int
    {
        if ($f === null) {
            return null; // juicio todavía sin registrar en Sofia Plus
        }

        if (! array_key_exists($f['documento'], $this->cacheFuncionarios)) {
            $func = Funcionario::firstOrCreate(
                ['Documento' => $f['documento']],
                ['Tipo_Documento' => $f['tipo'], 'Nombre' => $f['nombre'], 'Apellido' => '']
            );
            $this->cacheFuncionarios[$f['documento']] = $func->Id_Funcionario;
        }

        return $this->cacheFuncionarios[$f['documento']];
    }

    /** Juicios existentes del aprendiz (1 consulta por aprendiz, no por fila). */
    private function juiciosDe(Aprendiz $aprendiz): Collection
    {
        return $this->cacheJuicios[$aprendiz->Id_Aprendiz]
            ??= JuicioEvaluativo::where('Id_Aprendiz', $aprendiz->Id_Aprendiz)->get()->keyBy('Id_Resultado');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Resultado y bitácora
    // ══════════════════════════════════════════════════════════════════════

    /** @return array<int,string> */
    private function advertencias(ReporteSofiaPlus $reporte): array
    {
        // Aprobaciones revertidas y traslados de ficha no son advertencias: solo
        // ocurren cuando el usuario lo decidió en la pantalla de decisión.
        $a = [];

        if ($reporte->juiciosDesconocidos) {
            $a[] = 'Valores de juicio no reconocidos (se tomaron como pendientes): '
                . collect($reporte->juiciosDesconocidos)->map(fn ($n, $v) => "{$v} ({$n})")->implode(', ') . '.';
        }
        if ($reporte->funcionariosIlegibles) {
            $a[] = "{$reporte->funcionariosIlegibles} registro(s) con un funcionario ilegible quedaron sin funcionario asignado.";
        }

        return $a;
    }

    private function resultado(string $ficha, ReporteSofiaPlus $reporte, int $aprendices): array
    {
        $advertencias = $this->advertencias($reporte);

        $mensaje = $this->juiciosProcesados > 0
            ? "Ficha {$ficha}: se importaron {$this->juiciosProcesados} juicios de {$aprendices} aprendices."
            : 'No se importó ningún juicio.';
        if ($this->juiciosProcesados > 0) {
            $mensaje .= ' ' . $this->textoCambios();
        }
        if ($this->errores) {
            $mensaje .= ' ' . count($this->errores) . ' fila(s) omitidas con error.';
        }
        if ($advertencias) {
            $mensaje .= ' Atención: ' . implode(' ', $advertencias);
        }

        return [
            'status'        => 'success',
            'message'       => $mensaje,
            'procesados'    => $this->juiciosProcesados,
            'aprendices'    => $aprendices,
            'errores'       => $this->errores,
            'advertencias'  => $advertencias,
            'carga_inicial' => $this->cargaInicial,
            'cambios'       => $this->conteoCambios(),
            'politica'      => $this->politica,
            'detalles'      => ['ficha' => $ficha],
        ];
    }

    private function cerrarImportacion(?Importacion $importacion, string $ficha, int $aprendices, float $inicio, ?ReporteSofiaPlus $reporte = null): void
    {
        if (! $importacion) {
            return;
        }

        $detalle = ["Juicios importados: {$this->juiciosProcesados}. Aprendices: {$aprendices}."];
        if ($decision = $this->textoDecision()) {
            $detalle[] = $decision;
        }
        if ($reporte) {
            $detalle = array_merge($detalle, $this->advertencias($reporte));
        }
        if ($this->errores) {
            $detalle[] = count($this->errores) . ' fila(s) con error: ' . collect($this->errores)
                ->take(20)->map(fn ($e) => "fila {$e['fila']} ({$e['dato']})")->implode(', ')
                . (count($this->errores) > 20 ? ', …' : '');
        }

        $importacion->update([
            'id_ficha'              => $ficha,
            'aprendices_procesados' => $aprendices,
            'duracion_segundos'     => (int) round(microtime(true) - $inicio),
            'estado'                => $this->errores ? 'con_advertencias' : 'exitoso',
            'detalle'               => implode("\n", $detalle),
            // Sin registros escritos no hay foto: la carga no entra en la línea de tiempo.
            'resumen'               => $reporte && $this->juiciosProcesados > 0 ? $this->calcularResumen($ficha) : null,
        ]);
    }

    private function reiniciar(): void
    {
        $this->cacheCompetencias = $this->cacheResultados = $this->cacheFuncionarios = [];
        $this->cacheAprendices = $this->cacheJuicios = [];
        $this->errores = $this->cambios = $this->cambiosFila = $this->aprendicesNuevos = $this->clavesAnotadas = [];
        $this->juiciosProcesados = 0;
        $this->cargaInicial = false;
        $this->politica = self::CONSULTAR;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Historial entre reportes
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Anota un cambio de la fila en curso. En la carga inicial no se anota nada
     * (no hay carga anterior con qué comparar), salvo que sea un aviso que
     * siempre interesa ($siempre), como un aprendiz que llega de otra ficha.
     */
    private function anotarCambio(string $tipo, Aprendiz $aprendiz, ?int $resultadoId, ?string $anterior, ?string $nuevo, bool $siempre = false): void
    {
        if ($this->cargaInicial && ! $siempre) {
            return;
        }

        $cambio = [
            'tipo'           => $tipo,
            'Id_Aprendiz'    => $aprendiz->Id_Aprendiz,
            'Id_Resultado'   => $resultadoId,
            'valor_anterior' => $anterior,
            'valor_nuevo'    => $nuevo,
            'documento'      => $aprendiz->Documento,   // solo para los mensajes; no se guarda
        ];

        // Tras una fila fallida se vacían las cachés y el aprendiz se vuelve a resolver:
        // un cambio ya confirmado (p. ej. «no trasladado») no debe anotarse dos veces.
        $clave = self::claveCambio($cambio);
        if (isset($this->clavesAnotadas[$clave])) {
            return;
        }
        foreach ($this->cambiosFila as $c) {
            if (self::claveCambio($c) === $clave) {
                return;
            }
        }

        $this->cambiosFila[] = $cambio;
    }

    /** @param  array<string,mixed>  $c */
    private static function claveCambio(array $c): string
    {
        return $c['tipo'] . '|' . $c['Id_Aprendiz'] . '|' . ($c['Id_Resultado'] ?? '');
    }

    /** Aprendices de la ficha que no vienen en este reporte (no se borran; solo se anotan). */
    private function registrarAusentes(Ficha $ficha, ReporteSofiaPlus $reporte): void
    {
        if ($this->cargaInicial) {
            return;
        }

        // Todos los documentos del archivo, incluidos los de filas con error, para
        // no marcar como ausente a alguien que sí vino.
        $enArchivo = array_values(array_unique(array_merge(
            array_column($reporte->registros, 'documento'),
            array_column($reporte->errores, 'dato')
        )));

        Aprendiz::where('Id_Ficha', $ficha->Id_Ficha)
            ->whereNotIn('Documento', $enArchivo)
            ->get()
            ->each(function (Aprendiz $ausente) {
                $this->cambios[] = [
                    'tipo'           => ImportacionCambio::APRENDIZ_AUSENTE,
                    'Id_Aprendiz'    => $ausente->Id_Aprendiz,
                    'Id_Resultado'   => null,
                    'valor_anterior' => $ausente->Estado,
                    'valor_nuevo'    => null,
                    'documento'      => $ausente->Documento,
                ];
            });
    }

    private function guardarCambios(?Importacion $importacion): void
    {
        if (! $importacion || ! $this->cambios) {
            return;
        }

        $filas = array_map(fn (array $c) => [
            'importacion_id' => $importacion->id,
            'Id_Aprendiz'    => $c['Id_Aprendiz'],
            'Id_Resultado'   => $c['Id_Resultado'],
            'tipo'           => $c['tipo'],
            'valor_anterior' => $c['valor_anterior'] !== null ? mb_substr($c['valor_anterior'], 0, 100) : null,
            'valor_nuevo'    => $c['valor_nuevo'] !== null ? mb_substr($c['valor_nuevo'], 0, 100) : null,
        ], $this->cambios);

        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('importacion_cambios')->insert($lote);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function cambiosDeTipo(string $tipo): array
    {
        return array_values(array_filter($this->cambios, fn ($c) => $c['tipo'] === $tipo));
    }

    /** @return array<string,int> tipo => cantidad */
    private function conteoCambios(): array
    {
        return array_count_values(array_column($this->cambios, 'tipo'));
    }

    /** «Frente a la carga anterior: +312 aprobados, 1 cambio de estado.» */
    private function textoCambios(): string
    {
        $decision = $this->textoDecision();

        if ($this->cargaInicial) {
            return trim('Es la carga inicial de la ficha: desde la próxima importación verás qué cambió. ' . $decision);
        }

        $c = $this->conteoCambios();
        $partes = array_filter([
            isset($c[ImportacionCambio::JUICIO_APROBADO]) ? "+{$c[ImportacionCambio::JUICIO_APROBADO]} aprobados" : null,
            isset($c[ImportacionCambio::JUICIO_REVERTIDO]) ? "{$c[ImportacionCambio::JUICIO_REVERTIDO]} revertidos" : null,
            isset($c[ImportacionCambio::APRENDIZ_ESTADO]) ? "{$c[ImportacionCambio::APRENDIZ_ESTADO]} cambio(s) de estado" : null,
            isset($c[ImportacionCambio::APRENDIZ_NUEVO]) ? "{$c[ImportacionCambio::APRENDIZ_NUEVO]} aprendiz(es) nuevo(s)" : null,
            isset($c[ImportacionCambio::APRENDIZ_AUSENTE]) ? "{$c[ImportacionCambio::APRENDIZ_AUSENTE]} ausente(s)" : null,
            isset($c[ImportacionCambio::JUICIO_NUEVO]) ? "{$c[ImportacionCambio::JUICIO_NUEVO]} RAP nuevo(s)" : null,
        ]);

        $texto = $partes
            ? 'Frente a la carga anterior: ' . implode(', ', $partes) . '.'
            : 'Sin cambios frente a la carga anterior.';

        return trim($texto . ' ' . $decision);
    }

    /** Lo que resultó de la decisión del usuario: «Se conservaron 12 juicio(s) aprobados…». */
    private function textoDecision(): string
    {
        $c = $this->conteoCambios();
        $partes = array_filter([
            isset($c[ImportacionCambio::JUICIO_PROTEGIDO])
                ? "Se conservaron {$c[ImportacionCambio::JUICIO_PROTEGIDO]} juicio(s) aprobados que el reporte traía por evaluar." : null,
            isset($c[ImportacionCambio::APRENDIZ_NO_TRASLADADO])
                ? "{$c[ImportacionCambio::APRENDIZ_NO_TRASLADADO]} aprendiz(es) de otra ficha se dejaron en su ficha actual." : null,
            isset($c[ImportacionCambio::APRENDIZ_MOVIDO])
                ? "{$c[ImportacionCambio::APRENDIZ_MOVIDO]} aprendiz(es) se trasladaron a esta ficha desde otra." : null,
            // Fuera de la carga inicial los revertidos ya figuran en el resumen de cambios.
            $this->cargaInicial && isset($c[ImportacionCambio::JUICIO_REVERTIDO])
                ? "{$c[ImportacionCambio::JUICIO_REVERTIDO]} aprobación(es) se revirtieron a por evaluar." : null,
        ]);

        return implode(' ', $partes);
    }

    /** Foto de la ficha tras la carga (para la línea de tiempo). */
    private function calcularResumen(string $ficha): array
    {
        $porEstado = Aprendiz::where('Id_Ficha', $ficha)
            ->select('Estado', DB::raw('COUNT(*) as n'))
            ->groupBy('Estado')
            ->pluck('n', 'Estado')
            ->map(fn ($n) => (int) $n)
            ->all();

        $j = DB::table('juicios_evaluativos as j')
            ->join('aprendiz as a', 'a.Id_Aprendiz', '=', 'j.Id_Aprendiz')
            ->where('a.Id_Ficha', $ficha)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN j."Estado" = 1 THEN 1 ELSE 0 END) as aprobados')
            ->selectRaw('SUM(CASE WHEN a."Estado" = ? THEN 1 ELSE 0 END) as total_ef', ['EN FORMACION'])
            ->selectRaw('SUM(CASE WHEN a."Estado" = ? AND j."Estado" = 1 THEN 1 ELSE 0 END) as aprobados_ef', ['EN FORMACION'])
            ->first();

        $total = (int) $j->total;
        $aprobados = (int) $j->aprobados;
        $totalEf = (int) $j->total_ef;
        $aprobadosEf = (int) $j->aprobados_ef;

        return [
            'carga_inicial'           => $this->cargaInicial,
            'aprendices'              => array_sum($porEstado),
            'por_estado'              => $porEstado,
            'juicios'                 => $total,
            'aprobados'               => $aprobados,
            'pendientes'              => $total - $aprobados,
            'aprobados_en_formacion'  => $aprobadosEf,
            'pendientes_en_formacion' => $totalEf - $aprobadosEf,
            'cambios'                 => $this->conteoCambios(),
            'politica'                => $this->politica,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Política y decisión del usuario
    // ══════════════════════════════════════════════════════════════════════

    private function protegeAprobados(): bool
    {
        return in_array($this->politica, [self::PRESERVAR_APROBADOS, self::PERMITIR_TRASLADO], true);
    }

    private function permiteTraslado(): bool
    {
        // CONSULTAR procesa como si el reporte mandara para descubrir qué cambiaría.
        return $this->politica !== self::PRESERVAR_APROBADOS;
    }

    /** El reporte desharía aprobaciones o sacaría aprendices de otra ficha. */
    private function requiereDecision(): bool
    {
        return $this->cambiosDeTipo(ImportacionCambio::JUICIO_REVERTIDO) !== []
            || $this->cambiosDeTipo(ImportacionCambio::APRENDIZ_MOVIDO) !== [];
    }

    /**
     * Lo que el usuario necesita ver para decidir. Se arma dentro de la transacción
     * (antes de revertirla) y es compacto porque viaja en la sesión.
     *
     * @return array<string,mixed>
     */
    private function analisisParaDecidir(string $numeroFicha, ReporteSofiaPlus $reporte): array
    {
        $revertidos = collect($this->cambiosDeTipo(ImportacionCambio::JUICIO_REVERTIDO));
        $movidos    = collect($this->cambiosDeTipo(ImportacionCambio::APRENDIZ_MOVIDO));

        $nombres = Aprendiz::whereIn('Id_Aprendiz', $revertidos->pluck('Id_Aprendiz')->merge($movidos->pluck('Id_Aprendiz'))->unique())
            ->get()
            ->mapWithKeys(fn (Aprendiz $a) => [$a->Id_Aprendiz => $a->nombre_completo]);
        $raps = Resultado::whereIn('Id_Resultado', $revertidos->pluck('Id_Resultado')->unique())
            ->get()
            ->keyBy('Id_Resultado');

        return [
            'ficha'              => $numeroFicha,
            'programa'           => $reporte->denominacion,
            'registros'          => count($reporte->registros),
            'aprendices_archivo' => count(array_unique(array_column($reporte->registros, 'documento'))),
            'carga_inicial'      => $this->cargaInicial,
            'conteo'             => $this->conteoCambios(),
            // Un aprendiz por fila con los códigos de RAP que perdería.
            'revertidos' => $revertidos->groupBy('Id_Aprendiz')->map(fn ($g, $id) => [
                'documento' => $g->first()['documento'],
                'nombre'    => $nombres[$id] ?? $g->first()['documento'],
                'raps'      => $g->map(fn ($c) => $raps[$c['Id_Resultado']]->Codigo ?? '?')->values()->all(),
            ])->sortByDesc(fn ($f) => count($f['raps']))->values()->all(),
            'raps' => $raps->mapWithKeys(fn (Resultado $r) => [$r->Codigo => $r->Nombre])->all(),
            'movidos' => $movidos->map(fn ($c) => [
                'documento'    => $c['documento'],
                'nombre'       => $nombres[$c['Id_Aprendiz']] ?? $c['documento'],
                'ficha_actual' => $c['valor_anterior'],
            ])->sortBy('nombre')->values()->all(),
        ];
    }

    private static function etiquetaJuicio(int $estado): string
    {
        return $estado === 1 ? 'APROBADO' : 'POR EVALUAR';
    }
}
