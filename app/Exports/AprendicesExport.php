<?php

namespace App\Exports;

use App\Models\Aprendiz;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class AprendicesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Aprendiz::with(['ficha.programa']);

        if (!empty($this->filters['ficha'])) {
            $query->where('Id_Ficha', $this->filters['ficha']);
        }
        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('Nombre', 'like', "%$s%")
                  ->orWhere('Apellido', 'like', "%$s%")
                  ->orWhere('Documento', 'like', "%$s%");
            });
        }
        if (!empty($this->filters['estado'])) {
            $query->where('Estado', $this->filters['estado']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'TIPO DOC.',
            'DOCUMENTO',
            'NOMBRES',
            'APELLIDOS',
            'ESTADO',
            'FICHA',
            'PROGRAMA DE FORMACIÓN',
        ];
    }

    public function map($aprendiz): array
    {
        return [
            $aprendiz->Tipo_Documento,
            $aprendiz->Documento,
            $aprendiz->Nombre,
            $aprendiz->Apellido,
            $aprendiz->Estado,
            $aprendiz->Id_Ficha,
            $aprendiz->ficha->programa->Nombre ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF39A900'],
                ],
            ],
        ];
    }
}
