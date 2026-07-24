@extends('layouts.app')

@section('title', 'Simulador de Salvación Académica')

@section('content')
<div style="max-width: 1100px; margin: 0 auto;">

    <!-- Encabezado y navegación de regreso -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="{{ route('aprendices.show', $aprendiz->Id_Aprendiz) }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-arrow-left"></i> Volver al Expediente
            </a>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                <span style="background: rgba(57,169,0,0.15); color: var(--primary); padding: 0.4rem 0.8rem; border-radius: 12px; font-size: 1.2rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </span>
                Simulador de Salvación Académica
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.3rem;">
                Cálculo inteligente para proyectar la superación del estado "En Riesgo" de <strong>{{ $aprendiz->Nombre }} {{ $aprendiz->Apellido }}</strong>.
            </p>
        </div>

        <div style="display: flex; gap: 1rem;">
            <a href="{{ route('acciones.acta.pdf', $aprendiz->Id_Aprendiz) }}" class="btn btn-primary" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); padding: 0.8rem 1.5rem; font-weight: 800; border-radius: 14px; box-shadow: 0 8px 20px -4px var(--primary-glow); display: inline-flex; align-items: center; gap: 0.75rem;">
                <i class="fa-solid fa-file-pdf"></i> Descargar Acta de Compromiso (PDF)
            </a>
        </div>
    </div>

    <!-- Panel Superior de Diagnóstico Matemático -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        
        <!-- Estado Actual -->
        <div class="card" style="position: relative; overflow: hidden; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.08);">
            <div style="position: absolute; top: -30px; right: -30px; width: 100px; height: 100px; background: rgba(239, 68, 68, 0.1); filter: blur(40px); border-radius: 50%;"></div>
            
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                Situación Actual del Aprendiz
            </div>
            
            <div style="display: flex; align-items: baseline; gap: 1rem; margin-bottom: 1rem;">
                <div style="font-size: 2.5rem; font-weight: 900; color: {{ $tasaActual < 70 ? '#ef4444' : '#10b981' }};">
                    {{ $tasaActual }}%
                </div>
                <div style="font-size: 0.9rem; color: var(--text-muted);">
                    <strong>{{ $aprobados }}</strong> de {{ $totalJuicios }} aprobados
                </div>
            </div>

            <!-- Barra de progreso visual -->
            <div style="background: rgba(255,255,255,0.08); height: 12px; border-radius: 999px; overflow: hidden; position: relative; margin-bottom: 0.75rem;">
                <div style="width: {{ min(100, $tasaActual) }}%; background: {{ $tasaActual < 70 ? '#ef4444' : '#10b981' }}; height: 100%; border-radius: 999px; transition: width 0.6s;"></div>
                <!-- Línea de meta 70% -->
                <div style="position: absolute; left: 70%; top: 0; bottom: 0; width: 2px; background: #fbbf24; z-index: 2;" title="Meta mínima: 70%"></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted);">
                <span>0%</span>
                <span style="color: #fbbf24; font-weight: 700;"><i class="fa-solid fa-flag"></i> Meta Mínima: 70%</span>
                <span>100%</span>
            </div>
        </div>

        <!-- Proyección y Salida de Riesgo -->
        <div class="card" style="position: relative; overflow: hidden; background: linear-gradient(135deg, rgba(57,169,0,0.08), rgba(14,165,233,0.08)); border: 1px solid rgba(57,169,0,0.2);">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                <i class="fa-solid fa-calculator"></i> Proyección de Salvación
            </div>

            @if($juiciosAprobarParaSalvacion > 0)
                <div style="font-size: 1.1rem; color: #fff; line-height: 1.6; margin-bottom: 1.25rem;">
                    Para salir del estado en riesgo y alcanzar el <strong>70%</strong>, el aprendiz necesita aprobar exactamente:
                </div>
                <div style="display: flex; align-items: center; gap: 1.25rem; background: rgba(0,0,0,0.25); padding: 1.25rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);">
                    <div style="width: 54px; height: 54px; background: rgba(57,169,0,0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 900; color: var(--primary);">
                        +{{ $juiciosAprobarParaSalvacion }}
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 1.15rem; color: #fff;">Juicios Evaluativos Prioritarios</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">De los {{ $countPendientes }} que adeuda actualmente.</div>
                    </div>
                </div>
            @else
                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem 0;">
                    <div style="font-size: 2.5rem; color: #10b981;"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div style="font-weight: 800; font-size: 1.2rem; color: #fff;">¡Objetivo Cumplido!</div>
                        <div style="font-size: 0.9rem; color: var(--text-muted);">El aprendiz actualmente supera la meta del 70% de aprobación en su expediente.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Resultados Pendientes Agrupados por Competencia -->
    <h2 style="font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem;">
        <i class="fa-solid fa-list-check" style="color: var(--primary);"></i>
        Plan Focalizado por Competencias Pendientes ({{ $pendientesPorCompetencia->count() }} Competencias)
    </h2>

    @if($pendientesPorCompetencia->isEmpty())
        <div class="card" style="text-align: center; padding: 3rem;">
            <i class="fa-solid fa-clipboard-check" style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;"></i>
            <h3 style="color: #fff; font-weight: 800;">No hay resultados pendientes</h3>
            <p style="color: var(--text-muted); max-width: 400px; margin: 0.5rem auto 0;">El aprendiz ha completado todos sus juicios evaluativos satisfactoriamente.</p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            @foreach($pendientesPorCompetencia as $idx => $grupo)
                <div class="card" style="border-left: 4px solid {{ $loop->first ? '#ef4444' : 'var(--primary)' }}; padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: rgba(255,255,255,0.06); padding: 0.3rem 0.6rem; border-radius: 6px; color: var(--text-muted); display: inline-block; margin-bottom: 0.5rem;">
                                Prioridad #{{ $loop->iteration }} • Código: {{ $grupo['codigo'] }}
                            </span>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0;">
                                {{ $grupo['competencia'] }}
                            </h3>
                        </div>
                        <div style="background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); padding: 0.4rem 0.8rem; border-radius: 10px; font-size: 0.85rem; font-weight: 800;">
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ $grupo['pendientes'] }} Resultados Pendientes
                        </div>
                    </div>

                    <!-- Lista de Resultados en esta Competencia -->
                    <div style="background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <th style="padding: 0.75rem 1rem; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Código RA</th>
                                    <th style="padding: 0.75rem 1rem; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Denominación del Resultado de Aprendizaje</th>
                                    <th style="padding: 0.75rem 1rem; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; text-align: right;">Acción de Evaluar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo['resultados'] as $resultado)
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                    <td style="padding: 0.85rem 1rem; font-weight: 800; color: var(--primary); font-size: 0.85rem; width: 120px;">
                                        {{ $resultado->Codigo }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; color: #f1f5f9; font-size: 0.9rem;">
                                        {{ $resultado->Nombre }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: right;">
                                        <a href="{{ route('acciones.matriz', ['ficha_id' => $aprendiz->Id_Ficha, 'competencia_id' => $resultado->Id_Competencia]) }}" 
                                           class="btn" style="background: rgba(57,169,0,0.1); color: var(--primary); border: 1px solid rgba(57,169,0,0.2); padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-decoration: none;">
                                            <i class="fa-solid fa-check"></i> Calificar en Matriz
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
