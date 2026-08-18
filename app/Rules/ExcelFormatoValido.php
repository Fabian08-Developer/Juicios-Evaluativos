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
                    'El documento subido no cumple con el formato requerido de Sofia Plus. ' .
                    'No se encontraron las columnas/secciones obligatorias: [' . implode(', ', $faltantes) . ']. ' .
                    'Por favor asegúrate de subir el reporte oficial de juicios evaluativos descargado de Sofia Plus.'
                );
                return;
            }

            // Verificación adicional: debe haber al menos 14 filas (cabecera + datos)
            if ($sheet->getHighestRow() < 14) {
                $fail(
                    'El documento está vacío o incompleto. ' .
                    'Se requieren al menos 14 filas con encabezado institucional y registros de aprendices.'
                );
            }

            // Liberar memoria
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $fail('El documento no pudo ser leído. Verifica que sea un archivo Excel válido (.xlsx, .xls) y que no esté dañado ni protegido con contraseña.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ExcelFormatoValido: error al prevalidar — " . $e->getMessage());
            $fail('Ocurrió un problema al validar la estructura del documento: ' . $e->getMessage());
        }
    }
}
