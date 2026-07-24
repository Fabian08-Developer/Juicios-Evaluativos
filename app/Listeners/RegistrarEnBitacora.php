<?php

namespace App\Listeners;

use App\Events\ImportacionProcesada;
use Illuminate\Support\Facades\Log;

/**
 * LISTENER 2 — Registra un log detallado de la importación.
 *
 * Genera una entrada estructurada en storage/logs/laravel.log con:
 * - Ficha procesada
 * - Total de aprendices importados
 * - Errores por fila (si los hubo)
 * - Duración de la operación
 *
 * Útil para auditoría y depuración de problemas en producción.
 */
class RegistrarEnBitacora
{
    public function handle(ImportacionProcesada $event): void
    {
        $errores = $event->erroresPorFila;
        $imp     = $event->importacion;

        $contexto = [
            'ficha'          => $event->fichaId,
            'procesados'     => $event->aprendicesProcesados,
            'errores_count'  => count($errores),
            'duracion_s'     => $imp->duracion_segundos,
            'archivo'        => $imp->nombre_archivo,
            'estado'         => $imp->estado,
        ];

        if (!empty($errores)) {
            // Log de advertencia con detalle de filas problemáticas
            Log::warning('[Importación] Completada con advertencias.', array_merge(
                $contexto,
                ['errores_detalle' => $errores]
            ));
        } else {
            Log::info('[Importación] Completada exitosamente.', $contexto);
        }
    }
}
