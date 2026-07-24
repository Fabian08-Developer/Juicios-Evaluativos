<?php

namespace App\Http\Controllers;

use App\Models\Aprendiz;
use App\Models\JuicioEvaluativo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * MEJORA TÉCNICA #2 — Caché de consultas pesadas del dashboard
 *
 * Problema anterior: Cada visita ejecutaba 4 JOINs complejos entre 5 tablas.
 * Si el instructor cambiaba de ficha 10 veces, hacía 40+ consultas en BD.
 *
 * Solución: Cache::remember() con TTL de 5 minutos por clave única {fichaId}.
 * El caché se invalida automáticamente vía el Listener ActualizarCacheDashboard
 * cada vez que se importa un nuevo Excel.
 */
class DashboardController extends Controller
{
    private const CACHE_TTL_SEGUNDOS = 300; // 5 minutos

    /**
     * Calcula todas las estadísticas, usando caché cuando es posible.
     */
    private function calcularEstadisticas(?string $fichaId): array
    {
        // Clave única por ficha para el caché
        $cacheKey = $fichaId
            ? "dashboard.stats.ficha.{$fichaId}"
            : 'dashboard.stats.global';

        // Cache::remember → almacena arreglos puros para evitar errores de deserialización (__PHP_Incomplete_Class)
        $stats = Cache::remember($cacheKey, self::CACHE_TTL_SEGUNDOS, function () use ($fichaId) {
            return $this->ejecutarConsultasEstadisticas($fichaId);
        });

        // Garantizar siempre que sean objetos Collection listos y limpios al retornarse
        $stats['statsPorFicha']            = collect($stats['statsPorFicha']);
        $stats['aprobacionPorCompetencia'] = collect($stats['aprobacionPorCompetencia']);

        return $stats;
    }

    /**
     * Consultas reales a la BD (solo se ejecutan si no hay caché).
     * Usando los nuevos Scopes de Aprendiz (Mejora Técnica #3).
     */
    private function ejecutarConsultasEstadisticas(?string $fichaId): array
    {
        // ── Aprendices ────────────────────────────────────────────────────
        $aprendizQuery = Aprendiz::query()->when($fichaId, fn ($q) => $q->deFicha($fichaId));
        $totalAprendices = (clone $aprendizQuery)->count();

        // ── Juicios ────────────────────────────────────────────────────────
        $juiciosQuery = JuicioEvaluativo::query();
        if ($fichaId) {
            $juiciosQuery->whereHas('aprendiz', fn ($q) => $q->where('Id_Ficha', $fichaId));
        }
        $juiciosAprobados  = (clone $juiciosQuery)->where('Estado', 1)->count();
        $juiciosPendientes = (clone $juiciosQuery)->where('Estado', 0)->count();

        // ── Aprendices en Riesgo (usando Scope) ───────────────────────────
        $aprendicesEnRiesgo = Aprendiz::enRiesgo()
            ->when($fichaId, fn ($q) => $q->deFicha($fichaId))
            ->count();

        // ── Aprobación por Competencia ─────────────────────────────────────
        $aprobacionPorCompetenciaQuery = DB::table('competencia')
            ->join('resultados', 'resultados.Id_Competencia', '=', 'competencia.Id_Competencia')
            ->leftJoin('juicios_evaluativos', 'resultados.Id_Resultado', '=', 'juicios_evaluativos.Id_Resultado');

        if ($fichaId) {
            $aprobacionPorCompetenciaQuery
                ->join('aprendiz', 'juicios_evaluativos.Id_Aprendiz', '=', 'aprendiz.Id_Aprendiz')
                ->where('aprendiz.Id_Ficha', $fichaId);
        }

        $aprobacionPorCompetencia = $aprobacionPorCompetenciaQuery
            ->select(
                'competencia.Codigo',
                DB::raw('COUNT(CASE WHEN "juicios_evaluativos"."Estado" = 1 THEN 1 END) as aprobados'),
                DB::raw('COUNT("juicios_evaluativos"."Id_Juicio") as total')
            )
            ->groupBy('competencia.Codigo', 'competencia.Id_Competencia')
            ->get()
            ->map(function ($item) {
                $item->porcentaje = $item->total > 0
                    ? round(($item->aprobados / $item->total) * 100, 2)
                    : 0;
                return (array) $item;
            })->values()->all();

        // ── Stats generales por Ficha ──────────────────────────────────────
        $statsPorFicha = DB::table('ficha')
            ->leftJoin('aprendiz', 'ficha.Id_Ficha', '=', 'aprendiz.Id_Ficha')
            ->leftJoin('juicios_evaluativos', 'aprendiz.Id_Aprendiz', '=', 'juicios_evaluativos.Id_Aprendiz')
            ->select(
                'ficha.Id_Ficha',
                DB::raw('COUNT(CASE WHEN "juicios_evaluativos"."Estado" = 1 THEN 1 END) as aprobados'),
                DB::raw('COUNT(CASE WHEN "juicios_evaluativos"."Estado" = 0 THEN 1 END) as pendientes')
            )
            ->groupBy('ficha.Id_Ficha')
            ->orderBy('ficha.Id_Ficha')
            ->get()
            ->map(fn($item) => (array) $item)
            ->values()->all();

        return compact(
            'totalAprendices', 'juiciosAprobados', 'juiciosPendientes',
            'aprendicesEnRiesgo', 'aprobacionPorCompetencia', 'statsPorFicha'
        );
    }

    public function index(Request $request)
    {
        $fichaId = $request->get('ficha_id');
        $fichas  = \App\Models\Ficha::with('programa')->get();
        $stats   = $this->calcularEstadisticas($fichaId);

        // Garantizar que siempre sean objetos Collection válidos (incluso al deserializar de caché)
        $stats['statsPorFicha']            = collect($stats['statsPorFicha']);
        $stats['aprobacionPorCompetencia'] = collect($stats['aprobacionPorCompetencia']);

        $aprendices = Aprendiz::withCount(['juicios as pendientes' => fn ($q) => $q->where('Estado', 0)])
            ->with(['ficha'])
            ->when($fichaId, fn ($q) => $q->deFicha($fichaId))
            ->get();

        // Detalle de aprendices en riesgo (máx 5 para el panel)
        $aprendicesRiesgoDetalle = Aprendiz::enRiesgo()
            ->with('ficha.programa')
            ->when($fichaId, fn ($q) => $q->deFicha($fichaId))
            ->get()
            ->take(5);

        return view('dashboard', array_merge($stats, compact(
            'aprendices', 'fichas', 'fichaId', 'aprendicesRiesgoDetalle'
        )));
    }

    /**
     * Endpoint JSON para AJAX — también usa caché.
     */
    public function statsJson(Request $request)
    {
        $fichaId = $request->get('ficha_id');
        $stats   = $this->calcularEstadisticas($fichaId);

        return response()->json([
            'totalAprendices'          => $stats['totalAprendices'],
            'juiciosAprobados'         => $stats['juiciosAprobados'],
            'juiciosPendientes'        => $stats['juiciosPendientes'],
            'aprendicesEnRiesgo'       => $stats['aprendicesEnRiesgo'],
            'aprobacionPorCompetencia' => $stats['aprobacionPorCompetencia'],
            'statsPorFicha'            => $stats['statsPorFicha'],
            'fromCache'                => true, // Header informativo
        ]);
    }

    public function juiciosList()
    {
        $juicios = JuicioEvaluativo::with(['aprendiz', 'resultado', 'funcionario'])->paginate(20);
        return view('juicios.index', compact('juicios'));
    }
}
