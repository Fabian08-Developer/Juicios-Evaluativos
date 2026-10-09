<?php

namespace App\Services;

use App\Events\ImportacionProcesada;
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
 */
class ImportadorJuiciosService
{
<<<<<<< Updated upstream
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

    /**
     * @param  array<int,array<int,mixed>>  $filas  Filas de la hoja (Excel::toArray)
     * @param  string|null  $fichaManual  Ficha elegida en el formulario (opcional)
     * @return array{status:string,message:string,procesados:int,aprendices:int,errores:array,advertencias:array,detalles:array}
     *
     * @throws \RuntimeException si el archivo no es un reporte válido o la ficha no coincide
     */
    public function procesarArchivoExcel(array $filas, ?string $fichaManual = null, ?Importacion $importacion = null): array
    {
=======
    /** Resultado de la última ejecución para acceso externo */
    public array $erroresPorFila       = [];
    public int   $procesados           = 0;
    public int   $nuevosAprobados      = 0;
    public int   $regresionesProtegidas = 0;

    public function procesarArchivoExcel(
        array $filas,
        ?string $fichaManual = null,
        ?Importacion $importacion = null,
        string $politica = 'PRESERVAR_APROBADOS'
    ): array {
>>>>>>> Stashed changes
        $inicio = microtime(true);
        $this->reiniciar();

<<<<<<< Updated upstream
        // ── 1. Interpretar el archivo (sin tocar la base de datos) ────────────
        $reporte = ReporteSofiaPlus::desdeFilas($filas);
        $this->errores = $reporte->errores;
=======
        try {
            Log::info("[Importador] Iniciando con " . count($filas) . " filas. Política: {$politica}");
>>>>>>> Stashed changes

        $fichaManual = $fichaManual !== null && trim($fichaManual) !== '' ? trim($fichaManual) : null;

<<<<<<< Updated upstream
        if ($fichaManual && $reporte->ficha && $fichaManual !== $reporte->ficha) {
            throw new \RuntimeException(
                "El archivo corresponde a la ficha {$reporte->ficha}, pero seleccionaste la ficha {$fichaManual}. " .
                'Sube el reporte de la ficha correcta o deja el selector en «Autodetectar».'
            );
        }
=======
            $denominacion = $denominacion ?: 'PROGRAMA SOFIA PLUS';
            $numeroFicha  = $fichaManual ?: $numeroFicha;
>>>>>>> Stashed changes

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
                    array_push($this->cambios, ...$this->cambiosFila);
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
                $this->anotarCambio(
                    $estado === 1 ? ImportacionCambio::JUICIO_APROBADO : ImportacionCambio::JUICIO_REVERTIDO,
                    $aprendiz, $resultadoId, self::etiquetaJuicio($estadoAnterior), self::etiquetaJuicio($estado)
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

<<<<<<< Updated upstream
        // Un aprendiz nuevo ya queda registrado como tal: no se anota cada uno de sus RAP.
        if (! isset($this->aprendicesNuevos[$aprendiz->Documento])) {
            $this->anotarCambio(ImportacionCambio::JUICIO_NUEVO, $aprendiz, $resultadoId, null, self::etiquetaJuicio($estado));
=======
            // ── FASE 3: Localizar fila de inicio de datos ─────────────────────
            $inicioDatos = $this->encontrarInicioDatos($filas);

            // ── FASE 4: CACHÉS EN MEMORIA (evita N+1 en competencias/instructores)
            $cacheCompetencias = [];
            $cacheResultados   = [];
            $cacheFuncionarios = [];

            // ── FASE 5: PROCESAMIENTO FILA A FILA CON TOLERANCIA A FALLOS ────
            $this->procesados            = 0;
            $this->nuevosAprobados       = 0;
            $this->regresionesProtegidas = 0;
            $this->erroresPorFila        = [];

            for ($i = $inicioDatos; $i < count($filas); $i++) {
                $fila = $filas[$i];

                try {
                    $resultado = $this->procesarFila(
                        $fila, $i + 1, $ficha,
                        $cacheCompetencias, $cacheResultados, $cacheFuncionarios,
                        $politica
                    );

                    if ($resultado) {
                        $this->procesados++;
                    }

                } catch (\Exception $e) {
                    // ✅ Error tolerado: registrar y continuar con la siguiente fila
                    $this->erroresPorFila[] = [
                        'fila'  => $i + 1,
                        'dato'  => trim((string) ($fila[1] ?? $fila[0] ?? 'N/A')),
                        'error' => $e->getMessage(),
                    ];
                    Log::warning("[Importador] Fila " . ($i + 1) . " omitida: " . $e->getMessage());
                }
            }

            DB::commit();

            $duracion = round(microtime(true) - $inicio, 2);
            Log::info("[Importador] Finalizado. Procesados: {$this->procesados}, Nuevos Aprobados: {$this->nuevosAprobados}, Regresiones Protegidas: {$this->regresionesProtegidas}");

            // ── FASE 6: Actualizar registro de importación ────────────────────
            $detalle = "Procesados: {$this->procesados}. ";
            if ($this->nuevosAprobados > 0) {
                $detalle .= "+{$this->nuevosAprobados} nuevos aprobados. ";
            }
            if ($this->regresionesProtegidas > 0) {
                $detalle .= "🛡️ {$this->regresionesProtegidas} juicios protegidos contra regresión. ";
            }
            if (count($this->erroresPorFila) > 0) {
                $detalle .= count($this->erroresPorFila) . " fila(s) con error.";
            }

            if ($importacion) {
                $importacion->update([
                    'aprendices_procesados' => $this->procesados,
                    'duracion_segundos'     => (int) $duracion,
                    'estado'                => count($this->erroresPorFila) === 0 ? 'exitoso' : 'con_advertencias',
                    'detalle'               => trim($detalle),
                ]);
            }

            // ── FASE 7: Disparar Evento (OCP — los Listeners hacen el resto) ──
            if ($importacion) {
                ImportacionProcesada::dispatch($importacion, $this->procesados, (string) $ficha->Id_Ficha, $this->erroresPorFila);
            }

            $mensaje = "Se procesaron {$this->procesados} registros correctamente.";
            if ($this->nuevosAprobados > 0) {
                $mensaje .= " (+{$this->nuevosAprobados} nuevos juicios aprobados)";
            }
            if ($this->regresionesProtegidas > 0) {
                $mensaje .= " (🛡️ {$this->regresionesProtegidas} juicios protegidos contra regresión)";
            }

            return [
                'status'                => 'success',
                'message'               => $mensaje,
                'procesados'            => $this->procesados,
                'nuevos_aprobados'      => $this->nuevosAprobados,
                'regresiones_protegidas'=> $this->regresionesProtegidas,
                'errores'               => $this->erroresPorFila,
                'detalles'              => ['ficha' => $ficha->Id_Ficha],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Importador] Error fatal: " . $e->getMessage());
            throw $e;
>>>>>>> Stashed changes
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

            if ((int) $aprendiz->Id_Ficha !== (int) $ficha->Id_Ficha) {
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
        $a = [];

        $movidos = array_column($this->cambiosDeTipo(ImportacionCambio::APRENDIZ_MOVIDO), 'documento');
        if ($movidos) {
            $a[] = count($movidos) . ' aprendiz(es) ya estaban registrados en otra ficha y fueron movidos a esta '
                . '(sus juicios anteriores se conservan): documentos ' . implode(', ', array_slice($movidos, 0, 10))
                . (count($movidos) > 10 ? ', …' : '') . '.';
        }
        $revertidos = count($this->cambiosDeTipo(ImportacionCambio::JUICIO_REVERTIDO));
        if ($revertidos) {
            $a[] = "{$revertidos} juicio(s) pasaron de APROBADO a POR EVALUAR frente a la carga anterior. "
                . '¿Subiste un reporte más antiguo? Revisa el detalle de esta importación.';
        }
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
            'detalles'      => ['ficha' => $ficha],
        ];
    }

    private function cerrarImportacion(?Importacion $importacion, string $ficha, int $aprendices, float $inicio, ?ReporteSofiaPlus $reporte = null): void
    {
        if (! $importacion) {
            return;
        }

        $detalle = ["Juicios importados: {$this->juiciosProcesados}. Aprendices: {$aprendices}."];
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
        $this->errores = $this->cambios = $this->cambiosFila = $this->aprendicesNuevos = [];
        $this->juiciosProcesados = 0;
        $this->cargaInicial = false;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Historial entre reportes
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Anota un cambio de la fila en curso. En la carga inicial no se anota nada
     * (no hay carga anterior con qué comparar), salvo que sea un aviso que
     * siempre interesa ($siempre), como un aprendiz que llega de otra ficha.
     */
<<<<<<< Updated upstream
    private function anotarCambio(string $tipo, Aprendiz $aprendiz, ?int $resultadoId, ?string $anterior, ?string $nuevo, bool $siempre = false): void
    {
        if ($this->cargaInicial && ! $siempre) {
            return;
=======
    public function procesarFila(
        array  $fila,
        int    $numFila,
        Ficha  $ficha,
        array  &$cacheCompetencias,
        array  &$cacheResultados,
        array  &$cacheFuncionarios,
        string $politica = 'PRESERVAR_APROBADOS'
    ): bool {
        // Detectar documento del aprendiz (columnas 0, 1 o 2)
        $docAprendiz = null;
        foreach ([0, 1, 2] as $colIdx) {
            $val = trim((string) ($fila[$colIdx] ?? ''));
            if (is_numeric($val) && strlen($val) >= 7) {
                $docAprendiz = $val;
                break;
            }
>>>>>>> Stashed changes
        }

        $this->cambiosFila[] = [
            'tipo'           => $tipo,
            'Id_Aprendiz'    => $aprendiz->Id_Aprendiz,
            'Id_Resultado'   => $resultadoId,
            'valor_anterior' => $anterior,
            'valor_nuevo'    => $nuevo,
            'documento'      => $aprendiz->Documento,   // solo para los mensajes; no se guarda
        ];
    }

    /** Aprendices de la ficha que no vienen en este reporte (no se borran; solo se anotan). */
    private function registrarAusentes(Ficha $ficha, ReporteSofiaPlus $reporte): void
    {
        if ($this->cargaInicial) {
            return;
        }

<<<<<<< Updated upstream
        // Todos los documentos del archivo, incluidos los de filas con error, para
        // no marcar como ausente a alguien que sí vino.
        $enArchivo = array_values(array_unique(array_merge(
            array_column($reporte->registros, 'documento'),
            array_column($reporte->errores, 'dato')
        )));
=======
        // ── Aprendiz con Protección de Pertenencia de Ficha ──────────────
        $aprendiz = Aprendiz::where('Documento', $docAprendiz)->first();

        if ($aprendiz) {
            $datosAprendiz = [
                'Nombre'         => trim((string) ($fila[2] ?? 'N/A')),
                'Apellido'       => trim((string) ($fila[3] ?? 'N/A')),
                'Tipo_Documento' => 'CC',
                'Estado'         => trim((string) ($fila[4] ?? 'EN FORMACION')),
            ];

            // 🔒 BLOQUEO DE REASIGNACIÓN AUTOMÁTICA:
            // No vaciar la ficha de origen. Solo cambiar la ficha si el aprendiz no tenía ficha,
            // o si la política explícitamente autoriza el traslado ('PERMITIR_TRASLADO' o 'FORZAR_SOBRESCRITURA')
            if (empty($aprendiz->Id_Ficha) || in_array($politica, ['PERMITIR_TRASLADO', 'FORZAR_SOBRESCRITURA'])) {
                $datosAprendiz['Id_Ficha'] = $ficha->Id_Ficha;
            }

            $aprendiz->update($datosAprendiz);
        } else {
            // Nuevo aprendiz en el sistema: se vincula a la ficha procesada
            $aprendiz = Aprendiz::create([
                'Documento'      => $docAprendiz,
                'Nombre'         => trim((string) ($fila[2] ?? 'N/A')),
                'Apellido'       => trim((string) ($fila[3] ?? 'N/A')),
                'Id_Ficha'       => $ficha->Id_Ficha,
                'Tipo_Documento' => 'CC',
                'Estado'         => trim((string) ($fila[4] ?? 'EN FORMACION')),
            ]);
        }
>>>>>>> Stashed changes

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
        if ($this->cargaInicial) {
            return 'Es la carga inicial de la ficha: desde la próxima importación verás qué cambió.';
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

<<<<<<< Updated upstream
        return $partes
            ? 'Frente a la carga anterior: ' . implode(', ', $partes) . '.'
            : 'Sin cambios frente a la carga anterior.';
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
=======
        // ── Juicio Evaluativo con Protección de Integridad ────────────────
        $juicioRaw = strtoupper(trim((string) ($fila[7] ?? '')));
        $estado    = (!str_contains($juicioRaw, 'NO APROB') && !str_contains($juicioRaw, 'POR EVAL') && (str_contains($juicioRaw, 'APROB') || $juicioRaw === 'A' || $juicioRaw === 'S')) ? 1 : 0;

        $idResultado = $cacheResultados[$strRes];
        $idAprendiz  = $aprendiz->Id_Aprendiz;

        $juicioExistente = JuicioEvaluativo::where('Id_Resultado', $idResultado)
            ->where('Id_Aprendiz', $idAprendiz)
            ->first();

        if ($juicioExistente) {
            // El juicio ya existía previamente en la base de datos
            if ($juicioExistente->Estado == 1 && $estado == 0 && $politica === 'PRESERVAR_APROBADOS') {
                // 🛡️ REGLA DE ORO: No degradar de Aprobado a Pendiente
                $this->regresionesProtegidas++;
            } elseif ($juicioExistente->Estado == 0 && $estado == 1) {
                // 🟢 Nuevo avance académico: pasarlo a Aprobado
                $juicioExistente->update([
                    'Estado'         => 1,
                    'Id_Funcionario' => $cacheFuncionarios[$strFunc],
                    'Fecha'          => now()->format('Y-m-d'),
                ]);
                $this->nuevosAprobados++;
            } elseif ($politica === 'FORZAR_SOBRESCRITURA' && $juicioExistente->Estado != $estado) {
                // Sobrescritura explícita autorizada por el usuario
                $juicioExistente->update([
                    'Estado'         => $estado,
                    'Id_Funcionario' => $cacheFuncionarios[$strFunc],
                    'Fecha'          => now()->format('Y-m-d'),
                ]);
                if ($estado == 1) $this->nuevosAprobados++;
            }
        } else {
            // Registro nuevo que no existía en el sistema
            JuicioEvaluativo::create([
                'Id_Resultado'   => $idResultado,
                'Id_Aprendiz'    => $idAprendiz,
                'Estado'         => $estado,
                'Id_Funcionario' => $cacheFuncionarios[$strFunc],
                'Fecha'          => now()->format('Y-m-d'),
            ]);
            if ($estado == 1) {
                $this->nuevosAprobados++;
            }
        }
>>>>>>> Stashed changes

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
        ];
    }

    private static function etiquetaJuicio(int $estado): string
    {
        return $estado === 1 ? 'APROBADO' : 'POR EVALUAR';
    }
}
