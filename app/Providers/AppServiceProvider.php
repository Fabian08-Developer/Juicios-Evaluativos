<?php

namespace App\Providers;

use App\Events\ImportacionProcesada;
use App\Listeners\ActualizarCacheDashboard;
use App\Listeners\RegistrarEnBitacora;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider — Registro de servicios y eventos.
 *
 * MEJORA TÉCNICA #7: Los eventos y listeners se registran aquí.
 * Agregar un nuevo listener NO requiere tocar el código del importador (OCP).
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── Registro de Eventos ────────────────────────────────────────────
        // ImportacionProcesada → activa múltiples listeners independientes
        Event::listen(
            ImportacionProcesada::class,
            ActualizarCacheDashboard::class   // Invalidar caché del dashboard
        );
        Event::listen(
            ImportacionProcesada::class,
            RegistrarEnBitacora::class        // Log detallado de auditoría
        );

        // Para agregar más comportamiento post-importación, solo añade:
        // Event::listen(ImportacionProcesada::class, NuevoListener::class);
        // Sin tocar ImportadorJuiciosService ni el controlador.
    }
}
