<?php

namespace App\Exceptions;

/**
 * El reporte desharía aprobaciones o sacaría aprendices de otra ficha: la
 * importación se revirtió completa y el usuario debe decidir cómo aplicarla.
 *
 * $analisis trae lo necesario para la pantalla de decisión (ver
 * ImportadorJuiciosService::analisisParaDecidir()).
 */
class ImportacionRequiereDecision extends \RuntimeException
{
    /** @param  array<string,mixed>  $analisis */
    public function __construct(public readonly array $analisis)
    {
        parent::__construct('El reporte requiere una decisión antes de aplicarse.');
    }
}
