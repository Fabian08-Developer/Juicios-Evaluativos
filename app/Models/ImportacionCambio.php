<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un cambio detectado al importar un reporte, frente a la carga anterior de la ficha.
 */
class ImportacionCambio extends Model
{
    public const JUICIO_APROBADO  = 'juicio_aprobado';   // POR EVALUAR → APROBADO
    public const JUICIO_REVERTIDO = 'juicio_revertido';  // APROBADO → POR EVALUAR
    public const JUICIO_NUEVO     = 'juicio_nuevo';      // RAP que el aprendiz no tenía antes
    public const APRENDIZ_NUEVO   = 'aprendiz_nuevo';
    public const APRENDIZ_ESTADO  = 'aprendiz_estado';   // p. ej. EN FORMACION → RETIRO VOLUNTARIO
    public const APRENDIZ_MOVIDO  = 'aprendiz_movido';   // venía de otra ficha
    public const APRENDIZ_AUSENTE = 'aprendiz_ausente';  // estaba en la ficha y no vino en el reporte
    // Solo al importar conservando aprobados / sin trasladar (decisión del usuario):
    public const JUICIO_PROTEGIDO        = 'juicio_protegido';         // el reporte lo traía POR EVALUAR; se conservó APROBADO
    public const APRENDIZ_NO_TRASLADADO  = 'aprendiz_no_trasladado';   // es de otra ficha y se dejó en ella

    /** Etiqueta legible de cada tipo, en el orden en que se muestran. */
    public const ETIQUETAS = [
        self::JUICIO_APROBADO        => 'Nuevos aprobados',
        self::JUICIO_REVERTIDO       => 'Aprobaciones revertidas',
        self::JUICIO_PROTEGIDO       => 'Aprobados protegidos',
        self::APRENDIZ_ESTADO        => 'Cambios de estado',
        self::APRENDIZ_NUEVO         => 'Aprendices nuevos',
        self::APRENDIZ_AUSENTE       => 'Aprendices ausentes',
        self::APRENDIZ_MOVIDO        => 'Aprendices movidos de ficha',
        self::APRENDIZ_NO_TRASLADADO => 'Aprendices no trasladados',
        self::JUICIO_NUEVO           => 'RAP nuevos',
    ];

    protected $table = 'importacion_cambios';

    public $timestamps = false;

    protected $fillable = ['importacion_id', 'Id_Aprendiz', 'Id_Resultado', 'tipo', 'valor_anterior', 'valor_nuevo'];

    public function importacion()
    {
        return $this->belongsTo(Importacion::class);
    }

    public function aprendiz()
    {
        return $this->belongsTo(Aprendiz::class, 'Id_Aprendiz', 'Id_Aprendiz');
    }

    public function resultado()
    {
        return $this->belongsTo(Resultado::class, 'Id_Resultado', 'Id_Resultado');
    }
}
