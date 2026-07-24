<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    use HasFactory;

    protected $table = 'programa';
    protected $primaryKey = 'Id_Programa';
    protected $fillable = ['Nombre', 'Modalidad', 'Codigo', 'Version'];

    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'Id_Programa', 'Id_Programa');
    }
}
