<?php

namespace App\Http\Controllers;

use App\Services\ImportadorJuiciosService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JuiciosController extends Controller
{
    protected $importadorService;

    public function __construct(ImportadorJuiciosService $importadorService)
    {
        $this->importadorService = $importadorService;
    }

    public function importarExcel(Request $request)
    {
        // Validamos que el campo coincida con tu formulario (archivo_excel)
        $request->validate([
            'archivo_excel' => 'required|mimes:xls,xlsx,csv'
        ]);

        try {
            $archivo = $request->file('archivo_excel');
            
            // Cargamos el archivo usando PhpSpreadsheet directamente (Más control)
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $hojaActiva = $spreadsheet->getActiveSheet();
            
            // Convertimos la hoja a un array nativo
            $filasArray = $hojaActiva->toArray();

            // Capturamos la ficha seleccionada manualmente si existe
            $fichaManual = $request->get('Id_Ficha');

            // Procesamos con el Servicio Experto
            $resultado = $this->importadorService->procesarArchivoExcel($filasArray, $fichaManual);

            return back()->with('success', $resultado['message']);

        } catch (\Exception $e) {
            Log::error("Fallo en JuiciosController: " . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al procesar el archivo: ' . $e->getMessage());
        }
    }
}
