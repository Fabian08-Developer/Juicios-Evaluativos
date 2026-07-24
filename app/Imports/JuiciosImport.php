<?php

namespace App\Imports;

use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\Programa;
use App\Models\Competencia;
use App\Models\Resultado;
use App\Models\JuicioEvaluativo;
use App\Models\Funcionario;
use App\Services\ImportadorJuiciosService;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\Log;

/**
 * JuiciosImport — Clase de importación Maatwebsite Excel.
 *
 * Puede ser utilizada de dos maneras:
 * 1. Vía Excel::toArray(new JuiciosImport(), $archivo) -> para preprocesar y escanear cabecera en lote con ImportadorJuiciosService.
 * 2. Vía Excel::import(new JuiciosImport($fichaId), $archivo) -> procesa fila por fila delegando al servicio experto.
 */
class JuiciosImport implements OnEachRow, WithEvents
{
    protected $fichaId;
    protected $nombrePrograma;
    protected $funcionarioDefaultId;
    protected $fichaObj = null;

    // Cachés en memoria para procesamiento fila por fila directo
    protected $cacheCompetencias = [];
    protected $cacheResultados   = [];
    protected $cacheFuncionarios = [];

    public function __construct($fichaIdManual = null)
    {
        $this->fichaId = $fichaIdManual;
        $this->funcionarioDefaultId = Funcionario::first()->Id_Funcionario ?? 1;
    }

    /**
     * Paso 1: Capturar Identificadores antes de procesar las filas
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Escaneamos las primeras 15 filas buscando la Ficha y el Programa
                for ($r = 1; $r <= 15; $r++) {
                    $cellA = trim((string)$sheet->getCell('A' . $r)->getValue());
                    $cellB = trim((string)$sheet->getCell('B' . $r)->getValue());

                    // Buscar la Ficha de Caracterización si no se pasó manualmente
                    if (!$this->fichaId && stripos($cellA, 'Ficha') !== false) {
                        preg_match('/(\d{7,9})/', $cellA . $cellB, $matches);
                        if (isset($matches[1])) {
                            $this->fichaId = (int)$matches[1];
                        }
                    }

                    // Buscar la Denominación (Programa)
                    if (stripos($cellA, 'Denominaci') !== false) {
                        $this->nombrePrograma = !empty($cellB) ? $cellB : trim(str_ireplace(['Denominación:', 'Denominacion:', ':'], '', $cellA));
                    }
                }

                // Asegurar que exista la estructura base en la DB
                if ($this->fichaId) {
                    $this->asegurarEstructuraBase();
                }
            },
        ];
    }

    /**
     * Paso 2: Crear el Programa y la Ficha si no existen
     */
    protected function asegurarEstructuraBase()
    {
        $programa = Programa::firstOrCreate(
            ['Nombre' => $this->nombrePrograma ?: 'PROGRAMA SOFIA PLUS'],
            ['Codigo' => 'S-PLUS', 'Modalidad' => 'PRESENCIAL', 'Version' => '1']
        );

        $resBase  = Resultado::firstOrCreate(['Codigo' => 'RAP-BASE']);
        $compBase = Competencia::firstOrCreate(['Codigo' => 'COMP-GEN'], ['Id_Resultado' => $resBase->Id_Resultado]);

        $this->fichaObj = Ficha::firstOrCreate(
            ['Id_Ficha' => $this->fichaId],
            [
                'Id_Programa'    => $programa->Id_Programa,
                'Id_Competencia' => $compBase->Id_Competencia,
                'Jornada'        => 'DIURNA'
            ]
        );
    }

    /**
     * Paso 3: Procesar cada fila de la tabla cuando se usa Excel::import() directo
     */
    public function onRow(Row $row)
    {
        $rowIndex = $row->getRowIndex();
        
        // Saltamos las cabeceras (Los datos reales empiezan en la 14)
        if ($rowIndex < 14) return;

        // Aseguramos tener el objeto de la ficha
        if (!$this->fichaObj && $this->fichaId) {
            $this->fichaObj = Ficha::find($this->fichaId);
        }

        if (!$this->fichaObj) return;

        $data = $row->toArray();

        try {
            // Delegar al servicio experto (DRY & Tolerancia a fallos)
            app(ImportadorJuiciosService::class)->procesarFila(
                $data,
                $rowIndex,
                $this->fichaObj,
                $this->cacheCompetencias,
                $this->cacheResultados,
                $this->cacheFuncionarios
            );
        } catch (\Exception $e) {
            Log::warning("[JuiciosImport] Fila {$rowIndex} omitida: " . $e->getMessage());
        }
    }
}