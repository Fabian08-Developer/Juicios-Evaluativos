<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/** Filtro de lectura: solo carga las primeras N filas de la hoja (validación rápida y liviana). */
final class FiltroPrimerasFilas implements IReadFilter
{
    public function __construct(private readonly int $maxFilas) {}

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return $row <= $this->maxFilas;
    }
}
