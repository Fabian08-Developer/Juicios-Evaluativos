<?php

namespace App\Http\Controllers;

use App\Mail\AlertaBienestarMail;
use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\Ficha;
use App\Models\Funcionario;
use App\Models\JuicioEvaluativo;
use App\Models\Remision;
use App\Models\Resultado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $search = $request->get('search');
        $estadoFiltro = $request->get('estado');
        $fichas = Ficha::with('programa')->get();

        $aprendicesQuery = Aprendiz::withCount([
            'juicios as total_juicios',
            'juicios as pendientes_count' => fn ($q) => $q->where('Estado', 0),
            'juicios as aprobados_count'  => fn ($q) => $q->where('Estado', 1)
        ])->with('ficha.programa');

        if ($fichaId) {
            $aprendicesQuery->deFicha($fichaId);
        }

        if (!empty($search)) {
            $aprendicesQuery->buscar($search);
        }

        if (!empty($estadoFiltro)) {
            $aprendicesQuery->where('Estado', $estadoFiltro);
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

        // Conteos para las tarjetas superiores (calculados sobre el conjunto de aprendices antes de filtrar por semaforo)
        $conteoCritico   = $aprendices->where('semaforo', 'critico')->count();
        $conteoModerado  = $aprendices->where('semaforo', 'moderado')->count();
        $conteoEstable   = $aprendices->where('semaforo', 'estable')->count();

        // Filtrado por semáforo si se solicitó
        if ($semaforoFiltro) {
            $aprendices = $aprendices->where('semaforo', $semaforoFiltro)->values();
        }

        return view('acciones.diagnostico-desercion', compact(
            'fichas', 'fichaId', 'semaforoFiltro', 'search', 'estadoFiltro', 'aprendices',
            'conteoCritico', 'conteoModerado', 'conteoEstable'
        ));
    }

    public function alertaMasiva(Request $request)
    {
        $ids = $request->input('aprendices_ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'Por favor selecciona al menos un aprendiz para emitir la alerta a Bienestar.');
        }

        $aprendices = Aprendiz::with(['ficha.programa', 'juicios' => fn($q) => $q->where('Estado', 0)])
            ->whereIn('Id_Aprendiz', $ids)
            ->get();

        $radicadoConsecutivo = (Remision::max('id') ?? 0) + 1;
        $radicado = 'REM-' . date('Y') . '-' . str_pad($radicadoConsecutivo, 4, '0', STR_PAD_LEFT);
        $aprendicesDataMail = [];
        $fichaPrincipal = $aprendices->first()->ficha ?? null;

        foreach ($aprendices as $ap) {
            $totalJuicios = $ap->juicios()->count();
            $pendientes = $ap->juicios->count();
            $score = $totalJuicios > 0 ? round(($pendientes / $totalJuicios) * 100) : 0;
            if (in_array($ap->Estado, ['RETIRO VOLUNTARIO', 'CANCELADO', 'TRASLADADO'])) {
                $score = 100;
            }
            $score = min(100, $score);
            $semaforo = $score >= 70 ? 'CRITICO' : 'MODERADO';

            Remision::create([
                'Id_Aprendiz'      => $ap->Id_Aprendiz,
                'Id_Ficha'         => $ap->Id_Ficha,
                'score_riesgo'     => $score,
                'nivel_semaforo'   => $semaforo,
                'total_pendientes' => $pendientes,
                'estado_remision'  => 'PENDIENTE',
                'radicado'         => $radicado,
                'motivo'           => "Alerta por riesgo de deserción ({$score}% juicios pendientes)",
            ]);

            $aprendicesDataMail[] = [
                'nombre'           => $ap->Nombre,
                'apellido'         => $ap->Apellido,
                'documento'        => $ap->Documento,
                'ficha'            => $ap->Id_Ficha,
                'pendientes_count' => $pendientes,
                'score_riesgo'     => $score,
            ];
        }

        // Envío de correo formal a leiderfabianramoscano99@gmail.com
        $correoDestino = 'leiderfabianramoscano99@gmail.com';
        try {
            Mail::to($correoDestino)->send(new AlertaBienestarMail(
                $aprendicesDataMail,
                $radicado,
                (string) ($fichaPrincipal->Id_Ficha ?? ''),
                $fichaPrincipal->programa->Nombre ?? '',
                now()->format('d/m/Y H:i A')
            ));
            Log::info("[Alerta Bienestar] Correo enviado exitosamente a {$correoDestino} (Radicado: {$radicado}).");
        } catch (\Throwable $e) {
            Log::error("[Alerta Bienestar] No se pudo enviar el correo a {$correoDestino}: " . $e->getMessage());
        }

        return redirect()->route('remisiones.index')
            ->with('success', "✅ Alerta oficial emitida (Radicado: {$radicado}). Se remitieron " . count($aprendices) . " aprendices a Bienestar y se despachó la notificación a {$correoDestino}.");
    }

    public function historialRemisiones(Request $request)
    {
        $fichas = Ficha::with('programa')->get();
        $fichaFiltro  = $request->get('ficha');
        $estadoFiltro = $request->get('estado');

        $query = Remision::with(['aprendiz', 'ficha.programa'])->latest();

        if ($fichaFiltro) {
            $query->where('Id_Ficha', $fichaFiltro);
        }

        if ($estadoFiltro) {
            $query->where('estado_remision', $estadoFiltro);
        }

        $totalCasos       = Remision::count();
        $pendientesCount  = Remision::where('estado_remision', 'PENDIENTE')->count();
        $seguimientoCount = Remision::where('estado_remision', 'EN_SEGUIMIENTO')->count();
        $atendidosCount   = Remision::where('estado_remision', 'ATENDIDO')->count();

        $remisiones = $query->paginate(15)->withQueryString();

        return view('remisiones.index', compact(
            'remisiones', 'fichas', 'totalCasos',
            'pendientesCount', 'seguimientoCount', 'atendidosCount'
        ));
    }

    public function actualizarEstadoRemision(Request $request, $id)
    {
        $request->validate([
            'estado_remision' => 'required|in:PENDIENTE,EN_SEGUIMIENTO,ATENDIDO,CERRADO'
        ]);

        $remision = Remision::findOrFail($id);
        $remision->update([
            'estado_remision' => $request->estado_remision,
            'observaciones'   => $request->observaciones ?? $remision->observaciones
        ]);

        return back()->with('success', "Estado de la remisión {$remision->radicado} actualizado correctamente.");
    }

    public function descargarOficioPdf(Request $request)
    {
        $radicado = $request->get('radicado');
        
        $query = Remision::with(['aprendiz', 'ficha.programa']);
        if ($radicado) {
            $query->where('radicado', $radicado);
        }

        $remisiones = $query->get();
        if ($remisiones->isEmpty()) {
            return back()->with('error', 'No se encontraron registros para generar el oficio.');
        }

        $ficha = $remisiones->first()->ficha ?? null;
        $aprendices = $remisiones->map(function ($rem) {
            $ap = $rem->aprendiz;
            if ($ap) {
                $ap->remision = $rem;
            }
            return $ap;
        })->filter();

        $radicadoFinal = $radicado ?: ($remisiones->first()->radicado ?? 'REM-' . date('Ymd'));

        $pdf = Pdf::loadView('acciones.oficio-remision-pdf', [
            'radicado'   => $radicadoFinal,
            'fecha'      => $remisiones->first()->created_at->format('d/m/Y - H:i'),
            'ficha'      => $ficha,
            'aprendices' => $aprendices
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Oficio_Remision_{$radicadoFinal}.pdf");
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
        $search = $request->get('search');
        $estadoFiltro = $request->get('estado');

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

            // Obtener aprendices de la ficha con filtros opcionales
            $aprendicesQuery = Aprendiz::where('Id_Ficha', $fichaId);

            if (!empty($search)) {
                $aprendicesQuery->buscar($search);
            }

            if (!empty($estadoFiltro)) {
                $aprendicesQuery->where('Estado', $estadoFiltro);
            }

            $aprendices = $aprendicesQuery->orderBy('Apellido')->orderBy('Nombre')->get();

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
            'fichas', 'fichaId', 'competencias', 'competenciaId', 'search', 'estadoFiltro',
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
