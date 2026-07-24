<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    use HasFactory;

    protected $table = 'funcionario';
    protected $primaryKey = 'Id_Funcionario';
    protected $fillable = ['Tipo_Documento', 'Documento', 'Nombre', 'Apellido'];

    public function juicios()
    {
        return $this->hasMany(JuicioEvaluativo::class, 'Id_Funcionario', 'Id_Funcionario');
    }
}
