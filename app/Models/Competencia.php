<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competencia extends Model
{
    use HasFactory;

    protected $table = 'competencia';
    protected $primaryKey = 'Id_Competencia';
    protected $fillable = ['Codigo', 'Nombre'];

    public function resultados()
    {
        return $this->hasMany(Resultado::class, 'Id_Competencia', 'Id_Competencia');
    }

    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'Id_Competencia', 'Id_Competencia');
    }
}
