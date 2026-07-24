<?php

namespace App\Listeners;

use App\Events\ImportacionProcesada;
use Illuminate\Support\Facades\Cache;

/**
 * LISTENER 1 — Invalida el caché del dashboard cuando hay nuevos datos.
 *
 * Sin este listener, el dashboard mostraría datos obsoletos hasta que
 * el caché expire naturalmente (5 minutos).
 *
 * Con este listener: el caché se limpia INMEDIATAMENTE después de importar,
 * garantizando que la próxima visita al dashboard muestre datos actualizados.
 */
class ActualizarCacheDashboard
{
    public function handle(ImportacionProcesada $event): void
    {
        $fichaId = $event->fichaId;

        // Invalidar caché específico de la ficha importada
        Cache::forget("dashboard.stats.ficha.{$fichaId}");

        // Invalidar también el caché global (todas las fichas)
        Cache::forget('dashboard.stats.global');

        // Invalidar caché de la lista de aprendices (si se implementara)
        Cache::forget("aprendices.ficha.{$fichaId}");

        \Illuminate\Support\Facades\Log::info(
            "[Cache] Caché del dashboard invalidado para ficha {$fichaId} " .
            "tras importar {$event->aprendicesProcesados} aprendices."
        );
    }
}
