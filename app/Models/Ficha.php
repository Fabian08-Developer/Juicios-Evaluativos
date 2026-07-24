<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ficha extends Model
{
    use HasFactory;

    protected $table = 'ficha';
    protected $primaryKey = 'Id_Ficha';
    public $incrementing = false; // El SQL original no lo marcaba como identity
    protected $fillable = ['Id_Ficha', 'Jornada', 'Id_Programa', 'Id_Competencia'];

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'Id_Programa', 'Id_Programa');
    }

    public function competencia()
    {
        return $this->belongsTo(Competencia::class, 'Id_Competencia', 'Id_Competencia');
    }

    public function aprendices()
    {
        return $this->hasMany(Aprendiz::class, 'Id_Ficha', 'Id_Ficha');
    }
}
