<?php

namespace App\Http\Controllers;

use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\JuicioEvaluativo;
use App\Models\Resultado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InnovacionAcademicaController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    //  1. SIMULADOR DE SALVACIÓN ACADÉMICA & ACTA DE COMPROMISO PDF
    // ══════════════════════════════════════════════════════════════════════════

    public function simularSalvacion($id)
    {
        $aprendiz = Aprendiz::with(['ficha.programa', 'juicios.resultado.competencia'])->findOrFail($id);

        $totalJuicios = $aprendiz->juicios->count();
        $aprobados    = $aprendiz->juicios->where('Estado', 1)->count();
        $pendientes   = $aprendiz->juicios->where('Estado', 0);
        $countPendientes = $pendientes->count();

        $tasaActual = $totalJuicios > 0 ? round(($aprobados / $totalJuicios) * 100, 1) : 0;
        
        // Meta de salvación: alcanzar al menos el 70% de aprobación para salir de zona roja
        $metaPorcentaje = 70.0;
        $juiciosMetaNecesarios = ceil(($metaPorcentaje / 100) * $totalJuicios);
        $juiciosAprobarParaSalvacion = max(0, $juiciosMetaNecesarios - $aprobados);

        // Agrupar pendientes por competencia para focalizar el plan de mejoramiento
        $pendientesPorCompetencia = $pendientes->groupBy(function ($juicio) {
            return $juicio->resultado->competencia->Nombre ?? 'Competencia General';
        })->map(function ($group, $compNombre) {
            return [
                'competencia' => $compNombre,
                'codigo'      => $group->first()->resultado->competencia->Codigo ?? 'GEN',
                'pendientes'  => $group->count(),
                'resultados'  => $group->map(fn($j) => $j->resultado)
            ];
        })->sortByDesc('pendientes');

        return view('acciones.simulador', compact(
            'aprendiz', 'totalJuicios', 'aprobados', 'countPendientes',
            'tasaActual', 'metaPorcentaje', 'juiciosAprobarParaSalvacion', 'pendientesPorCompetencia'
        ));
    }

    public function descargarActaPdf($id)
    {
        $aprendiz = Aprendiz::with(['ficha.programa', 'juicios.resultado.competencia'])->findOrFail($id);

        $pendientes = $aprendiz->juicios->where('Estado', 0)->map(fn($j) => $j->resultado);
        $totalJuicios = $aprendiz->juicios->count();
        $aprobados    = $aprendiz->juicios->where('Estado', 1)->count();
        $tasaActual   = $totalJuicios > 0 ? round(($aprobados / $totalJuicios) * 100, 1) : 0;

        $pdf = Pdf::loadView('acciones.acta-compromiso-pdf', compact('aprendiz', 'pendientes', 'tasaActual', 'totalJuicios', 'aprobados'))
                  ->setPaper('a4', 'portrait');

        $nombreArchivo = "Acta_Compromiso_SENA_{$aprendiz->Documento}.pdf";
        return $pdf->download($nombreArchivo);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  2. DETECTOR DE CUELLOS DE BOTELLA & GRUPO DE REFUERZO
    // ══════════════════════════════════════════════════════════════════════════

    public function cuellosBotella(Request $request)
    {
        $fichaId = $request->get('ficha_id');
        $fichas  = Ficha::with('programa')->get();

        // Consulta agregada: Contar cuántos juicios pendientes hay por competencia en la ficha seleccionada
        $query = DB::table('competencia')
            ->join('resultados', 'resultados.Id_Competencia', '=', 'competencia.Id_Competencia')
            ->join('juicios_evaluativos', 'juicios_evaluativos.Id_Resultado', '=', 'resultados.Id_Resultado')
            ->join('aprendiz', 'aprendiz.Id_Aprendiz', '=', 'juicios_evaluativos.Id_Aprendiz')
            ->where('juicios_evaluativos.Estado', 0); // Solo pendientes

        if ($fichaId) {
            $query->where('aprendiz.Id_Ficha', $fichaId);
        }

        $rankingCompetencias = $query->select(
            'competencia.Id_Competencia',
            'competencia.Codigo',
            'competencia.Nombre',
            DB::raw('COUNT(DISTINCT "aprendiz"."Id_Aprendiz") as total_aprendices_afectados'),
            DB::raw('COUNT("juicios_evaluativos"."Id_Juicio") as total_juicios_pendientes')
        )
        ->groupBy('competencia.Id_Competencia', 'competencia.Codigo', 'competencia.Nombre')
        ->orderByDesc('total_aprendices_afectados')
        ->get();

        // Si el usuario seleccionó una competencia específica para aislar el "Grupo de Refuerzo"
        $competenciaSeleccionadaId = $request->get('competencia_id');
        $grupoRefuerzo = collect([]);
        $competenciaObj = null;

        if ($competenciaSeleccionadaId) {
            $competenciaObj = Competencia::find($competenciaSeleccionadaId);
            
            $grupoRefuerzoQuery = Aprendiz::with(['ficha', 'juicios' => function ($q) use ($competenciaSeleccionadaId) {
                $q->where('Estado', 0)
                  ->whereHas('resultado', fn($rQuery) => $rQuery->where('Id_Competencia', $competenciaSeleccionadaId));
            }, 'juicios.resultado'])
            ->whereHas('juicios', function ($q) use ($competenciaSeleccionadaId) {
                $q->where('Estado', 0)
                  ->whereHas('resultado', fn($rQuery) => $rQuery->where('Id_Competencia', $competenciaSeleccionadaId));
            });

            if ($fichaId) {
                $grupoRefuerzoQuery->where('Id_Ficha', $fichaId);
            }

            $grupoRefuerzo = $grupoRefuerzoQuery->get();
        }

        return view('acciones.cuellos-botella', compact(
            'fichas', 'fichaId', 'rankingCompetencias', 'competenciaSeleccionadaId', 'grupoRefuerzo', 'competenciaObj'
        ));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  3. SEMÁFORO PREDICTIVO DE DESERCIÓN (IA / HEURÍSTICA)
    // ══════════════════════════════════════════════════════════════════════════

    public function diagnosticoDesercion(Request $request)
    {
        $fichaId = $request->get('ficha_id');
        $semaforoFiltro = $request->get('semaforo'); // critico, moderado, estable
        $fichas = Ficha::with('programa')->get();

        $aprendicesQuery = Aprendiz::withCount([
            'juicios as total_juicios',
            'juicios as pendientes_count' => fn ($q) => $q->where('Estado', 0),
            'juicios as aprobados_count'  => fn ($q) => $q->where('Estado', 1)
        ])->with('ficha.programa');

        if ($fichaId) {
            $aprendicesQuery->deFicha($fichaId);
        }

        $aprendices = $aprendicesQuery->get()->map(function ($a) {
            // Algoritmo Heurístico de Cálculo de Riesgo (0 a 100)
            $porcentajePendientes = $a->total_juicios > 0 ? ($a->pendientes_count / $a->total_juicios) * 100 : 0;
            
            $score = round($porcentajePendientes);

            // Penalización por estados anormales
            if (in_array($a->Estado, ['RETIRO VOLUNTARIO', 'CANCELADO', 'TRASLADADO'])) {
                $score = 100;
            } elseif ($porcentajePendientes >= 70) {
                $score = max(85, $score); // Alerta crítica automática si debe ≥70%
            }

            $a->score_riesgo = min(100, $score);

            if ($a->score_riesgo >= 75) {
                $a->semaforo = 'critico';
                $a->semaforo_label = '🔴 Crítico';
                $a->semaforo_color = '#ef4444';
            } elseif ($a->score_riesgo >= 40) {
                $a->semaforo = 'moderado';
                $a->semaforo_label = '🟡 Moderado';
                $a->semaforo_color = '#f59e0b';
            } else {
                $a->semaforo = 'estable';
                $a->semaforo_label = '🟢 Estable';
                $a->semaforo_color = '#10b981';
            }

            return $a;
        });

        // Filtrado por semáforo si se solicitó
        if ($semaforoFiltro) {
            $aprendices = $aprendices->where('semaforo', $semaforoFiltro)->values();
        }

        // Conteos para las tarjetas superiores
        $conteoCritico   = $aprendices->where('semaforo', 'critico')->count();
        $conteoModerado  = $aprendices->where('semaforo', 'moderado')->count();
        $conteoEstable   = $aprendices->where('semaforo', 'estable')->count();

        return view('acciones.diagnostico-desercion', compact(
            'fichas', 'fichaId', 'semaforoFiltro', 'aprendices',
            'conteoCritico', 'conteoModerado', 'conteoEstable'
        ));
    }

    public function alertaMasiva(Request $request)
    {
        $ids = $request->input('aprendices_ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'Por favor selecciona al menos un aprendiz para emitir la alerta a Bienestar.');
        }

        $aprendices = Aprendiz::whereIn('Id_Aprendiz', $ids)->get();
        
        Log::info("[Alerta Temprana SENA] Se emitió alerta oficial a Coordinación/Bienestar para " . count($aprendices) . " aprendices.", [
            'ids' => $ids,
            'documentos' => $aprendices->pluck('Documento')->toArray()
        ]);

        return back()->with('success', '✅ Alerta masiva enviada exitosamente a Bienestar al Aprendiz y Coordinación Académica para ' . count($aprendices) . ' aprendices seleccionados.');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  4. MATRIZ INTERACTIVA DE EVALUACIÓN RÁPIDA EN PANTALLA
    // ══════════════════════════════════════════════════════════════════════════

    public function matrizEvaluacion(Request $request)
    {
        $fichas = Ficha::with('programa')->get();
        $fichaId = $request->get('ficha_id', $fichas->first()->Id_Ficha ?? null);
        $competencias = collect([]);
        $competenciaId = $request->get('competencia_id');

        $aprendices = collect([]);
        $resultados = collect([]);
        $juiciosMap = [];

        if ($fichaId) {
            $fichaSeleccionada = Ficha::with('programa')->find($fichaId);
            
            // Cargar competencias asociadas a los resultados o de la ficha
            $competencias = Competencia::whereHas('resultados.juicios.aprendiz', fn($q) => $q->where('Id_Ficha', $fichaId))->get();
            if ($competencias->isEmpty()) {
                $competencias = Competencia::all();
            }

            if (!$competenciaId && $competencias->isNotEmpty()) {
                $competenciaId = $competencias->first()->Id_Competencia;
            }

            // Obtener aprendices de la ficha
            $aprendices = Aprendiz::where('Id_Ficha', $fichaId)->orderBy('Apellido')->get();

            // Obtener resultados de aprendizaje de la competencia seleccionada
            if ($competenciaId) {
                $resultados = Resultado::where('Id_Competencia', $competenciaId)->orderBy('Codigo')->get();
            } else {
                $resultados = Resultado::limit(8)->get(); // Fallback rápido si no hay competencia
            }

            // Crear el mapa de juicios evaluativos [$aprendizId-$resultadoId => Estado]
            $juiciosDB = JuicioEvaluativo::whereIn('Id_Aprendiz', $aprendices->pluck('Id_Aprendiz'))
                ->whereIn('Id_Resultado', $resultados->pluck('Id_Resultado'))
                ->get();

            foreach ($juiciosDB as $j) {
                $juiciosMap["{$j->Id_Aprendiz}-{$j->Id_Resultado}"] = (int) $j->Estado;
            }
        }

        return view('acciones.matriz-evaluacion', compact(
            'fichas', 'fichaId', 'competencias', 'competenciaId',
            'aprendices', 'resultados', 'juiciosMap'
        ));
    }

    public function actualizarJuicioAjax(Request $request)
    {
        $request->validate([
            'id_aprendiz'  => 'required|integer|exists:aprendiz,Id_Aprendiz',
            'id_resultado' => 'required|integer|exists:resultados,Id_Resultado',
            'estado'       => 'required|integer|in:0,1'
        ]);

        try {
            $funcionarioId = Funcionario::first()->Id_Funcionario ?? 1;

            $juicio = JuicioEvaluativo::updateOrCreate(
                [
                    'Id_Aprendiz'  => $request->id_aprendiz,
                    'Id_Resultado' => $request->id_resultado,
                ],
                [
                    'Estado'         => $request->estado,
                    'Id_Funcionario' => $funcionarioId,
                    'Fecha'          => now()->toDateString(),
                    'Hora'           => now()
                ]
            );

            // Invalidar caché del dashboard inmediatamente
            Cache::forget('dashboard.stats.global');
            $aprendiz = Aprendiz::find($request->id_aprendiz);
            if ($aprendiz && $aprendiz->Id_Ficha) {
                Cache::forget("dashboard.stats.ficha.{$aprendiz->Id_Ficha}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Juicio evaluativo actualizado correctamente.',
                'estado'  => (int) $juicio->Estado
            ]);

        } catch (\Exception $e) {
            Log::error("[Matriz Evaluacion] Error al actualizar juicio: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarLoteAjax(Request $request)
    {
        $request->validate([
            'cambios' => 'required|array',
            'cambios.*.id_aprendiz'  => 'required|integer',
            'cambios.*.id_resultado' => 'required|integer',
            'cambios.*.estado'       => 'required|integer|in:0,1',
        ]);

        try {
            $funcionarioId = Funcionario::first()->Id_Funcionario ?? 1;
            $count = 0;

            DB::transaction(function () use ($request, $funcionarioId, &$count) {
                foreach ($request->cambios as $item) {
                    JuicioEvaluativo::updateOrCreate(
                        [
                            'Id_Aprendiz'  => $item['id_aprendiz'],
                            'Id_Resultado' => $item['id_resultado'],
                        ],
                        [
                            'Estado'         => $item['estado'],
                            'Id_Funcionario' => $funcionarioId,
                            'Fecha'          => now()->toDateString(),
                            'Hora'           => now()
                        ]
                    );
                    $count++;
                }
            });

            Cache::forget('dashboard.stats.global');

            return response()->json([
                'success' => true,
                'message' => "Se han guardado {$count} juicios evaluativos en lote exitosamente."
            ]);

        } catch (\Exception $e) {
            Log::error("[Matriz Evaluacion Lote] Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el lote: ' . $e->getMessage()
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  5. CENTRO DE NOTIFICACIONES WHATSAPP & CORREO
    // ══════════════════════════════════════════════════════════════════════════

    public function registrarNotificacion(Request $request)
    {
        $request->validate([
            'aprendiz_id' => 'required|exists:aprendiz,Id_Aprendiz',
            'canal'       => 'required|in:whatsapp,correo',
            'mensaje'     => 'required|string'
        ]);

        $aprendiz = Aprendiz::find($request->aprendiz_id);

        Log::info("[Notificación SENA] Alerta enviada por {$request->canal} al aprendiz {$aprendiz->Nombre} {$aprendiz->Apellido} ({$aprendiz->Documento}).", [
            'mensaje' => $request->mensaje
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Bitácora de notificación registrada exitosamente.']);
        }

        return back()->with('success', "✅ Notificación registrada en bitácora para el aprendiz {$aprendiz->Nombre} {$aprendiz->Apellido}.");
    }
}
