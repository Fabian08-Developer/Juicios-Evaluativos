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
        'user_id',
        'aprendices_procesados',
        'duracion_segundos',
        'estado',
        'detalle',
        'resumen',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'resumen'    => 'array',
    ];

<<<<<<< Updated upstream
    public function cambios()
    {
        return $this->hasMany(ImportacionCambio::class);
    }

    /** Usuario del sistema que subió el reporte. */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Carga anterior de la misma ficha (la base contra la que se compararon los cambios). */
    public function anteriorDeLaFicha(): ?self
    {
        return self::where('id_ficha', $this->id_ficha)
            ->whereNotNull('resumen')
            ->where('id', '<', $this->id)
            ->orderByDesc('id')
            ->first();
    }

    /** Cantidad de cambios de un tipo según el resumen guardado. */
    public function conteoCambios(string $tipo): int
    {
        return (int) ($this->resumen['cambios'][$tipo] ?? 0);
    }

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
=======
    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'id_ficha', 'Id_Ficha');
    }

    /**
     * Extrae el conteo de nuevos juicios aprobados desde el detalle si existe.
     */
    public function getNuevosAprobadosAttribute(): int
    {
        if (preg_match('/\+(\d+)\s+nuevos\s+aprobados/i', $this->detalle ?? '', $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Extrae el conteo de regresiones protegidas desde el detalle si existe.
     */
    public function getRegresionesProtegidasAttribute(): int
    {
        if (preg_match('/(\d+)\s+juicios\s+protegidos/i', $this->detalle ?? '', $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Calcula la velocidad de procesamiento en registros por segundo.
     */
    public function getVelocidadAttribute(): float
    {
        if ($this->duracion_segundos > 0 && $this->aprendices_procesados > 0) {
            return round($this->aprendices_procesados / $this->duracion_segundos, 1);
        }
        return (float) $this->aprendices_procesados;
>>>>>>> Stashed changes
    }
}
