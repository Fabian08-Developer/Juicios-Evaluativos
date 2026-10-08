<?php

namespace App\Rules;

use App\Support\FiltroPrimerasFilas;
use App\Support\ReporteSofiaPlus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Valida que el archivo sea el "Reporte de Juicios de Evaluación" de Sofia Plus
 * ANTES de importarlo: debe traer la fila de encabezados con las columnas
 * obligatorias (Número de Documento, Nombre, Competencia, Resultado de
 * Aprendizaje, Juicio de Evaluación) y al menos una fila de datos.
 *
 * Las columnas se buscan por su texto, no por posición, y solo se leen las
 * primeras filas del archivo (no se carga el libro completo).
 */
class ExcelFormatoValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('El archivo proporcionado no es válido.');
            return;
        }

        try {
            $ruta   = $value->path();
            $reader = IOFactory::createReaderForFile($ruta);
            $reader->setReadDataOnly(true);

            $info       = $reader->listWorksheetInfo($ruta);
            $totalFilas = (int) ($info[0]['totalRows'] ?? 0);

            $reader->setReadFilter(new FiltroPrimerasFilas(ReporteSofiaPlus::FILAS_CABECERA));
            $hoja  = $reader->load($ruta)->getActiveSheet();
            $filas = $hoja->toArray(null, true, false, false);

            $problemas = ReporteSofiaPlus::problemasDeCabecera($filas);
            if ($problemas) {
                $fail(
                    'El documento no cumple con el formato del reporte de Sofia Plus. Falta: ' . implode('; ', $problemas) . '. ' .
                    'Sube el «Reporte de Juicios de Evaluación» tal como se descarga de Sofia Plus.'
                );
                return;
            }

            // La cabecera está en las primeras filas; debe haber al menos un registro debajo.
            $reporte = ReporteSofiaPlus::desdeFilas($filas);
            if ($totalFilas <= $reporte->filaEncabezado + 1) {
                $fail('El documento está vacío: no tiene filas de aprendices debajo de los encabezados.');
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $fail('El documento no pudo ser leído. Verifica que sea un archivo Excel válido (.xlsx, .xls) y que no esté dañado ni protegido con contraseña.');
        } catch (\Throwable $e) {
            Log::warning('ExcelFormatoValido: error al prevalidar — ' . $e->getMessage());
            $fail('Ocurrió un problema al validar la estructura del documento: ' . $e->getMessage());
        }
    }
}
