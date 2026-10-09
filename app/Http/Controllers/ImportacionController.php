<?php

namespace App\Http\Controllers;

use App\Models\Ficha;
use App\Models\Importacion;
use App\Models\ImportacionCambio;

class ImportacionController extends Controller
{
    /**
     * Muestra la línea de tiempo y registro de trazabilidad de importaciones.
     */
    public function index(Request $request)
    {
<<<<<<< Updated upstream
        $importaciones             = Importacion::with('usuario')->latest()->orderByDesc('id')->paginate(20);
        $totalAprendicesProcesados = Importacion::sum('aprendices_procesados');
        $ultimaImportacion         = Importacion::latest()->orderByDesc('id')->first();

        // Solo se enlaza la línea de tiempo de fichas que aún existen.
        $fichasExistentes = Ficha::whereIn('Id_Ficha', $importaciones->pluck('id_ficha')->filter()->unique()->map(fn ($f) => (int) $f))
            ->pluck('Id_Ficha')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
=======
        $fichaFiltro = $request->get('ficha');

        $query = Importacion::with('ficha.programa')->latest();

        if ($fichaFiltro) {
            $query->where('id_ficha', $fichaFiltro);
        }

        $importaciones = $query->paginate(15)->withQueryString();

        // Fichas disponibles para el filtro
        $fichas = Ficha::with('programa')->orderBy('Id_Ficha')->get();

        // Métricas de auditoría
        $totalImportaciones = Importacion::when($fichaFiltro, fn($q) => $q->where('id_ficha', $fichaFiltro))->count();
        $totalAprendicesProcesados = Importacion::when($fichaFiltro, fn($q) => $q->where('id_ficha', $fichaFiltro))->sum('aprendices_procesados');
        $totalExitosas = Importacion::when($fichaFiltro, fn($q) => $q->where('id_ficha', $fichaFiltro))->where('estado', 'exitoso')->count();
        $tasaExito = $totalImportaciones > 0 ? round(($totalExitosas / $totalImportaciones) * 100, 1) : 100;
        $ultimaImportacion = Importacion::when($fichaFiltro, fn($q) => $q->where('id_ficha', $fichaFiltro))->latest()->first();

        // Lista de importaciones para el selector del Comparador de Hitos
        $importacionesComparador = Importacion::when($fichaFiltro, fn($q) => $q->where('id_ficha', $fichaFiltro))
            ->where('estado', '!=', 'error')
            ->latest()
            ->get();
>>>>>>> Stashed changes

        return view('importaciones.index', compact(
            'importaciones',
            'fichas',
            'fichaFiltro',
            'totalImportaciones',
            'totalAprendicesProcesados',
<<<<<<< Updated upstream
            'ultimaImportacion',
            'fichasExistentes'
=======
            'tasaExito',
            'ultimaImportacion',
            'importacionesComparador'
        ));
    }

    /**
     * Retorna los datos de una importación en formato JSON para el modal de auditoría.
     */
    public function showJson($id)
    {
        $importacion = Importacion::with('ficha.programa')->findOrFail($id);

        return response()->json([
            'id'                     => $importacion->id,
            'nombre_archivo'         => $importacion->nombre_archivo,
            'id_ficha'               => $importacion->id_ficha ?? 'Sin ficha específica',
            'programa'               => $importacion->ficha->programa->Nombre ?? 'No asignado',
            'estado'                 => $importacion->estado,
            'duracion_segundos'      => $importacion->duracion_segundos,
            'aprendices_procesados'  => $importacion->aprendices_procesados,
            'velocidad'              => $importacion->velocidad,
            'nuevos_aprobados'       => $importacion->nuevos_aprobados,
            'regresiones_protegidas' => $importacion->regresiones_protegidas,
            'detalle'                => $importacion->detalle ?: 'Sin detalles adicionales registrados.',
            'fecha_formateada'       => $importacion->created_at->format('d/m/Y H:i:s'),
            'hace_tiempo'            => $importacion->created_at->diffForHumans(),
        ]);
    }

    /**
     * Compara el avance académico entre dos hitos de importación de la misma ficha.
     */
    public function comparar(Request $request)
    {
        $idA = $request->get('id_inicial');
        $idB = $request->get('id_final');

        if (!$idA || !$idB || $idA == $idB) {
            return redirect()->route('importaciones.index')
                ->with('error', 'Debes seleccionar dos importaciones distintas para generar la comparativa.');
        }

        $impA = Importacion::with('ficha.programa')->findOrFail($idA);
        $impB = Importacion::with('ficha.programa')->findOrFail($idB);

        // Asegurar que Hito A sea cronológicamente el anterior y Hito B el posterior
        if ($impA->created_at > $impB->created_at) {
            $temporal = $impA;
            $impA = $impB;
            $impB = $temporal;
        }

        // Análisis del diferencial
        $tiempoTranscurrido = $impA->created_at->diff($impB->created_at);
        $diasTranscurridos  = $impA->created_at->diffInDays($impB->created_at);

        $deltaAprendices    = $impB->aprendices_procesados - $impA->aprendices_procesados;
        $deltaAprobados     = $impB->nuevos_aprobados - $impA->nuevos_aprobados;
        $deltaVelocidad     = round($impB->velocidad - $impA->velocidad, 1);

        return view('importaciones.comparar', compact(
            'impA',
            'impB',
            'tiempoTranscurrido',
            'diasTranscurridos',
            'deltaAprendices',
            'deltaAprobados',
            'deltaVelocidad'
>>>>>>> Stashed changes
        ));
    }

    /**
     * «Qué cambió» en una importación frente a la carga anterior de la misma ficha.
     */
    public function show(Importacion $importacion)
    {
        $importacion->load('usuario');

        $cambios = $importacion->cambios()
            ->with(['aprendiz', 'resultado.competencia'])
            ->get()
            ->groupBy('tipo');

        $aprobados = $cambios->get(ImportacionCambio::JUICIO_APROBADO, collect());

        // Nuevos aprobados por aprendiz (quién avanzó y en qué RAP).
        $aprobadosPorAprendiz = $aprobados
            ->groupBy('Id_Aprendiz')
            ->map(fn ($grupo) => ['aprendiz' => $grupo->first()->aprendiz, 'cambios' => $grupo])
            ->sortByDesc(fn ($fila) => $fila['cambios']->count())
            ->values();

        // Nuevos aprobados por RAP (qué se registró en Sofia en este periodo).
        $aprobadosPorRap = $aprobados
            ->groupBy('Id_Resultado')
            ->map(fn ($grupo) => ['resultado' => $grupo->first()->resultado, 'aprendices' => $grupo->count()])
            ->sortByDesc('aprendices')
            ->values();

        return view('importaciones.show', [
            'importacion'          => $importacion,
            'anterior'             => $importacion->anteriorDeLaFicha(),
            'cambios'              => $cambios,
            'aprobadosPorAprendiz' => $aprobadosPorAprendiz,
            'aprobadosPorRap'      => $aprobadosPorRap,
        ]);
    }

    /**
     * Línea de tiempo de una ficha: una foto (resumen) por cada carga.
     */
    public function ficha($id)
    {
        $ficha = Ficha::with('programa')->findOrFail($id);

        $cargas = Importacion::with('usuario')
            ->where('id_ficha', (string) $ficha->Id_Ficha)
            ->whereNotNull('resumen')
            ->orderBy('id')
            ->get();

        // Si hay dos cargas el mismo día, se agrega la hora para distinguirlas en el eje.
        $dias    = $cargas->map(fn ($c) => $c->created_at->format('d/m/Y'));
        $formato = $dias->unique()->count() === $dias->count() ? 'd/m/Y' : 'd/m/Y H:i';

        $serie = [
            'etiquetas'  => $cargas->map(fn ($c) => $c->created_at->format($formato))->all(),
            'aprobados'  => $cargas->map(fn ($c) => $c->resumen['aprobados_en_formacion'] ?? 0)->all(),
            'pendientes' => $cargas->map(fn ($c) => $c->resumen['pendientes_en_formacion'] ?? 0)->all(),
        ];

        return view('fichas.historial', compact('ficha', 'cargas', 'serie'));
    }
}
