<?php

namespace App\Services;

use App\Events\ImportacionProcesada;
use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\Importacion;
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
 */
class ImportadorJuiciosService
{
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
    /** @var array<string,int|string> aprendices que ya estaban en otra ficha: documento => ficha anterior */
    private array $aprendicesMovidos = [];
    private int $juiciosProcesados = 0;
    private int $aprobacionesLocalesConservadas = 0;

    /**
     * @param  array<int,array<int,mixed>>  $filas  Filas de la hoja (Excel::toArray)
     * @param  string|null  $fichaManual  Ficha elegida en el formulario (opcional)
     * @return array{status:string,message:string,procesados:int,aprendices:int,errores:array,advertencias:array,detalles:array}
     *
     * @throws \RuntimeException si el archivo no es un reporte válido o la ficha no coincide
     */
    public function procesarArchivoExcel(array $filas, ?string $fichaManual = null, ?Importacion $importacion = null): array
    {
        $inicio = microtime(true);
        $this->reiniciar();

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
        $conservarLocales = (bool) config('sena.importacion.conservar_aprobados_locales', true);

        $aprendicesProcesados = DB::transaction(function () use ($reporte, $numeroFicha, $conservarLocales) {
            $ficha = $this->asegurarFichaYPrograma($reporte, $numeroFicha);
            $aprendicesOk = [];

            foreach ($reporte->registros as $registro) {
                $movidosAntes = $this->aprendicesMovidos;

                try {
                    // Transacción anidada => SAVEPOINT: si falla, solo se revierte esta fila.
                    DB::transaction(fn () => $this->procesarRegistro($registro, $ficha, $conservarLocales));

                    $this->juiciosProcesados++;
                    $aprendicesOk[$registro['documento']] = true;
                } catch (\Throwable $e) {
                    // Lo que esta fila creó fue revertido: las cachés en memoria ya no
                    // son confiables (podrían apuntar a registros inexistentes).
                    $this->cacheAprendices = $this->cacheCompetencias = $this->cacheResultados = [];
                    $this->cacheFuncionarios = $this->cacheJuicios = [];
                    $this->aprendicesMovidos = $movidosAntes;

                    $this->errores[] = [
                        'fila'  => $registro['fila'],
                        'dato'  => $registro['documento'],
                        'error' => $e->getMessage(),
                    ];
                    Log::warning("[Importador] Fila {$registro['fila']} omitida: " . $e->getMessage());
                }
            }

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
    private function procesarRegistro(array $r, Ficha $ficha, bool $conservarLocales): void
    {
        $aprendiz = $this->resolverAprendiz($r, $ficha);
        $resultadoId = $this->resolverResultado($r);
        $funcionarioId = $this->resolverFuncionario($r['funcionario']);

        $estado = $r['aprobado'] ? 1 : 0;
        $juicio = $this->juiciosDe($aprendiz)->get($resultadoId);

        if ($juicio) {
            // Una aprobación hecha a mano en la matriz no se borra porque el
            // Excel (aún) diga "POR EVALUAR". Si el Excel ya la trae aprobada,
            // el dato oficial prevalece.
            if ($conservarLocales && $estado === 0 && (int) $juicio->Estado === 1 && $juicio->registrado_por !== null) {
                $this->aprobacionesLocalesConservadas++;
                return;
            }

            $juicio->fill([
                'Estado'         => $estado,
                'Id_Funcionario' => $funcionarioId,
                'registrado_por' => null,
                'Fecha'          => $r['fecha']?->toDateString(),
                'Hora'           => $r['fecha'],
            ])->save();

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
            if ((int) $aprendiz->Id_Ficha !== (int) $ficha->Id_Ficha) {
                // El documento es único en todo el sistema: el aprendiz "se mueve".
                // Sus juicios anteriores se conservan; se avisa al usuario.
                $this->aprendicesMovidos[$doc] = $aprendiz->Id_Ficha;
            }
            $aprendiz->fill($datos)->save();
        } else {
            $aprendiz = Aprendiz::create(['Documento' => $doc] + $datos);
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

        if ($this->aprendicesMovidos) {
            $a[] = count($this->aprendicesMovidos) . ' aprendiz(es) ya estaban registrados en otra ficha y fueron movidos a esta '
                . '(sus juicios anteriores se conservan): documentos ' . implode(', ', array_slice(array_keys($this->aprendicesMovidos), 0, 10))
                . (count($this->aprendicesMovidos) > 10 ? ', …' : '') . '.';
        }
        if ($this->aprobacionesLocalesConservadas) {
            $a[] = "{$this->aprobacionesLocalesConservadas} juicio(s) aprobados manualmente en la matriz se conservaron aunque el Excel aún los muestra «POR EVALUAR».";
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
        if ($this->errores) {
            $mensaje .= ' ' . count($this->errores) . ' fila(s) omitidas con error.';
        }
        if ($advertencias) {
            $mensaje .= ' Atención: ' . implode(' ', $advertencias);
        }

        return [
            'status'       => 'success',
            'message'      => $mensaje,
            'procesados'   => $this->juiciosProcesados,
            'aprendices'   => $aprendices,
            'errores'      => $this->errores,
            'advertencias' => $advertencias,
            'detalles'     => ['ficha' => $ficha],
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
        ]);
    }

    private function reiniciar(): void
    {
        $this->cacheCompetencias = $this->cacheResultados = $this->cacheFuncionarios = [];
        $this->cacheAprendices = $this->cacheJuicios = [];
        $this->errores = $this->aprendicesMovidos = [];
        $this->juiciosProcesados = $this->aprobacionesLocalesConservadas = 0;
    }
}
