<?php

namespace App\Services;

/**
 * Cálculo ÚNICO del semáforo de riesgo de deserción.
 *
 * Antes la fórmula estaba copiada en el diagnóstico y en la emisión de alertas,
 * con resultados distintos (la pantalla mostraba 85 y la remisión guardaba 72
 * para el mismo aprendiz). Ahora ambos usan este servicio, y los umbrales viven
 * en config/sena.php.
 *
 * Nota de dominio: el score es el % de juicios PENDIENTES. No considera cuánto
 * del programa ha transcurrido; al inicio de la formación casi todos los
 * juicios están por evaluar. Es una heurística, no una predicción.
 */
class RiesgoDesercionService
{
    /** Estados en los que el aprendiz ya no continúa en la ficha. */
    public const ESTADOS_TERMINALES = ['RETIRO VOLUNTARIO', 'CANCELADO', 'TRASLADADO'];

    /**
     * @return array{score:int, nivel:string, label:string, color:string}
     *         nivel: 'critico' | 'moderado' | 'estable'
     */
    public function evaluar(?string $estado, int $totalJuicios, int $pendientes): array
    {
        $cfg = config('sena.riesgo');

        $porcentajePendientes = $totalJuicios > 0 ? ($pendientes / $totalJuicios) * 100 : 0;
        $score = (int) round($porcentajePendientes);

        if (in_array($estado, self::ESTADOS_TERMINALES, true)) {
            $score = 100;
        } elseif ($porcentajePendientes >= $cfg['umbral_alerta']) {
            $score = max($cfg['score_alerta'], $score);
        }

        $score = min(100, $score);

        if ($score >= $cfg['critico']) {
            return ['score' => $score, 'nivel' => 'critico', 'label' => '🔴 Crítico', 'color' => '#ef4444'];
        }
        if ($score >= $cfg['moderado']) {
            return ['score' => $score, 'nivel' => 'moderado', 'label' => '🟡 Moderado', 'color' => '#f59e0b'];
        }

        return ['score' => $score, 'nivel' => 'estable', 'label' => '🟢 Estable', 'color' => '#10b981'];
    }
}
