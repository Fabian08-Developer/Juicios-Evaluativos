<?php

namespace App\Http\Controllers;

use App\Exports\AprendicesExport;
use App\Http\Requests\ImportarExcelRequest;
use App\Imports\ReporteSofiaImport;
use App\Services\ImportadorJuiciosService;
use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\Importacion;
use App\Models\ImportacionCambio;
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
            // scopeBuscar usa ILIKE en PostgreSQL: "garcia" encuentra "GARCIA".
            $query->buscar($request->search);
        }
        if ($request->filled('estado')) {
            $query->where('Estado', $request->estado);
        }

        $statsQuery       = clone $query;
        $totalAprendices  = $statsQuery->count();
        $enFormacion      = (clone $statsQuery)->where('Estado', 'EN FORMACION')->count();
        $retiroVoluntario = (clone $statsQuery)->where('Estado', 'RETIRO VOLUNTARIO')->count();
        $traslado         = (clone $statsQuery)->where('Estado', 'TRASLADADO')->count();

        // ── Ordenamiento Dinámico (Alfabético / Documento / Fecha) ─────────
        $orden = $request->get('orden', 'nombre_asc');
        switch ($orden) {
            case 'nombre_asc':
                $query->orderBy('Nombre', 'asc')->orderBy('Apellido', 'asc');
                break;
            case 'nombre_desc':
                $query->orderBy('Nombre', 'desc')->orderBy('Apellido', 'desc');
                break;
            case 'apellido_asc':
                $query->orderBy('Apellido', 'asc')->orderBy('Nombre', 'asc');
                break;
            case 'apellido_desc':
                $query->orderBy('Apellido', 'desc')->orderBy('Nombre', 'desc');
                break;
            case 'documento_asc':
                $query->orderByRaw('CAST("Documento" AS BIGINT) ASC');
                break;
            case 'documento_desc':
                $query->orderByRaw('CAST("Documento" AS BIGINT) DESC');
                break;
            case 'estado_asc':
                $query->orderBy('Estado', 'asc');
                break;
            case 'recientes':
                $query->latest();
                break;
            default:
                $query->orderBy('Nombre', 'asc')->orderBy('Apellido', 'asc');
                break;
        }

        $aprendices = $query->paginate(15)->withQueryString();
        $fichas     = Ficha::with('programa')->get();

        return view('aprendices.index', compact(
            'aprendices', 'fichas', 'totalAprendices',
            'enFormacion', 'retiroVoluntario', 'traslado', 'orden'
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
        // $request ya está validado (incluida la estructura del Excel).
        $inicio        = now();
        $nombreArchivo = $request->file('archivo_excel')->getClientOriginalName();

        $importacion = Importacion::create([
            'nombre_archivo'    => $nombreArchivo,
            'id_ficha'          => $request->Id_Ficha,
            'user_id'           => $request->user()->id,
            'duracion_segundos' => 0,
            'estado'            => 'procesando',
        ]);

        try {
            $filas = Excel::toArray(new ReporteSofiaImport(), $request->file('archivo_excel'))[0] ?? [];

            $resultado = app(ImportadorJuiciosService::class)
                ->procesarArchivoExcel($filas, $request->Id_Ficha, $importacion);

            if ($resultado['procesados'] === 0 && empty($resultado['errores'])) {
                $importacion->update(['estado' => 'error', 'detalle' => 'El archivo no contiene registros de aprendices para procesar.']);

                return redirect()->back()
                    ->with('error', 'El documento no contiene registros válidos de aprendices para procesar. Verifica que sea el reporte de juicios evaluativos de la ficha.');
            }

            // Tras importar se aterriza en «qué cambió» frente a la carga anterior.
            $destino = redirect()->route('importaciones.show', $importacion);

            if (! empty($resultado['errores'])) {
                return $destino
                    ->with('warning', $resultado['message'])
                    ->with('warning_errores', $resultado['errores']);
            }

            // Advertencias sin errores de fila (p. ej. reporte más antiguo): se muestran como aviso.
            return $destino->with($resultado['advertencias'] ? 'warning' : 'success', $resultado['message']);

        } catch (\Throwable $e) {
            Log::error('Error fatal en importación: ' . $e->getMessage());
            $importacion->update([
                'estado'            => 'error',
                'duracion_segundos' => max(0, (int) round($inicio->diffInSeconds(now()))),
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

        // Línea de tiempo del aprendiz: sus cambios en cada importación (más reciente primero).
        $historial = ImportacionCambio::with(['importacion', 'resultado'])
            ->where('Id_Aprendiz', $aprendiz->Id_Aprendiz)
            ->get()
            ->groupBy('importacion_id')
            ->sortKeysDesc();

        return view('aprendices.show', compact('aprendiz', 'avancePorCompetencia', 'historial'));
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
        $filters = $request->only(['ficha', 'search', 'estado', 'orden']);
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
            ->buscar($q)
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
