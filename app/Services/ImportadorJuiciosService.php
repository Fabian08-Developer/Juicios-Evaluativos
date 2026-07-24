<?php

namespace App\Services;

use App\Events\ImportacionProcesada;
use App\Models\Ficha;
use App\Models\Programa;
use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Resultado;
use App\Models\Funcionario;
use App\Models\Importacion;
use App\Models\JuicioEvaluativo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MEJORA TÉCNICA #8 — Tolerancia a fallos por fila
 *
 * Problema anterior: Un error en la fila 50 hacía rollback() de TODAS las filas
 * previas — si 3 de 200 filas estaban corruptas, se perdían las 197 válidas.
 *
 * Solución: Procesar cada fila en su propio try/catch, acumular errores,
 * y al final hacer commit() de todo lo que sí funcionó.
 *
 * El reporte de errores se devuelve al controlador para mostrarlo al usuario.
 *
 * MEJORA TÉCNICA #7 — Disparo de Evento al finalizar
 * Al terminar, se dispara ImportacionProcesada que notifica a los Listeners
 * sin que este servicio sepa qué hacen con esa información (OCP).
 */
class ImportadorJuiciosService
{
    /** Resultado de la última ejecución para acceso externo */
    public array $erroresPorFila = [];
    public int   $procesados     = 0;

    public function procesarArchivoExcel(array $filas, ?string $fichaManual = null, ?Importacion $importacion = null): array
    {
        $inicio = microtime(true);
        DB::beginTransaction();

        try {
            Log::info("[Importador] Iniciando con " . count($filas) . " filas.");

            // ── FASE 1: Escaneo Inteligente de Cabecera ───────────────────────
            [$numeroFicha, $denominacion] = $this->escanearCabecera($filas);

            $denominacion = $denominacion ?: 'PROGRAMA SOFIA PLUS';
            $numeroFicha  = $numeroFicha  ?: $fichaManual;

            if (!$numeroFicha) {
                throw new \RuntimeException(
                    "No se detectó el número de Ficha. Selecciona una ficha manualmente o revisa el formato del Excel."
                );
            }

            // ── FASE 2: Asegurar Ficha y Programa en BD ───────────────────────
            $programa = Programa::firstOrCreate(
                ['Nombre'  => $denominacion],
                ['Codigo'  => 'S-PLUS', 'Modalidad' => 'PRESENCIAL', 'Version' => '1']
            );

            $ficha = Ficha::updateOrCreate(
                ['Id_Ficha'    => $numeroFicha],
                ['Id_Programa' => $programa->Id_Programa, 'Jornada' => 'DIURNA']
            );

            // ── FASE 3: Localizar fila de inicio de datos ─────────────────────
            $inicioDatos = $this->encontrarInicioDatos($filas);

            // ── FASE 4: CACHÉS EN MEMORIA (evita N+1 en competencias/instructores)
            $cacheCompetencias = [];
            $cacheResultados   = [];
            $cacheFuncionarios = [];

            // ── FASE 5: PROCESAMIENTO FILA A FILA CON TOLERANCIA A FALLOS ────
            $this->procesados    = 0;
            $this->erroresPorFila = [];

            for ($i = $inicioDatos; $i < count($filas); $i++) {
                $fila = $filas[$i];

                try {
                    $resultado = $this->procesarFila(
                        $fila, $i + 1, $ficha,
                        $cacheCompetencias, $cacheResultados, $cacheFuncionarios
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
            Log::info("[Importador] Finalizado. Procesados: {$this->procesados}, Errores: " . count($this->erroresPorFila));

            // ── FASE 6: Actualizar registro de importación ────────────────────
            if ($importacion) {
                $importacion->update([
                    'aprendices_procesados' => $this->procesados,
                    'duracion_segundos'     => (int) $duracion,
                    'estado'                => count($this->erroresPorFila) === 0 ? 'exitoso' : 'con_advertencias',
                    'detalle'               => count($this->erroresPorFila) > 0
                        ? count($this->erroresPorFila) . " fila(s) con error: " . collect($this->erroresPorFila)->pluck('dato')->implode(', ')
                        : "Importación completada sin errores.",
                ]);
            }

            // ── FASE 7: Disparar Evento (OCP — los Listeners hacen el resto) ──
            if ($importacion) {
                ImportacionProcesada::dispatch($importacion, $this->procesados, (string) $ficha->Id_Ficha, $this->erroresPorFila);
            }

            return [
                'status'    => 'success',
                'message'   => "Se procesaron {$this->procesados} registros correctamente." .
                               (count($this->erroresPorFila) > 0
                                   ? " (" . count($this->erroresPorFila) . " filas omitidas con error)"
                                   : ""),
                'procesados'      => $this->procesados,
                'errores'         => $this->erroresPorFila,
                'detalles'        => ['ficha' => $ficha->Id_Ficha],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Importador] Error fatal: " . $e->getMessage());
            throw $e;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  MÉTODOS PRIVADOS DE APOYO
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Escanea las primeras 20 filas buscando el número de ficha y la denominación.
     */
    private function escanearCabecera(array $filas): array
    {
        $numeroFicha  = null;
        $denominacion = null;

        foreach ($filas as $rIdx => $fila) {
            if ($rIdx > 20) break;

            $filaTexto = strtoupper(implode(' ', array_filter(array_map('strval', $fila))));

            if (!$numeroFicha && (str_contains($filaTexto, 'FICHA') || str_contains($filaTexto, 'CARACTERIZ'))) {
                foreach ($fila as $val) {
                    if (preg_match('/(\d{7,})/', trim((string) $val), $m)) {
                        $numeroFicha = $m[1];
                        break;
                    }
                }
            }

            if (!$denominacion && str_contains($filaTexto, 'DENOMINACI')) {
                foreach ($fila as $val) {
                    $v = trim((string) $val);
                    if (!empty($v) && !str_contains(strtoupper($v), 'DENOMINACI')) {
                        $denominacion = $v;
                        break;
                    }
                }
            }

            if ($numeroFicha && $denominacion) break;
        }

        return [$numeroFicha, $denominacion];
    }

    /**
     * Busca la fila donde comienzan los datos (fila con "DOCUMENTO" y "NOMBRE").
     */
    private function encontrarInicioDatos(array $filas): int
    {
        foreach ($filas as $idx => $fila) {
            $filaStr = strtoupper(implode(' ', array_filter(array_map('strval', $fila))));
            if (str_contains($filaStr, 'DOCUMENTO') && str_contains($filaStr, 'NOMBRE')) {
                return $idx + 1;
            }
        }
        return 13; // Fallback al default histórico
    }

    /**
     * Procesa una única fila de datos.
     * Lanza una excepción si la fila no es procesable.
     * Devuelve true si se procesó, false si se saltó (fila vacía válida).
     */
    public function procesarFila(
        array  $fila,
        int    $numFila,
        Ficha  $ficha,
        array  &$cacheCompetencias,
        array  &$cacheResultados,
        array  &$cacheFuncionarios
    ): bool {
        // Detectar documento del aprendiz (columnas 0, 1 o 2)
        $docAprendiz = null;
        foreach ([0, 1, 2] as $colIdx) {
            $val = trim((string) ($fila[$colIdx] ?? ''));
            if (is_numeric($val) && strlen($val) >= 7) {
                $docAprendiz = $val;
                break;
            }
        }

        // Fila vacía o sin documento → saltar sin error
        if (!$docAprendiz) {
            return false;
        }

        // ── Aprendiz ─────────────────────────────────────────────────────
        $aprendiz = Aprendiz::updateOrCreate(
            ['Documento' => $docAprendiz],
            [
                'Nombre'         => trim((string) ($fila[2] ?? 'N/A')),
                'Apellido'       => trim((string) ($fila[3] ?? 'N/A')),
                'Id_Ficha'       => $ficha->Id_Ficha,
                'Tipo_Documento' => 'CC',
                'Estado'         => trim((string) ($fila[4] ?? 'EN FORMACION')),
            ]
        );

        // ── Competencia (caché en memoria) ────────────────────────────────
        $strComp = (string) ($fila[5] ?? 'COMP-GEN');
        if (!isset($cacheCompetencias[$strComp])) {
            $partes  = explode(' - ', $strComp, 2);
            $codigo  = trim($partes[0]);
            $nombre  = $partes[1] ?? 'Competencia sin nombre';
            $comp    = Competencia::updateOrCreate(['Codigo' => $codigo], ['Nombre' => $nombre]);
            $cacheCompetencias[$strComp] = $comp->Id_Competencia;
        }

        // ── Resultado de Aprendizaje (caché en memoria) ───────────────────
        $strRes = (string) ($fila[6] ?? 'RAP-GEN');
        if (!isset($cacheResultados[$strRes])) {
            $partes  = explode(' - ', $strRes, 2);
            $codigo  = trim($partes[0]);
            $nombre  = $partes[1] ?? 'Resultado sin nombre';
            $res     = Resultado::updateOrCreate(
                ['Codigo' => $codigo],
                ['Nombre' => $nombre, 'Id_Competencia' => $cacheCompetencias[$strComp]]
            );
            $cacheResultados[$strRes] = $res->Id_Resultado;
        }

        // ── Funcionario / Instructor (caché en memoria) ───────────────────
        $strFunc = trim((string) ($fila[9] ?? 'INSTRUCTOR SENA'));
        if (!isset($cacheFuncionarios[$strFunc])) {
            $partes   = explode(' - ', $strFunc, 2);
            $docFunc  = preg_replace('/[^0-9]/', '', $partes[0]) ?: '1000';
            $nombre   = $partes[1] ?? $strFunc;
            $inst     = Funcionario::firstOrCreate(
                ['Documento' => $docFunc],
                ['Nombre' => $nombre, 'Tipo_Documento' => 'CC', 'Apellido' => 'SENA']
            );
            $cacheFuncionarios[$strFunc] = $inst->Id_Funcionario;
        }

        // ── Juicio Evaluativo ─────────────────────────────────────────────
        $juicioRaw = strtoupper(trim((string) ($fila[7] ?? '')));
        $estado    = (str_contains($juicioRaw, 'APROB') || $juicioRaw === 'A' || $juicioRaw === 'S') ? 1 : 0;

        JuicioEvaluativo::updateOrCreate(
            ['Id_Resultado' => $cacheResultados[$strRes], 'Id_Aprendiz' => $aprendiz->Id_Aprendiz],
            [
                'Estado'        => $estado,
                'Id_Funcionario'=> $cacheFuncionarios[$strFunc],
                'Fecha'         => now()->format('Y-m-d'),
            ]
        );

        return true;
    }
}
