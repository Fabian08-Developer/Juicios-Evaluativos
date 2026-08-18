<?php

namespace App\Http\Controllers;

use App\Exports\AprendicesExport;
use App\Http\Requests\ImportarExcelRequest;
use App\Imports\JuiciosImport;
use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\Importacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AprendizController extends Controller
{
    public function index(Request $request)
    {
        $query = Aprendiz::with(['ficha.programa']);

        if (!$request->has('ficha')) {
            $ultimaFicha = Ficha::orderBy('created_at', 'desc')->first();
            if ($ultimaFicha) {
                $request->merge(['ficha' => $ultimaFicha->Id_Ficha]);
            }
        }

        if ($request->filled('ficha')) {
            $query->where('Id_Ficha', $request->ficha);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Nombre', 'like', "%{$search}%")
                  ->orWhere('Apellido', 'like', "%{$search}%")
                  ->orWhere('Documento', 'like', "%{$search}%");
            });
        }
        if ($request->filled('estado')) {
            $query->where('Estado', $request->estado);
        }

        $statsQuery       = clone $query;
        $totalAprendices  = $statsQuery->count();
        $enFormacion      = (clone $statsQuery)->where('Estado', 'EN FORMACION')->count();
        $retiroVoluntario = (clone $statsQuery)->where('Estado', 'RETIRO VOLUNTARIO')->count();
        $traslado         = (clone $statsQuery)->where('Estado', 'TRASLADADO')->count();

        $aprendices = $query->latest()->paginate(15)->withQueryString();
        $fichas     = Ficha::all();

        return view('aprendices.index', compact(
            'aprendices', 'fichas', 'totalAprendices',
            'enFormacion', 'retiroVoluntario', 'traslado'
        ));
    }

    public function showUploadForm()
    {
        $fichas = Ficha::with('programa')->get();
        return view('aprendices.upload', compact('fichas'));
    }

    /**
     * MEJORA TÉCNICA #4 — Usa ImportarExcelRequest en lugar de Request.
     * La validación (incluyendo ExcelFormatoValido) ya corrió antes de llegar aquí.
     *
     * MEJORA TÉCNICA #8 — Llama al servicio actualizado con tolerancia a fallos.
     * Si hay errores por fila, los muestra al usuario como advertencia, no como error fatal.
     */
    public function import(ImportarExcelRequest $request)
    {
        // En este punto $request YA está validado (incluyendo el contenido del Excel).
        // No necesitamos $request->validate() manual.

        $inicio        = now();
        $nombreArchivo = $request->file('archivo_excel')->getClientOriginalName();

        // Crear registro de importación pendiente
        $importacion = Importacion::create([
            'nombre_archivo'    => $nombreArchivo,
            'id_ficha'          => $request->Id_Ficha,
            'duracion_segundos' => 0,
            'estado'            => 'procesando',
        ]);

        try {
            // Usar el servicio actualizado con tolerancia a fallos
            $servicio = app(\App\Services\ImportadorJuiciosService::class);

            // Leer el Excel como array de filas
            $filas = \Maatwebsite\Excel\Facades\Excel::toArray(
                new \App\Imports\JuiciosImport(),
                $request->file('archivo_excel')
            )[0] ?? [];

            $resultado = $servicio->procesarArchivoExcel($filas, $request->Id_Ficha, $importacion);

            // Si no se procesó ningún registro válido
            if ($resultado['procesados'] === 0 && empty($resultado['errores'])) {
                return redirect()->back()
                    ->with('error', 'El documento no contiene registros válidos de aprendices para procesar. Verifica que el archivo corresponda a la ficha seleccionada.');
            }

            // Construir mensaje para el usuario
            $mensaje = $resultado['message'];
            if (!empty($resultado['errores'])) {
                // Hubo errores por fila — redirigir con advertencia y detalle
                return redirect()->route('dashboard')
                    ->with('warning', $mensaje)
                    ->with('warning_errores', $resultado['errores']);
            }

            return redirect()->route('dashboard')->with('success', $mensaje);

        } catch (\Exception $e) {
            Log::error('Error fatal en importación: ' . $e->getMessage());
            $importacion->update([
                'estado'            => 'error',
                'duracion_segundos' => now()->diffInSeconds($inicio),
                'detalle'           => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Error al procesar el documento: ' . $e->getMessage());
        }
    }


    public function show($id)
    {
        $aprendiz = Aprendiz::with([
            'ficha.programa',
            'juicios.resultado.competencia',
        ])->findOrFail($id);

        $avancePorCompetencia = $aprendiz->juicios->groupBy(function ($juicio) {
            $comp   = $juicio->resultado->competencia;
            $codigo = $comp->Codigo ?? 'S-C';
            $nombre = $comp->Nombre  ?? 'Sin Competencia Asignada';
            return "{$codigo}|||{$nombre}";
        })->map(function ($juicios, $key) {
            $partes    = explode('|||', $key);
            $total     = $juicios->count();
            $aprobados = $juicios->where('Estado', 1)->count();
            return [
                'codigo'     => $partes[0],
                'nombre'     => $partes[1],
                'total'      => $total,
                'aprobados'  => $aprobados,
                'porcentaje' => $total > 0 ? ($aprobados / $total) * 100 : 0,
                'juicios'    => $juicios,
            ];
        });

        return view('aprendices.show', compact('aprendiz', 'avancePorCompetencia'));
    }

    /**
     * 📄 Exportar PDF del expediente del aprendiz.
     */
    public function exportarPdf($id)
    {
        $aprendiz = Aprendiz::with([
            'ficha.programa',
            'juicios.resultado.competencia',
        ])->findOrFail($id);

        $avancePorCompetencia = $aprendiz->juicios->groupBy(function ($juicio) {
            $comp   = $juicio->resultado->competencia;
            $codigo = $comp->Codigo ?? 'S-C';
            $nombre = $comp->Nombre  ?? 'Sin Competencia Asignada';
            return "{$codigo}|||{$nombre}";
        })->map(function ($juicios, $key) {
            $partes    = explode('|||', $key);
            $total     = $juicios->count();
            $aprobados = $juicios->where('Estado', 1)->count();
            return [
                'codigo'     => $partes[0],
                'nombre'     => $partes[1],
                'total'      => $total,
                'aprobados'  => $aprobados,
                'porcentaje' => $total > 0 ? ($aprobados / $total) * 100 : 0,
                'juicios'    => $juicios,
            ];
        });

        $pdf = Pdf::loadView('aprendices.reporte-pdf', compact('aprendiz', 'avancePorCompetencia'))
                  ->setPaper('a4', 'portrait');

        $nombreArchivo = "Expediente_{$aprendiz->Documento}_{$aprendiz->Apellido}.pdf";
        return $pdf->download($nombreArchivo);
    }

    /**
     * 📊 Exportar listado actual de aprendices a Excel.
     */
    public function exportarExcel(Request $request)
    {
        $filters = $request->only(['ficha', 'search', 'estado']);
        $fecha   = now()->format('Y-m-d');
        return Excel::download(new AprendicesExport($filters), "Aprendices_SENA_{$fecha}.xlsx");
    }

    /**
     * 🔍 Búsqueda global (JSON) para autocompletado.
     */
    public function buscarJson(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $resultados = Aprendiz::with('ficha.programa')
            ->where(function ($query) use ($q) {
                $query->where('Nombre', 'like', "%{$q}%")
                      ->orWhere('Apellido', 'like', "%{$q}%")
                      ->orWhere('Documento', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get()
            ->map(fn($a) => [
                'id'       => $a->Id_Aprendiz,
                'nombre'   => "{$a->Nombre} {$a->Apellido}",
                'doc'      => $a->Documento,
                'ficha'    => $a->Id_Ficha,
                'programa' => $a->ficha->programa->Nombre ?? 'N/A',
                'estado'   => $a->Estado,
                'url'      => route('aprendices.show', $a->Id_Aprendiz),
                'iniciales'=> strtoupper(substr($a->Nombre, 0, 1) . substr($a->Apellido, 0, 1)),
            ]);

        return response()->json($resultados);
    }
}
