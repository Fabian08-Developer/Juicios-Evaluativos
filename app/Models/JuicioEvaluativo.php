<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JuicioEvaluativo extends Model
{
    use HasFactory;

    protected $table = 'juicios_evaluativos';
    protected $primaryKey = 'Id_Juicio';
    protected $fillable = [
        'Id_Resultado',
        'Id_Aprendiz',
        'Estado',
        'Id_Funcionario',
        'registrado_por',
        'Fecha',
        'Hora'
    ];

    protected $casts = [
        'Fecha' => 'date',
        'Hora' => 'datetime'
    ];

    public function resultado()
    {
        return $this->belongsTo(Resultado::class, 'Id_Resultado', 'Id_Resultado');
    }

    public function aprendiz()
    {
        return $this->belongsTo(Aprendiz::class, 'Id_Aprendiz', 'Id_Aprendiz');
    }

    /** Funcionario que registró el juicio en Sofia Plus (viene del Excel). */
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'Id_Funcionario', 'Id_Funcionario');
    }

    /** Usuario del sistema que lo calificó manualmente en la matriz (si aplica). */
    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por', 'id');
    }
}
