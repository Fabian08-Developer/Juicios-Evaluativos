<?php

namespace App\Imports;

use App\Models\Aprendiz;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AprendizImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $fichaId;

    public function __construct($fichaId)
    {
        $this->fichaId = $fichaId;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Validar que la fila no esté vacía en el documento
        if (!isset($row['numero_documento']) || empty($row['numero_documento'])) {
            return null;
        }

        // updateOrCreate para no duplicar si el aprendiz ya existe
        return Aprendiz::updateOrCreate(
            [
                'Documento' => $row['numero_documento'],
            ],
            [
                'Tipo_Documento' => $row['tipo_documento'] ?? 'CC',
                'Nombre' => $row['nombres'],
                'Apellido' => $row['apellidos'],
                'Estado' => $row['estado'] ?? 'ACTIVO',
                'Id_Ficha' => $this->fichaId,
            ]
        );
    }

    public function batchSize(): int
    {
        return 500; // Escalabilidad: inserta de 500 en 500
    }

    public function chunkSize(): int
    {
        return 500; // Escalabilidad: lee el archivo en partes de 500 filas
    }
}
