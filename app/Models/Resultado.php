<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resultado extends Model
{
    use HasFactory;

    protected $table = 'resultados';
    protected $primaryKey = 'Id_Resultado';
    protected $fillable = ['Codigo', 'Nombre', 'Id_Competencia'];

    public function competencia()
    {
        return $this->belongsTo(Competencia::class, 'Id_Competencia', 'Id_Competencia');
    }

    public function juicios()
    {
        return $this->hasMany(JuicioEvaluativo::class, 'Id_Resultado', 'Id_Resultado');
    }

}
