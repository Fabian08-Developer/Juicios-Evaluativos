@extends('layouts.app')

@section('title', 'Comparador de Evolución Temporal')

@section('content')
<div style="max-width: 1200px; margin: 0 auto;">

    <!-- Breadcrumb / Volver -->
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('importaciones.index', ['ficha' => $impA->id_ficha]) }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; transition: color 0.2s;"
           onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">
            <i class="fa-solid fa-arrow-left"></i> Volver al historial de importaciones
        </a>
    </div>

    <!-- Header Principal -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.5rem;">
            <div style="width: 46px; height: 46px; border-radius: 14px; background: rgba(57,169,0,0.15); border: 1px solid rgba(57,169,0,0.3); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.4rem;">
                <i class="fa-solid fa-code-compare"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #fff;">
                    Evolución Temporal de la Ficha {{ $impA->id_ficha ?? $impB->id_ficha }}
                </h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.2rem;">
                    Comparativa de avance académico entre dos reportes procesados en momentos distintos del tiempo.
                </p>
            </div>
        </div>

        <!-- Badges informativos -->
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem;">
            <span style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #cbd5e1; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-graduation-cap" style="color: var(--primary);"></i> {{ $impA->ficha->programa->Nombre ?? ($impB->ficha->programa->Nombre ?? 'Programa Sofia Plus') }}
            </span>
            <span style="background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.25); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #38bdf8; display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
                <i class="fa-solid fa-calendar-days"></i> Lapso Evaluado: {{ $diasTranscurridos }} día(s)
            </span>
        </div>
    </div>

    <!-- Comparativa Visual Lado a Lado (Hito A vs Hito B) -->
    <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 1.5rem; align-items: stretch; margin-bottom: 2rem;">
        
        <!-- Tarjeta Hito A (Inicial) -->
        <div class="card" style="padding: 1.75rem; border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span style="background: rgba(148,163,184,0.15); color: #94a3b8; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase;">
                        Hito Inicial (Reporte A)
                    </span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                        #{{ $impA->id }}
                    </span>
                </div>

                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-file-excel" style="color: var(--primary);"></i>
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 320px;" title="{{ $impA->nombre_archivo }}">
                        {{ $impA->nombre_archivo }}
                    </span>
                </h4>

                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    <i class="fa-regular fa-clock"></i> Procesado el {{ $impA->created_at->format('d/m/Y H:i') }}
                </div>

                <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 1rem; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Aprendices procesados:</span>
                        <strong style="color: #fff;">{{ $impA->aprendices_procesados }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Nuevos aprobados en carga:</span>
                        <strong style="color: var(--primary);">+{{ $impA->nuevos_aprobados }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Duración del proceso:</span>
                        <span style="color: #cbd5e1;">{{ $impA->duracion_segundos }}s</span>
                    </div>
                </div>
            </div>

            @if($impA->detalle)
            <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(255,255,255,0.02); border-radius: 8px; font-size: 0.78rem; color: var(--text-muted); font-style: italic;">
                {{ $impA->detalle }}
            </div>
            @endif
        </div>

        <!-- Conector Central con Flecha y Tiempo -->
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 100px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(57,169,0,0.1); border: 2px solid var(--primary); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.2rem; margin-bottom: 0.5rem; box-shadow: 0 0 16px var(--primary-glow);">
                <i class="fa-solid fa-arrow-right"></i>
            </div>
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--primary); text-transform: uppercase; text-align: center;">
                Avance
            </div>
            <div style="font-size: 0.7rem; color: var(--text-muted); text-align: center;">
                {{ $diasTranscurridos }} días
            </div>
        </div>

        <!-- Tarjeta Hito B (Posterior) -->
        <div class="card" style="padding: 1.75rem; border-color: rgba(57,169,0,0.3); background: rgba(57,169,0,0.03); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span style="background: rgba(57,169,0,0.2); color: var(--primary); padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase;">
                        Hito Posterior (Reporte B)
                    </span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                        #{{ $impB->id }}
                    </span>
                </div>

                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-file-excel" style="color: var(--primary);"></i>
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 320px;" title="{{ $impB->nombre_archivo }}">
                        {{ $impB->nombre_archivo }}
                    </span>
                </h4>

                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    <i class="fa-regular fa-clock"></i> Procesado el {{ $impB->created_at->format('d/m/Y H:i') }}
                </div>

                <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 1rem; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Aprendices procesados:</span>
                        <strong style="color: #fff;">{{ $impB->aprendices_procesados }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Nuevos aprobados en carga:</span>
                        <strong style="color: var(--primary);">+{{ $impB->nuevos_aprobados }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Duración del proceso:</span>
                        <span style="color: #cbd5e1;">{{ $impB->duracion_segundos }}s</span>
                    </div>
                </div>
            </div>

            @if($impB->detalle)
            <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(57,169,0,0.04); border-radius: 8px; font-size: 0.78rem; color: var(--text-muted); font-style: italic;">
                {{ $impB->detalle }}
            </div>
            @endif
        </div>

    </div>

    <!-- Resumen Ejecutivo del Avance -->
    <div class="card" style="padding: 2rem; margin-bottom: 2.5rem; border-color: rgba(255,255,255,0.1); background: rgba(15,23,42,0.8);">
        <h3 style="margin-bottom: 1.5rem; color: #fff; font-size: 1.25rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-chart-line" style="color: var(--primary);"></i>
            Diagnóstico de Progreso Entre Ambos Reportes
        </h3>

        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
            
            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 14px; padding: 1.25rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.4rem;">
                    Crecimiento de Aprobaciones
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: var(--primary);">
                    +{{ $impB->nuevos_aprobados }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                    Juicios adicionales incorporados al grupo
                </div>
            </div>

            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 14px; padding: 1.25rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.4rem;">
                    Regresiones Protegidas
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: {{ $impB->regresiones_protegidas > 0 ? '#38bdf8' : '#94a3b8' }};">
                    {{ $impB->regresiones_protegidas }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                    Calificaciones preservadas ante archivos desactualizados
                </div>
            </div>

            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 14px; padding: 1.25rem;">
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.4rem;">
                    Velocidad de Carga
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: #cbd5e1;">
                    {{ $impB->velocidad }} <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">reg/s</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                    Eficiencia del motor de procesamiento
                </div>
            </div>

        </div>

        <div style="margin-top: 1.75rem; padding: 1rem 1.25rem; background: rgba(57,169,0,0.06); border: 1px solid rgba(57,169,0,0.2); border-radius: 12px; font-size: 0.85rem; color: #cbd5e1; display: flex; align-items: center; gap: 12px;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.2rem; color: var(--primary);"></i>
            <div>
                La trazabilidad confirma que entre la importación <strong>#{{ $impA->id }}</strong> y la <strong>#{{ $impB->id }}</strong> la cohorte avanzó exitosamente con integridad académica asegurada.
            </div>
        </div>
    </div>

</div>
@endsection
