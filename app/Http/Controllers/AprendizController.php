<?php

namespace App\Http\Controllers;

use App\Exports\AprendicesExport;
use App\Exceptions\ImportacionRequiereDecision;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AprendizController extends Controller
{
    /** Reportes que esperan la decisión del usuario (disco local, privado). */
    private const CARPETA_PENDIENTES = 'importaciones-pendientes';
    /** Clave de sesión: token => archivo, ficha elegida y análisis para decidir. */
    private const SESION_PENDIENTES = 'importacion_pendiente';

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
     * Sube el reporte de Sofia Plus. La validación (incluida la estructura del
     * Excel) ya corrió en ImportarExcelRequest.
     *
     * Si el reporte desharía aprobaciones o trae aprendices que hoy están en otra
     * ficha, no se aplica nada: el archivo se guarda temporalmente y el usuario
     * decide cómo aplicarlo (ver decisionImportacion / confirmarImportacion).
     */
    public function import(ImportarExcelRequest $request)
    {
        $archivo = $request->file('archivo_excel');
        $nombre  = $archivo->getClientOriginalName();

        try {
            return $this->importarYRedirigir($archivo, null, $nombre, $request->Id_Ficha, ImportadorJuiciosService::CONSULTAR);
        } catch (ImportacionRequiereDecision $d) {
            $this->limpiarPendientesVencidas();

            // La extensión decide qué lector usa PhpSpreadsheet al releer el archivo.
            $token = (string) Str::uuid();
            $ext   = strtolower($archivo->getClientOriginalExtension());
            $ext   = in_array($ext, ['xls', 'xlsx', 'csv'], true) ? $ext : 'xls';
            $ruta  = $archivo->storeAs(self::CARPETA_PENDIENTES, "{$token}.{$ext}", 'local');

            // De paso se olvidan las decisiones abandonadas cuyo archivo ya se borró.
            $vigentes = array_filter(session(self::SESION_PENDIENTES, []), fn ($p) => Storage::disk('local')->exists($p['ruta']));
            $vigentes[$token] = [
                'ruta'         => $ruta,
                'nombre'       => $nombre,
                'ficha_manual' => $request->Id_Ficha,
                'analisis'     => $d->analisis,
            ];
            session([self::SESION_PENDIENTES => $vigentes]);

            return redirect()->route('aprendices.import.decision', $token);
        }
    }

    /** Pantalla de decisión de una importación pendiente. */
    public function decisionImportacion(string $token)
    {
        $pendiente = $this->pendiente($token);
        if (! $pendiente) {
            return redirect()->route('aprendices.upload')
                ->with('error', 'La importación pendiente ya no existe (se aplicó, se canceló o expiró). Vuelve a subir el archivo.');
        }

        return view('aprendices.decision-importacion', [
            'token'    => $token,
            'nombre'   => $pendiente['nombre'],
            'analisis' => $pendiente['analisis'],
        ]);
    }

    /** Aplica (o descarta) una importación pendiente con la política elegida. */
    public function confirmarImportacion(Request $request, string $token)
    {
        $acciones = [
            'preservar' => ImportadorJuiciosService::PRESERVAR_APROBADOS,
            'trasladar' => ImportadorJuiciosService::PERMITIR_TRASLADO,
            'forzar'    => ImportadorJuiciosService::FORZAR_SOBRESCRITURA,
            'cancelar'  => null,
        ];
        $request->validate(['accion' => ['required', 'in:' . implode(',', array_keys($acciones))]]);

        $pendiente = $this->pendiente($token);
        if (! $pendiente) {
            return redirect()->route('aprendices.upload')
                ->with('error', 'La importación pendiente ya no existe (se aplicó, se canceló o expiró). Vuelve a subir el archivo.');
        }

        // Se consume una sola vez, pase lo que pase (también si la importación falla).
        // Renombrar el archivo es atómico: si llega un segundo envío (doble clic),
        // ya no lo encuentra y no se aplica dos veces.
        session()->forget(self::claveSesion($token));
        $disco   = Storage::disk('local');
        $enCurso = preg_replace('/(\.\w+)$/', '.aplicando$1', $pendiente['ruta']);

        if (! $disco->move($pendiente['ruta'], $enCurso)) {
            return redirect()->route('importaciones.index')
                ->with('warning', 'Esta importación ya se está aplicando o ya se aplicó.');
        }

        try {
            if ($request->accion === 'cancelar') {
                return redirect()->route('aprendices.upload')
                    ->with('success', "Importación de «{$pendiente['nombre']}» cancelada: no se modificó ningún dato.");
            }

            return $this->importarYRedirigir(
                $enCurso, 'local', $pendiente['nombre'], $pendiente['ficha_manual'], $acciones[$request->accion]
            );
        } finally {
            $disco->delete($enCurso);
        }
    }

    /**
     * Importa y redirige al detalle de «qué cambió».
     *
     * @param  \Illuminate\Http\UploadedFile|string  $archivo  Archivo subido o ruta dentro de $disco
     * @throws ImportacionRequiereDecision (solo con la política CONSULTAR)
     */
    private function importarYRedirigir($archivo, ?string $disco, string $nombreArchivo, ?string $fichaManual, string $politica)
    {
        $inicio = now();

        $importacion = Importacion::create([
            'nombre_archivo'    => $nombreArchivo,
            'id_ficha'          => $fichaManual,
            'user_id'           => auth()->id(),
            'duracion_segundos' => 0,
            'estado'            => 'procesando',
        ]);

        try {
            $filas = Excel::toArray(new ReporteSofiaImport(), $archivo, $disco)[0] ?? [];

            $resultado = app(ImportadorJuiciosService::class)
                ->procesarArchivoExcel($filas, $fichaManual, $importacion, $politica);

            if ($resultado['procesados'] === 0 && empty($resultado['errores'])) {
                $importacion->update(['estado' => 'error', 'detalle' => 'El archivo no contiene registros de aprendices para procesar.']);

                return redirect()->route('aprendices.upload')
                    ->with('error', 'El documento no contiene registros válidos de aprendices para procesar. Verifica que sea el reporte de juicios evaluativos de la ficha.');
            }

            // Tras importar se aterriza en «qué cambió» frente a la carga anterior.
            $destino = redirect()->route('importaciones.show', $importacion);

            if (! empty($resultado['errores'])) {
                return $destino
                    ->with('warning', $resultado['message'])
                    ->with('warning_errores', $resultado['errores']);
            }

            return $destino->with($resultado['advertencias'] ? 'warning' : 'success', $resultado['message']);

        } catch (ImportacionRequiereDecision $d) {
            // Nada se aplicó: el registro de esta carga se descarta hasta que el usuario decida.
            $importacion->delete();
            throw $d;
        } catch (\Throwable $e) {
            Log::error('Error fatal en importación: ' . $e->getMessage());
            $importacion->update([
                'estado'            => 'error',
                'duracion_segundos' => max(0, (int) round($inicio->diffInSeconds(now()))),
                'detalle'           => $e->getMessage(),
            ]);

            return redirect()->route('aprendices.upload')->with('error', 'Error al procesar el documento: ' . $e->getMessage());
        }
    }

    private static function claveSesion(string $token): string
    {
        return self::SESION_PENDIENTES . ".{$token}";
    }

    /** @return array{ruta:string,nombre:string,ficha_manual:?string,analisis:array}|null */
    private function pendiente(string $token): ?array
    {
        $pendiente = Str::isUuid($token) ? session(self::claveSesion($token)) : null;

        return $pendiente && Storage::disk('local')->exists($pendiente['ruta']) ? $pendiente : null;
    }

    /** Archivos de decisiones nunca tomadas (sesión cerrada, pestaña abandonada): se borran al día. */
    private function limpiarPendientesVencidas(): void
    {
        $disco  = Storage::disk('local');
        $limite = now()->subDay()->getTimestamp();

        foreach ($disco->files(self::CARPETA_PENDIENTES) as $f) {
            if ($disco->lastModified($f) < $limite) {
                $disco->delete($f);
            }
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
