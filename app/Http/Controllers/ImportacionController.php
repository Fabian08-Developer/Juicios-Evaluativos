<?php

namespace App\Http\Controllers;

use App\Models\Ficha;
use App\Models\Importacion;
use App\Models\ImportacionCambio;

class ImportacionController extends Controller
{
    public function index()
    {
        $importaciones             = Importacion::with('usuario')->latest()->orderByDesc('id')->paginate(20);
        $totalAprendicesProcesados = Importacion::sum('aprendices_procesados');
        $ultimaImportacion         = Importacion::latest()->orderByDesc('id')->first();

        // Solo se enlaza la línea de tiempo de fichas que aún existen.
        $fichasExistentes = Ficha::whereIn('Id_Ficha', $importaciones->pluck('id_ficha')->filter()->unique()->map(fn ($f) => (int) $f))
            ->pluck('Id_Ficha')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();

        return view('importaciones.index', compact(
            'importaciones',
            'totalAprendicesProcesados',
            'ultimaImportacion',
            'fichasExistentes'
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
