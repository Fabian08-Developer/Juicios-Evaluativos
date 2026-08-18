<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Remision extends Model
{
    use HasFactory;

    protected $table = 'remisiones';

    protected $fillable = [
        'Id_Aprendiz',
        'Id_Ficha',
        'score_riesgo',
        'nivel_semaforo',
        'total_pendientes',
        'estado_remision',
        'radicado',
        'motivo',
        'observaciones',
    ];

    public function aprendiz()
    {
        return $this->belongsTo(Aprendiz::class, 'Id_Aprendiz', 'Id_Aprendiz');
    }

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'Id_Ficha', 'Id_Ficha');
    }

    /**
     * Accesor para color de badge de estado
     */
    public function getEstadoBadgeAttribute(): array
    {
        return match ($this->estado_remision) {
            'PENDIENTE'      => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#fca5a5', 'border' => 'rgba(239,68,68,0.3)', 'label' => 'Pendiente de Citación'],
            'EN_SEGUIMIENTO' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fde68a', 'border' => 'rgba(245,158,11,0.3)', 'label' => 'En Acompañamiento'],
            'ATENDIDO'       => ['bg' => 'rgba(57,169,0,0.15)', 'color' => '#86efac', 'border' => 'rgba(57,169,0,0.3)', 'label' => 'Atendido por Bienestar'],
            'CERRADO'        => ['bg' => 'rgba(148,163,184,0.15)', 'color' => '#cbd5e1', 'border' => 'rgba(148,163,184,0.3)', 'label' => 'Caso Cerrado'],
            default          => ['bg' => 'rgba(255,255,255,0.05)', 'color' => '#fff', 'border' => 'rgba(255,255,255,0.1)', 'label' => $this->estado_remision],
        };
    }
}
