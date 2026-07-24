<?php

namespace App\Http\Requests;

use App\Rules\ExcelFormatoValido;
use Illuminate\Foundation\Http\FormRequest;

/**
 * MEJORA TÉCNICA #4 — Form Request (Single Responsibility Principle)
 *
 * Antes: La validación vivía inline en AprendizController::import().
 *        Si necesitas la misma validación en otro lugar, tienes que duplicarla.
 *
 * Ahora: La validación es una clase independiente, testeable y reutilizable.
 *        Laravel la resuelve automáticamente vía type-hint en el controlador.
 *
 * Uso en el controlador:
 *   public function import(ImportarExcelRequest $request) { ... }
 *   // $request ya está validado cuando llega al método — sin código extra.
 */
class ImportarExcelRequest extends FormRequest
{
    /**
     * Cualquier usuario puede hacer esta request (sin autenticación).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación — incluyendo la regla personalizada de contenido Excel.
     */
    public function rules(): array
    {
        return [
            'Id_Ficha' => [
                'nullable',
                'integer',
                'exists:ficha,Id_Ficha', // Verifica que la ficha exista en BD
            ],
            'archivo_excel' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // 10 MB máximo
                new ExcelFormatoValido(), // ← Regla #5: valida el contenido del Excel
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'archivo_excel.required' => 'Debes seleccionar un archivo para importar.',
            'archivo_excel.file'     => 'El campo debe ser un archivo válido.',
            'archivo_excel.mimes'    => 'Solo se aceptan archivos en formato .xlsx, .xls o .csv.',
            'archivo_excel.max'      => 'El archivo no puede superar los 10 MB.',
            'Id_Ficha.integer'       => 'El identificador de ficha debe ser un número entero.',
            'Id_Ficha.exists'        => 'La ficha seleccionada no existe en el sistema. Verifica el número.',
        ];
    }

    /**
     * Atributos con nombre amigable para los mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'archivo_excel' => 'archivo del reporte',
            'Id_Ficha'      => 'ficha de destino',
        ];
    }
}
