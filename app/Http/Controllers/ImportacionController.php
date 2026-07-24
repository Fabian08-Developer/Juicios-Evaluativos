<?php

namespace App\Http\Controllers;

use App\Models\Importacion;
use Illuminate\Http\Request;

class ImportacionController extends Controller
{
    public function index()
    {
        $importaciones             = Importacion::latest()->paginate(20);
        $totalAprendicesProcesados = Importacion::sum('aprendices_procesados');
        $ultimaImportacion         = Importacion::latest()->first();

        return view('importaciones.index', compact(
            'importaciones',
            'totalAprendicesProcesados',
            'ultimaImportacion'
        ));
    }
}
