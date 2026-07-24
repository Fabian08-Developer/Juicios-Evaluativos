<?php

namespace App\Events;

use App\Models\Importacion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MEJORA TÉCNICA #7 — Evento del sistema
 *
 * Se dispara cuando una importación de Excel termina (exitosa o con errores).
 *
 * Patrón: Observer / Event-Driven
 * Principio: Open/Closed — agregar nuevo comportamiento post-importación
 *            sin modificar el código del ImportadorJuiciosService.
 *
 * Listeners registrados (en AppServiceProvider):
 *   - ActualizarCacheDashboard → invalida el caché automáticamente
 *   - RegistrarEnBitacora      → guarda detalle en Log
 */
class ImportacionProcesada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Importacion $importacion,
        public readonly int         $aprendicesProcesados,
        public readonly string      $fichaId,
        public readonly array       $erroresPorFila = [],
    ) {}
}
