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

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'Id_Funcionario', 'Id_Funcionario');
    }
}
