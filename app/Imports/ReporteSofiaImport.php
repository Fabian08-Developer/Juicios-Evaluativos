<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Lector mínimo del reporte de Sofia Plus: devuelve las hojas como arreglos
 * de celdas. La interpretación vive en App\Support\ReporteSofiaPlus y la
 * escritura en base de datos en App\Services\ImportadorJuiciosService.
 *
 * Uso: Excel::toArray(new ReporteSofiaImport, $archivo)[0]
 */
class ReporteSofiaImport implements ToArray
{
    public function array(array $array): void
    {
        // Intencionalmente vacío: Excel::toArray() ya entrega los datos.
    }
}
