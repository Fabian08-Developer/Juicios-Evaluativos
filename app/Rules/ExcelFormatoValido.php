<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * MEJORA TÉCNICA #5 — Regla de Validación Personalizada
 *
 * Problema: Cualquier archivo .xlsx se acepta sin verificar su contenido.
 * Un Excel de ventas pasaría la validación y fallaría a mitad de importación.
 *
 * Solución: Leer las primeras filas del Excel y verificar palabras clave
 * del formato SENA (FICHA, DOCUMENTO, NOMBRE) antes de comenzar la importación.
 *
 * Uso en Form Request:
 *   'archivo_excel' => ['required', 'mimes:xlsx,xls', new ExcelFormatoValido()]
 */
class ExcelFormatoValido implements ValidationRule
{
    /** Máximo de filas a escanear para encontrar la cabecera */
    private const MAX_FILAS_CABECERA = 20;

    /** Palabras clave obligatorias que debe contener el reporte SENA */
    private const PALABRAS_REQUERIDAS = ['FICHA', 'DOCUMENTO', 'NOMBRE'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Verificar que sea un objeto UploadedFile
        if (!$value instanceof \Illuminate\Http\UploadedFile) {
            $fail('El archivo proporcionado no es válido.');
            return;
        }

        try {
            // Cargar solo las primeras filas (sin cargar todo el archivo en memoria)
            $reader = IOFactory::createReaderForFile($value->path());
            $reader->setReadDataOnly(true);

            // Leer solo la hoja activa
            $spreadsheet = $reader->load($value->path());
            $sheet       = $spreadsheet->getActiveSheet();

            // Extraer texto de las primeras N filas para análisis
            $textoGlobal = '';
            $maxFila     = min(self::MAX_FILAS_CABECERA, $sheet->getHighestRow());

            for ($fila = 1; $fila <= $maxFila; $fila++) {
                for ($col = 'A'; $col <= 'L'; $col++) {
                    $valor = trim((string) $sheet->getCell("{$col}{$fila}")->getValue());
                    if (!empty($valor)) {
                        $textoGlobal .= ' ' . strtoupper($valor);
                    }
                }
            }

            // Verificar que existan todas las palabras clave del formato SENA
            $faltantes = [];
            foreach (self::PALABRAS_REQUERIDAS as $palabra) {
                if (!str_contains($textoGlobal, $palabra)) {
                    $faltantes[] = $palabra;
                }
            }

            if (!empty($faltantes)) {
                $fail(
                    'El archivo no tiene el formato de reporte SENA. ' .
                    'No se encontraron las siguientes secciones: ' .
                    implode(', ', $faltantes) . '. ' .
                    'Asegúrate de exportarlo correctamente desde Sofia Plus.'
                );
                return;
            }

            // Verificación adicional: debe haber al menos 14 filas (cabecera + datos)
            if ($sheet->getHighestRow() < 14) {
                $fail(
                    'El archivo parece estar vacío o incompleto. ' .
                    'Se esperan al menos 14 filas (encabezado + datos de aprendices).'
                );
            }

            // Liberar memoria
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $fail('No se puede leer el archivo Excel. Verifica que no esté corrupto o protegido con contraseña.');
        } catch (\Throwable $e) {
            // Error inesperado — dejar pasar y que el servicio de importación lo maneje
            \Illuminate\Support\Facades\Log::warning("ExcelFormatoValido: error al prevalidar — " . $e->getMessage());
        }
    }
}
