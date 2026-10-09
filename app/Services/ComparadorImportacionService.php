<?php

namespace App\Services;

use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\Importacion;
use App\Models\JuicioEvaluativo;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ComparadorImportacionService
{
    /**
     * Analiza el archivo Excel frente a los datos actuales en la base de datos.
     * Retorna un diagnóstico completo sin alterar la base de datos.
     */
    public function analizar(array $filas, ?string $fichaManual = null): array
    {
        // 1. Escanear cabecera para identificar ficha, denominación y fecha del reporte
        [$numeroFichaDetectado, $denominacion, $fechaReporte] = $this->escanearCabecera($filas);
        $numeroFicha = $fichaManual ?: $numeroFichaDetectado;

        if (!$numeroFicha) {
            return [
                'valido'       => false,
                'error'        => 'No se detectó el número de ficha en el archivo ni se seleccionó manualmente.',
                'numero_ficha' => null,
            ];
        }

        // 2. Localizar inicio de datos
        $inicioDatos = $this->encontrarInicioDatos($filas);

        // 3. Pre-escanear documentos de aprendices presentes en el Excel
        $docsEnExcel = [];
        for ($i = $inicioDatos; $i < count($filas); $i++) {
            $fila = $filas[$i];
            foreach ([0, 1, 2] as $colIdx) {
                $val = trim((string) ($fila[$colIdx] ?? ''));
                if (is_numeric($val) && strlen($val) >= 7) {
                    $docsEnExcel[$val] = [
                        'nombre' => trim((string) ($fila[2] ?? '')) . ' ' . trim((string) ($fila[3] ?? '')),
                        'estado' => trim((string) ($fila[4] ?? 'EN FORMACION')),
                    ];
                    break;
                }
            }
        }

        // 4. Consultar datos en BD: buscar por Ficha O por los documentos de los aprendices
        $aprendicesBD = Aprendiz::where(function ($query) use ($numeroFicha, $docsEnExcel) {
            if ($numeroFicha) {
                $query->where('Id_Ficha', $numeroFicha);
            }
            if (!empty($docsEnExcel)) {
                $query->orWhereIn('Documento', array_keys($docsEnExcel));
            }
        })
        ->with(['juicios.resultado.competencia', 'ficha'])
        ->get();

        // Consultar última importación previa para esta ficha
        $ultimaImportacion = Importacion::where(function ($q) use ($numeroFicha, $numeroFichaDetectado, $fichaManual) {
            if ($numeroFicha) $q->where('id_ficha', $numeroFicha);
            if ($numeroFichaDetectado) $q->orWhere('id_ficha', $numeroFichaDetectado);
            if ($fichaManual) $q->orWhere('id_ficha', $fichaManual);
        })
        ->where('estado', '!=', 'error')
        ->latest()
        ->first();

        // 5. Mapear estado actual en BD en memoria
        $mapaBD = [];
        $aprendicesBDMap = [];
        $totalAprobadosBD = 0;
        $totalPendientesBD = 0;
        $fichasPreviasEnBD = [];

        foreach ($aprendicesBD as $ap) {
            $doc = trim((string) $ap->Documento);
            $aprendicesBDMap[$doc] = [
                'id'       => $ap->Id_Aprendiz,
                'nombre'   => trim("{$ap->Nombre} {$ap->Apellido}"),
                'estado'   => $ap->Estado,
                'id_ficha' => $ap->Id_Ficha,
            ];

            if ($ap->Id_Ficha) {
                $fichasPreviasEnBD[$ap->Id_Ficha] = true;
            }

            foreach ($ap->juicios as $j) {
                $codRap = $j->resultado->Codigo ?? 'RAP-GEN';
                $mapaBD[$doc][$codRap] = [
                    'estado'         => (int) $j->Estado,
                    'competencia'    => $j->resultado->competencia->Codigo ?? 'COMP',
                    'nombre_comp'    => $j->resultado->competencia->Nombre ?? '',
                    'nombre_rap'     => $j->resultado->Nombre ?? '',
                ];

                if ($j->Estado == 1) {
                    $totalAprobadosBD++;
                } else {
                    $totalPendientesBD++;
                }
            }
        }

        $esPrimeraImportacion = $aprendicesBD->isEmpty() || ($totalAprobadosBD === 0 && $totalPendientesBD === 0);

        // 6. Analizar filas del Excel frente a la BD
        $nuevosAprobados          = [];
        $regresiones              = [];
        $sinCambios               = 0;
        $cambiosEstadoAprendiz    = [];
        $totalJuiciosEnExcel      = 0;
        $totalAprobadosEnExcel    = 0;

        for ($i = $inicioDatos; $i < count($filas); $i++) {
            $fila = $filas[$i];

            // Extraer documento
            $docAprendiz = null;
            foreach ([0, 1, 2] as $colIdx) {
                $val = trim((string) ($fila[$colIdx] ?? ''));
                if (is_numeric($val) && strlen($val) >= 7) {
                    $docAprendiz = $val;
                    break;
                }
            }

            if (!$docAprendiz) continue;

            $nombreCompleto = trim((string) ($fila[2] ?? '')) . ' ' . trim((string) ($fila[3] ?? ''));
            $estadoExcel    = trim((string) ($fila[4] ?? 'EN FORMACION'));

            // Verificar cambio de estado del aprendiz (ej. En Formación vs Retirado)
            if (isset($aprendicesBDMap[$docAprendiz])) {
                $estadoPrevio = $aprendicesBDMap[$docAprendiz]['estado'];
                if (strtoupper($estadoPrevio) !== strtoupper($estadoExcel)) {
                    if (!isset($cambiosEstadoAprendiz[$docAprendiz])) {
                        $cambiosEstadoAprendiz[$docAprendiz] = [
                            'documento'      => $docAprendiz,
                            'nombre'         => $nombreCompleto,
                            'estado_anterior'=> $estadoPrevio,
                            'estado_nuevo'   => $estadoExcel,
                        ];
                    }
                }
            }

            // Datos de competencia y RAP
            $strComp = (string) ($fila[5] ?? 'COMP-GEN');
            $partesComp = explode(' - ', $strComp, 2);
            $codComp = trim($partesComp[0]);
            $nombreComp = $partesComp[1] ?? 'Competencia';

            $strRes = (string) ($fila[6] ?? 'RAP-GEN');
            $partesRes = explode(' - ', $strRes, 2);
            $codRes = trim($partesRes[0]);
            $nombreRes = $partesRes[1] ?? 'Resultado';

            // Estado en Excel
            $juicioRaw = strtoupper(trim((string) ($fila[7] ?? '')));
            $estadoJuicioExcel = (!str_contains($juicioRaw, 'NO APROB') && !str_contains($juicioRaw, 'POR EVAL') && (str_contains($juicioRaw, 'APROB') || $juicioRaw === 'A' || $juicioRaw === 'S')) ? 1 : 0;

            $totalJuiciosEnExcel++;
            if ($estadoJuicioExcel === 1) {
                $totalAprobadosEnExcel++;
            }

            // Comparar contra BD
            if (isset($mapaBD[$docAprendiz][$codRes])) {
                $estadoActualBD = $mapaBD[$docAprendiz][$codRes]['estado'];

                if ($estadoActualBD === 1 && $estadoJuicioExcel === 0) {
                    // 🚨 REGRESIÓN: En BD ya estaba APROBADO, en Excel viene PENDIENTE
                    $regresiones[] = [
                        'documento'   => $docAprendiz,
                        'aprendiz'    => $docsEnExcel[$docAprendiz]['nombre'] ?: ($aprendicesBDMap[$docAprendiz]['nombre'] ?? $docAprendiz),
                        'competencia' => $codComp,
                        'resultado'   => $codRes,
                        'nombre_rap'  => $nombreRes,
                        'fila_excel'  => $i + 1,
                    ];
                } elseif ($estadoActualBD === 0 && $estadoJuicioExcel === 1) {
                    // 🟢 AVANCE: Estaba pendiente en BD y ahora se aprueba
                    $nuevosAprobados[] = [
                        'documento'   => $docAprendiz,
                        'aprendiz'    => $docsEnExcel[$docAprendiz]['nombre'],
                        'competencia' => $codComp,
                        'resultado'   => $codRes,
                        'nombre_rap'  => $nombreRes,
                    ];
                } else {
                    $sinCambios++;
                }
            } else {
                // Nuevo registro que no existía en BD
                if ($estadoJuicioExcel === 1) {
                    $nuevosAprobados[] = [
                        'documento'   => $docAprendiz,
                        'aprendiz'    => $docsEnExcel[$docAprendiz]['nombre'],
                        'competencia' => $codComp,
                        'resultado'   => $codRes,
                        'nombre_rap'  => $nombreRes,
                    ];
                } else {
                    $sinCambios++;
                }
            }
        }

        // 7. Comparar presencia de aprendices
        $aprendicesNuevos = [];
        foreach ($docsEnExcel as $doc => $data) {
            if (!isset($aprendicesBDMap[$doc])) {
                $aprendicesNuevos[] = [
                    'documento' => $doc,
                    'nombre'    => $data['nombre'],
                    'estado'    => $data['estado'],
                ];
            }
        }

        $aprendicesAusentes = [];
        foreach ($aprendicesBDMap as $doc => $data) {
            if (!isset($docsEnExcel[$doc])) {
                $aprendicesAusentes[] = [
                    'documento' => $doc,
                    'nombre'    => $data['nombre'],
                    'estado'    => $data['estado'],
                ];
            }
        }

        // 8. Diagnóstico y evaluación de anomalías
        $tieneRegresiones      = count($regresiones) > 0;
        $tieneAusentes        = count($aprendicesAusentes) > 0;
        $totalAprobadosMenor  = ($totalAprobadosEnExcel < $totalAprobadosBD);

        // Comparación de fecha
        $fechaUltimaImportacion = $ultimaImportacion ? $ultimaImportacion->created_at->format('Y-m-d') : null;
        $esFechaAnterior = false;
        if ($fechaReporte && $fechaUltimaImportacion) {
            $esFechaAnterior = ($fechaReporte < $fechaUltimaImportacion);
        }

        // Verificación de pertenencia y discordancia de ficha
        $fichasPreviasList = array_keys($fichasPreviasEnBD);
        $conflictosFicha = [];

        foreach ($docsEnExcel as $doc => $data) {
            if (isset($aprendicesBDMap[$doc]) && !empty($aprendicesBDMap[$doc]['id_ficha'])) {
                $fichaActual = (string) $aprendicesBDMap[$doc]['id_ficha'];
                if ($fichaActual !== (string) $numeroFicha) {
                    $conflictosFicha[] = [
                        'documento'    => $doc,
                        'nombre'       => $data['nombre'] ?: $aprendicesBDMap[$doc]['nombre'],
                        'ficha_actual' => $fichaActual,
                        'ficha_nueva'  => $numeroFicha,
                    ];
                }
            }
        }

        $tieneConflictosFicha = count($conflictosFicha) > 0;
        $fichaCambioDetectado = $tieneConflictosFicha;

        // ¿Requiere decisión interactiva del usuario?
        // Se activa ante CUALQUIER regresión, conflicto de ficha, fecha más antigua o aprendices faltantes
        $requiereDecision = !$esPrimeraImportacion && (
            $tieneRegresiones ||
            $tieneConflictosFicha ||
            $tieneAusentes ||
            $totalAprobadosMenor ||
            $esFechaAnterior
        );

        if ($esPrimeraImportacion) {
            $tipoDiagnostico = 'PRIMERA_IMPORTACION';
            $mensaje = "Primera importación para la ficha {$numeroFicha}. Se crearán todos los registros.";
        } elseif ($tieneRegresiones) {
            $tipoDiagnostico = 'ALERTA_REGRESION';
            $mensaje = "¡ATENCIÓN! Se detectaron " . count($regresiones) . " calificaciones que ya estaban APROBADAS en el sistema pero en este archivo figuran como PENDIENTES. Este archivo podría ser una versión desactualizada o incompleta.";
        } elseif ($tieneConflictosFicha) {
            $tipoDiagnostico = 'ALERTA_CONFLICTO_FICHA';
            $mensaje = "¡ATENCIÓN! Se detectaron " . count($conflictosFicha) . " aprendices en este archivo que ya pertenecen a la Ficha " . implode(', ', $fichasPreviasList) . ". El sistema NO les cambiará la ficha automáticamente para no desvincularlos ni vaciar su ficha de origen.";
        } elseif ($esFechaAnterior && $totalAprobadosMenor) {
            $tipoDiagnostico = 'ALERTA_ARCHIVO_OBSOLETO';
            $mensaje = "¡ATENCIÓN! La fecha del reporte ({$fechaReporte}) es anterior a la última importación registrada ({$fechaUltimaImportacion}) y contiene menos juicios aprobados que el sistema.";
        } elseif ($tieneAusentes) {
            $tipoDiagnostico = 'ALERTA_APRENDICES_FALTANTES';
            $mensaje = "El archivo omite a " . count($aprendicesAusentes) . " aprendiz(ces) que ya se encuentran registrados en el sistema.";
        } else {
            $tipoDiagnostico = 'EVOLUCION_LIMPIA';
            $mensaje = "Archivo consistente. Contiene " . count($nuevosAprobados) . " nuevos juicios aprobados sin ninguna regresión.";
        }

        return [
            'valido'                   => true,
            'numero_ficha'             => $numeroFicha,
            'numero_ficha_archivo'     => $numeroFichaDetectado,
            'denominacion'             => $denominacion ?: 'PROGRAMA SOFIA PLUS',
            'fecha_reporte'            => $fechaReporte,
            'fecha_ultima_importacion' => $fechaUltimaImportacion,
            'es_fecha_anterior'        => $esFechaAnterior,
            'ficha_cambio_detectado'   => $fichaCambioDetectado,
            'tiene_conflictos_ficha'   => $tieneConflictosFicha,
            'conflictos_ficha'         => $conflictosFicha,
            'fichas_previas'           => $fichasPreviasList,
            'es_primera_importacion'   => $esPrimeraImportacion,
            'tipo_diagnostico'         => $tipoDiagnostico,
            'mensaje'                  => $mensaje,
            'tiene_regresiones'        => $tieneRegresiones,
            'tiene_ausentes'           => $tieneAusentes,
            'requiere_decision'        => $requiereDecision,

            // Métricas comparativas
            'aprendices_bd_count'      => count($aprendicesBDMap),
            'aprendices_excel_count'   => count($docsEnExcel),
            'total_aprobados_bd'       => $totalAprobadosBD,
            'total_aprobados_excel'    => $totalAprobadosEnExcel,
            'total_juicios_excel'      => $totalJuiciosEnExcel,

            // Deltas
            'conteo_nuevos_aprobados'  => count($nuevosAprobados),
            'conteo_regresiones'       => count($regresiones),
            'conteo_sin_cambios'       => $sinCambios,
            'conteo_aprendices_nuevos' => count($aprendicesNuevos),
            'conteo_aprendices_ausentes'=> count($aprendicesAusentes),
            'conteo_cambios_estado'    => count($cambiosEstadoAprendiz),

            // Listas detalladas (para previsualización)
            'nuevos_aprobados'         => array_slice($nuevosAprobados, 0, 50),
            'regresiones'              => $regresiones,
            'aprendices_nuevos'        => $aprendicesNuevos,
            'aprendices_ausentes'      => $aprendicesAusentes,
            'cambios_estado_aprendiz'  => array_values($cambiosEstadoAprendiz),
        ];
    }

    /**
     * Extrae número de ficha, denominación y fecha del reporte desde la cabecera.
     */
    private function escanearCabecera(array $filas): array
    {
        $numeroFicha  = null;
        $denominacion = null;
        $fechaReporte = null;

        foreach ($filas as $rIdx => $fila) {
            if ($rIdx > 20) break;
            $filaTexto = strtoupper(implode(' ', array_filter(array_map('strval', $fila))));

            // Ficha
            if (!$numeroFicha && (str_contains($filaTexto, 'FICHA') || str_contains($filaTexto, 'CARACTERIZ'))) {
                foreach ($fila as $val) {
                    if (preg_match('/(\d{7,})/', trim((string) $val), $m)) {
                        $numeroFicha = $m[1];
                        break;
                    }
                }
            }

            // Denominación
            if (!$denominacion && str_contains($filaTexto, 'DENOMINACI')) {
                foreach ($fila as $val) {
                    $v = trim((string) $val);
                    if (!empty($v) && !str_contains(strtoupper($v), 'DENOMINACI')) {
                        $denominacion = $v;
                        break;
                    }
                }
            }

            // Fecha del Reporte
            if (!$fechaReporte && str_contains($filaTexto, 'FECHA DEL REPORTE')) {
                foreach ($fila as $val) {
                    $parsed = $this->parseFechaReporte($val);
                    if ($parsed) {
                        $fechaReporte = $parsed;
                        break;
                    }
                }
            }

            if ($numeroFicha && $denominacion && $fechaReporte) break;
        }

        return [$numeroFicha, $denominacion, $fechaReporte];
    }

    /**
     * Convierte fechas de reporte de formatos texto o número serial de Excel a 'Y-m-d'.
     */
    private function parseFechaReporte($val): ?string
    {
        if (empty($val)) return null;
        $valStr = trim((string) $val);

        // Si es número serial de Excel (ej: 45698)
        if (is_numeric($valStr) && (int)$valStr > 30000 && (int)$valStr < 60000) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$valStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Formato dd/mm/yyyy o dd-mm-yyyy
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $valStr, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        // Formato yyyy-mm-dd
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valStr)) {
            return $valStr;
        }

        return null;
    }

    /**
     * Localiza la fila donde comienzan los datos tabulares.
     */
    private function encontrarInicioDatos(array $filas): int
    {
        foreach ($filas as $idx => $fila) {
            $filaStr = strtoupper(implode(' ', array_filter(array_map('strval', $fila))));
            if (str_contains($filaStr, 'DOCUMENTO') && str_contains($filaStr, 'NOMBRE')) {
                return $idx + 1;
            }
        }
        return 13;
    }
}
