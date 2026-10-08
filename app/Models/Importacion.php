<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = [
        'nombre_archivo',
        'id_ficha',
        'aprendices_procesados',
        'duracion_segundos',
        'estado',
        'detalle',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Colores y etiqueta del estado para la vista del historial.
     * Antes cualquier estado distinto de «exitoso» (incluido «con_advertencias»)
     * se mostraba como «✗ Error».
     *
     * @return array<string,string>
     */
    public function getEstadoVisualAttribute(): array
    {
        return match ($this->estado) {
            'exitoso' => [
                'dot_bg' => 'var(--primary)', 'dot_border' => 'rgba(57,169,0,0.3)', 'dot_glow' => 'var(--primary-glow)',
                'badge_bg' => 'rgba(57,169,0,0.1)', 'badge_color' => 'var(--primary)', 'badge_border' => 'rgba(57,169,0,0.2)',
                'label' => '✓ Exitoso',
            ],
            'con_advertencias' => [
                'dot_bg' => '#f59e0b', 'dot_border' => 'rgba(245,158,11,0.3)', 'dot_glow' => 'rgba(245,158,11,0.4)',
                'badge_bg' => 'rgba(245,158,11,0.1)', 'badge_color' => '#fcd34d', 'badge_border' => 'rgba(245,158,11,0.2)',
                'label' => '⚠ Con advertencias',
            ],
            'procesando' => [
                'dot_bg' => '#94a3b8', 'dot_border' => 'rgba(148,163,184,0.3)', 'dot_glow' => 'rgba(148,163,184,0.4)',
                'badge_bg' => 'rgba(148,163,184,0.1)', 'badge_color' => '#cbd5e1', 'badge_border' => 'rgba(148,163,184,0.2)',
                'label' => '… Procesando',
            ],
            default => [
                'dot_bg' => '#ef4444', 'dot_border' => 'rgba(239,68,68,0.3)', 'dot_glow' => 'rgba(239,68,68,0.4)',
                'badge_bg' => 'rgba(239,68,68,0.1)', 'badge_color' => '#fca5a5', 'badge_border' => 'rgba(239,68,68,0.2)',
                'label' => '✗ Error',
            ],
        };
    }
}
