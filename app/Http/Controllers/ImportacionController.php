<?php

namespace App\Http\Controllers;

use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\Importacion;
use App\Models\ImportacionCambio;
use Illuminate\Http\Request;

class ImportacionController extends Controller
{
    public function index(Request $request)
    {
        $fichaFiltro = $request->filled('ficha') ? (string) $request->integer('ficha') : null;
        $filtrar     = fn ($q) => $q->when($fichaFiltro, fn ($q) => $q->where('id_ficha', $fichaFiltro));

        $importaciones = Importacion::with(['usuario', 'ficha.programa'])
            ->tap($filtrar)
            ->latest()->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $totalImportaciones        = Importacion::tap($filtrar)->count();
        $totalAprendicesProcesados = Importacion::tap($filtrar)->sum('aprendices_procesados');
        $conError                  = Importacion::tap($filtrar)->where('estado', 'error')->count();
        $tasaExito                 = $totalImportaciones > 0 ? round(100 * ($totalImportaciones - $conError) / $totalImportaciones, 1) : null;
        $ultimaImportacion         = Importacion::tap($filtrar)->latest()->orderByDesc('id')->first();

        // Comparador: cargas con foto (las que registraron datos), agrupadas por ficha.
        $comparables = Importacion::tap($filtrar)
            ->whereNotNull('resumen')
            ->orderByDesc('id')
            ->get(['id', 'id_ficha', 'nombre_archivo', 'created_at'])
            ->groupBy('id_ficha')
            ->filter(fn ($cargas) => $cargas->count() >= 2);

        $fichas = Ficha::with('programa')->orderBy('Id_Ficha')->get();

        // Solo se enlaza la línea de tiempo de fichas que aún existen.
        $fichasExistentes = $fichas->mapWithKeys(fn ($f) => [(string) $f->Id_Ficha => true])->all();

        return view('importaciones.index', compact(
            'importaciones',
            'fichas',
            'fichaFiltro',
            'totalImportaciones',
            'totalAprendicesProcesados',
            'tasaExito',
            'ultimaImportacion',
            'comparables',
            'fichasExistentes'
        ));
    }

    /**
     * Avance de una ficha entre dos de sus cargas: las dos fotos (resumen) y el
     * efecto neto de todos los cambios registrados entre ambas.
     */
    public function comparar(Request $request)
    {
        $a = Importacion::with(['usuario', 'ficha.programa'])->whereNotNull('resumen')->find($request->integer('id_inicial'));
        $b = Importacion::with('usuario')->whereNotNull('resumen')->find($request->integer('id_final'));

        $volver = redirect()->route('importaciones.index', array_filter(['ficha' => $request->input('ficha')]));
        if (! $a || ! $b || $a->is($b)) {
            return $volver->with('error', 'Elige dos cargas distintas para compararlas.');
        }
        if ($a->id_ficha !== $b->id_ficha) {
            return $volver->with('error', 'Solo se pueden comparar cargas de la misma ficha.');
        }
        if ($a->id > $b->id) {
            [$a, $b] = [$b, $a];
        }

        // Cargas posteriores a A hasta B (inclusive): sus cambios llevan de la foto A a la B.
        $tramo = Importacion::with('usuario')
            ->where('id_ficha', $a->id_ficha)
            ->whereNotNull('resumen')
            ->whereBetween('id', [$a->id + 1, $b->id])
            ->orderBy('id')
            ->get();

        $conteo = [];
        foreach ($tramo as $carga) {
            foreach ($carga->resumen['cambios'] ?? [] as $tipo => $n) {
                $conteo[$tipo] = ($conteo[$tipo] ?? 0) + $n;
            }
        }

        // Efecto neto por juicio: aprobado y luego revertido (o al revés) se anulan.
        $neto = ImportacionCambio::whereIn('importacion_id', $tramo->pluck('id'))
            ->whereIn('tipo', [ImportacionCambio::JUICIO_APROBADO, ImportacionCambio::JUICIO_REVERTIDO])
            ->get(['Id_Aprendiz', 'Id_Resultado', 'tipo'])
            ->groupBy(fn ($c) => $c->Id_Aprendiz . '|' . $c->Id_Resultado)
            ->map(fn ($g) => [
                'aprendiz' => $g->first()->Id_Aprendiz,
                'saldo'    => $g->sum(fn ($c) => $c->tipo === ImportacionCambio::JUICIO_APROBADO ? 1 : -1),
            ])
            ->filter(fn ($j) => $j['saldo'] !== 0);

        $aprendices = Aprendiz::whereIn('Id_Aprendiz', $neto->pluck('aprendiz')->unique())->get()->keyBy('Id_Aprendiz');
        $porAprendiz = $neto->groupBy('aprendiz')
            ->map(fn ($g, $id) => [
                'aprendiz'   => $aprendices[$id] ?? null,
                'avances'    => $g->where('saldo', '>', 0)->count(),
                'retrocesos' => $g->where('saldo', '<', 0)->count(),
            ])
            ->sortByDesc('avances')
            ->values();

        $ra = $a->resumen;
        $rb = $b->resumen;
        $delta = [
            'aprobados'  => ($rb['aprobados_en_formacion'] ?? 0) - ($ra['aprobados_en_formacion'] ?? 0),
            'pendientes' => ($rb['pendientes_en_formacion'] ?? 0) - ($ra['pendientes_en_formacion'] ?? 0),
            'aprendices' => ($rb['aprendices'] ?? 0) - ($ra['aprendices'] ?? 0),
        ];

        return view('importaciones.comparar', [
            'a'           => $a,
            'b'           => $b,
            'tramo'       => $tramo,
            'conteo'      => $conteo,
            'delta'       => $delta,
            'dias'        => (int) $a->created_at->diffInDays($b->created_at),
            'porAprendiz' => $porAprendiz,
            'fichaExiste' => Ficha::whereKey((int) $a->id_ficha)->exists(),
        ]);
    }

    /** Datos de una importación en JSON (vista rápida del historial e integraciones). */
    public function showJson(Importacion $importacion)
    {
        $importacion->load(['usuario', 'ficha.programa']);

        return response()->json([
            'id'                    => $importacion->id,
            'nombre_archivo'        => $importacion->nombre_archivo,
            'id_ficha'              => $importacion->id_ficha,
            'programa'              => $importacion->ficha?->programa?->Nombre,
            'estado'                => $importacion->estado,
            'estado_etiqueta'       => $importacion->estado_visual['label'],
            'subido_por'            => $importacion->usuario?->name,
            'aprendices_procesados' => (int) $importacion->aprendices_procesados,
            'duracion_segundos'     => (int) $importacion->duracion_segundos,
            'fecha'                 => $importacion->created_at->toIso8601String(),
            'fecha_formateada'      => $importacion->created_at->format('d/m/Y H:i'),
            'hace'                  => $importacion->created_at->locale('es')->diffForHumans(),
            'detalle'               => $importacion->detalle,
            'resumen'               => $importacion->resumen,
            'url'                   => route('importaciones.show', $importacion),
        ]);
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
