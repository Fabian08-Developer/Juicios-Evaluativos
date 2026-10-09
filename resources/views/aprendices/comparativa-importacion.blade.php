@extends('layouts.app')

@section('title', 'Comparativa y Diagnóstico Pre-Importación')

@section('content')
<div style="max-width: 1200px; margin: 0 auto;">

    <!-- Breadcrumb / Volver -->
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('aprendices.upload') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-arrow-left"></i> Cancelar y volver al formulario de carga
        </a>
    </div>

    <!-- Header Principal -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.5rem;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.3rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #fff;">
                    Diagnóstico Comparativo de Importación
                </h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.2rem;">
                    El sistema analizó el archivo <strong style="color: #fff;">{{ $nombreArchivo }}</strong> contra la base de datos de la <strong style="color: var(--primary);">Ficha {{ $analisis['numero_ficha'] }}</strong> antes de aplicar cualquier cambio.
                </p>
            </div>
        </div>

        <!-- Badges de Metadatos y Fechas -->
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem;">
            <span style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #cbd5e1; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-calendar-day" style="color: var(--primary);"></i> Fecha Reporte Archivo: <strong>{{ $analisis['fecha_reporte'] ? \Carbon\Carbon::parse($analisis['fecha_reporte'])->format('d/m/Y') : 'No identificada' }}</strong>
            </span>
            @if($analisis['fecha_ultima_importacion'])
            <span style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #cbd5e1; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-clock-rotate-left" style="color: #38bdf8;"></i> Última Importación en BD: <strong>{{ \Carbon\Carbon::parse($analisis['fecha_ultima_importacion'])->format('d/m/Y') }}</strong>
            </span>
            @endif
            @if($analisis['es_fecha_anterior'])
            <span style="background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #fbbf24; display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
                <i class="fa-solid fa-triangle-exclamation"></i> Archivo con fecha anterior a la última actualización
            </span>
            @endif
            <span style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #cbd5e1; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-check-double" style="color: #a855f7;"></i> Balance Aprobados: <strong style="color: var(--primary);">{{ $analisis['total_aprobados_excel'] }} en archivo</strong> vs <strong style="color: #fff;">{{ $analisis['total_aprobados_bd'] }} en BD</strong>
            </span>
        </div>
    </div>

    @if(!empty($analisis['tiene_conflictos_ficha']))
    <!-- Alerta de Conflicto de Pertenencia de Ficha -->
    <div class="card" style="margin-bottom: 1.5rem; border-color: rgba(245,158,11,0.4); background: rgba(245,158,11,0.06); padding: 1.25rem;">
        <div style="display: flex; align-items: flex-start; gap: 1rem;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.5rem; color: #fbbf24; margin-top: 0.2rem;"></i>
            <div>
                <h4 style="margin: 0 0 0.25rem 0; color: #fbbf24; font-size: 0.95rem; font-weight: 700;">
                    Protección de Ficha Activa: Conflicto de Asignación Detectado
                </h4>
                <p style="margin: 0; color: #cbd5e1; font-size: 0.84rem; line-height: 1.4;">
                    {{ count($analisis['conflictos_ficha']) }} aprendiz(ces) en este archivo ya pertenecen a la Ficha <strong>{{ implode(', ', $analisis['fichas_previas']) }}</strong>. Para proteger la ficha de origen y evitar dejarla vacía, el sistema <strong>conservará su ficha actual intacta</strong> y solo actualizará sus calificaciones, a menos que autorices explícitamente el traslado.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Banner de Alerta Crítica -->
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(239,68,68,0.4); background: linear-gradient(135deg, rgba(239,68,68,0.08), rgba(15,23,42,0.6)); padding: 1.5rem;">
        <div style="display: flex; align-items: flex-start; gap: 1rem;">
            <i class="fa-solid fa-shield-halved" style="font-size: 2rem; color: #ef4444; margin-top: 0.25rem;"></i>
            <div>
                <h4 style="margin: 0 0 0.5rem 0; color: #fca5a5; font-size: 1.1rem; font-weight: 800;">
                    Posible archivo anterior o reporte incompleto detectado
                </h4>
                <p style="margin: 0; color: #cbd5e1; font-size: 0.9rem; line-height: 1.5;">
                    {{ $analisis['mensaje'] }}
                </p>
                <div style="margin-top: 0.8rem; font-size: 0.82rem; color: #94a3b8;">
                    <i class="fa-solid fa-circle-info"></i> Si este archivo fue descargado en una fecha anterior o no contiene todas las notas recientes, sobrescribirlo a ciegas borraría los juicios que los aprendices ya habían aprobado en el sistema.
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas Comparativas (KPIs) -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- 🚨 Regresiones Detectadas -->
        <div class="card stat-card" style="border-color: rgba(239,68,68,0.3); background: rgba(239,68,68,0.05);">
            <div class="icon-box" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="fa-solid fa-arrow-down-long"></i>
            </div>
            <div>
                <div class="stat-value" style="color: #ef4444;">{{ $analisis['conteo_regresiones'] }}</div>
                <div class="stat-label" style="font-size: 0.7rem;">Juicios en Regresión</div>
                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">(Aprobado en BD ➔ Pendiente en archivo)</div>
            </div>
        </div>

        <!-- 🟢 Nuevos Aprobados -->
        <div class="card stat-card" style="border-color: rgba(57,169,0,0.3); background: rgba(57,169,0,0.05);">
            <div class="icon-box" style="background: rgba(57,169,0,0.15); color: var(--primary);">
                <i class="fa-solid fa-arrow-up-long"></i>
            </div>
            <div>
                <div class="stat-value" style="color: var(--primary);">+{{ $analisis['conteo_nuevos_aprobados'] }}</div>
                <div class="stat-label" style="font-size: 0.7rem;">Nuevos Aprobados</div>
                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">(Avances académicos detectados)</div>
            </div>
        </div>

        <!-- 👥 Aprendices en Archivo vs BD -->
        <div class="card stat-card">
            <div class="icon-box" style="background: rgba(14,165,233,0.15); color: #0ea5e9;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="stat-value">{{ $analisis['aprendices_excel_count'] }} <span style="font-size: 1rem; color: var(--text-muted); font-weight: 500;">/ {{ $analisis['aprendices_bd_count'] }}</span></div>
                <div class="stat-label" style="font-size: 0.7rem;">Aprendices (Archivo / BD)</div>
                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">
                    @if($analisis['conteo_aprendices_ausentes'] > 0)
                        <span style="color: #f59e0b;">{{ $analisis['conteo_aprendices_ausentes'] }} ausente(s) en archivo</span>
                    @else
                        <span>Grupo completo concordante</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- ℹ️ Sin Cambios -->
        <div class="card stat-card">
            <div class="icon-box" style="background: rgba(255,255,255,0.05); color: #94a3b8;">
                <i class="fa-solid fa-equals"></i>
            </div>
            <div>
                <div class="stat-value">{{ $analisis['conteo_sin_cambios'] }}</div>
                <div class="stat-label" style="font-size: 0.7rem;">Juicios Sin Cambios</div>
                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">Concordancia total</div>
            </div>
        </div>

    </div>

    <!-- Detalle de Juicios en Riesgo de Regresión -->
    @if(count($analisis['regresiones']) > 0)
    <div class="card" style="margin-bottom: 2.5rem; padding: 1.5rem;">
        <h3 style="margin-bottom: 0.5rem; color: #fca5a5; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-list-check"></i> Detalle de Juicios en Riesgo ({{ count($analisis['regresiones']) }})
        </h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">
            Las siguientes calificaciones figuran como <strong>Aprobadas</strong> en el sistema, pero el archivo subido indica que están <strong>Pendientes / Por Evaluar</strong>:
        </p>

        <div style="max-height: 280px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.3); text-align: left; color: var(--text-muted); border-bottom: 1px solid rgba(255,255,255,0.08);">
                        <th style="padding: 0.75rem 1rem;">APRENDIZ</th>
                        <th style="padding: 0.75rem 1rem;">COMPETENCIA</th>
                        <th style="padding: 0.75rem 1rem;">RESULTADO (RAP)</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">ESTADO EN BD</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">EN ESTE ARCHIVO</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analisis['regresiones'] as $reg)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); background: rgba(239,68,68,0.02);">
                        <td style="padding: 0.65rem 1rem; font-weight: 700; color: #f1f5f9;">
                            {{ $reg['aprendiz'] }}
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: normal;">CC {{ $reg['documento'] }}</div>
                        </td>
                        <td style="padding: 0.65rem 1rem; color: var(--accent);">
                            {{ $reg['competencia'] }}
                        </td>
                        <td style="padding: 0.65rem 1rem; color: #cbd5e1;">
                            <strong>{{ $reg['resultado'] }}</strong> — {{ $reg['nombre_rap'] }}
                        </td>
                        <td style="padding: 0.65rem 1rem; text-align: center;">
                            <span style="background: rgba(57,169,0,0.15); color: var(--primary); padding: 0.2rem 0.55rem; border-radius: 6px; font-weight: 800; font-size: 0.75rem;">
                                ✓ APROBADO
                            </span>
                        </td>
                        <td style="padding: 0.65rem 1rem; text-align: center;">
                            <span style="background: rgba(239,68,68,0.15); color: #fca5a5; padding: 0.2rem 0.55rem; border-radius: 6px; font-weight: 800; font-size: 0.75rem;">
                                ⏳ POR EVALUAR
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Panel de Decisión y Resolución -->
    <div class="card" style="padding: 2rem; border-color: rgba(57,169,0,0.3); background: rgba(15,23,42,0.9);">
        <h3 style="margin-bottom: 0.5rem; font-size: 1.25rem; font-weight: 800; color: #fff;">
            ¿Cómo deseas proceder con esta importación?
        </h3>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 2rem;">
            Selecciona la política de aplicación según la validez del archivo subido:
        </p>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            
            <!-- OPCIÓN 1: PRESERVAR (RECOMENDADA) -->
            <form method="POST" action="{{ route('aprendices.import.confirmar') }}" style="margin: 0;">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="accion" value="preservar">

                <div style="border: 2px solid var(--primary); background: rgba(57,169,0,0.06); border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.4rem;">
                            <span style="background: var(--primary); color: #000; font-size: 0.7rem; font-weight: 900; padding: 0.15rem 0.6rem; border-radius: 6px; text-transform: uppercase;">Recomendado</span>
                            <h4 style="margin: 0; color: #fff; font-size: 1.05rem; font-weight: 700;">Preservar Aprobados e Incorporar Nuevos Logros</h4>
                        </div>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.85rem; line-height: 1.4;">
                            El sistema sumará los <strong>+{{ $analisis['conteo_nuevos_aprobados'] }} nuevos juicios aprobados</strong> y actualizará los aprendices, pero <strong>bloqueará la degradación</strong> de los {{ $analisis['conteo_regresiones'] }} juicios que ya estaban aprobados en el sistema.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 0.95rem; font-weight: 800; white-space: nowrap;">
                        <i class="fa-solid fa-shield-check"></i> Aplicar Protegiendo Aprobados
                    </button>
                </div>
            </form>

            @if(!empty($analisis['tiene_conflictos_ficha']))
            <!-- OPCIÓN TRASLADAR: REASIGNAR FICHA EXPLÍCITAMENTE -->
            <form method="POST" action="{{ route('aprendices.import.confirmar') }}" style="margin: 0;" onsubmit="return confirm('¿Confirmas que deseas trasladar a estos aprendices a la Ficha {{ $analisis['numero_ficha'] }}?');">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="accion" value="trasladar">

                <div style="border: 1px solid rgba(14,165,233,0.35); background: rgba(14,165,233,0.05); border-radius: 16px; padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.3rem;">
                            <span style="background: rgba(14,165,233,0.2); color: #38bdf8; font-size: 0.7rem; font-weight: 800; padding: 0.15rem 0.6rem; border-radius: 6px; text-transform: uppercase;">Traslado</span>
                            <h4 style="margin: 0; color: #38bdf8; font-size: 0.95rem; font-weight: 700;">Autorizar Traslado a la Ficha {{ $analisis['numero_ficha'] }}</h4>
                        </div>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.82rem; line-height: 1.4;">
                            Mueve formalmente a los {{ count($analisis['conflictos_ficha']) }} aprendices a la Ficha {{ $analisis['numero_ficha'] }} e incorpora sus notas sin borrar juicios aprobados.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-outline" style="white-space: nowrap; border-color: rgba(14,165,233,0.4); color: #38bdf8; padding: 0.75rem 1.25rem; font-size: 0.88rem; font-weight: 700;">
                        <i class="fa-solid fa-right-left"></i> Reasignar Ficha
                    </button>
                </div>
            </form>
            @endif

            <!-- OPCIÓN 2: CANCELAR -->
            <form method="POST" action="{{ route('aprendices.import.confirmar') }}" style="margin: 0;">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="accion" value="cancelar">

                <div style="border: 1px solid var(--glass-border); background: rgba(255,255,255,0.02); border-radius: 16px; padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px;">
                        <h4 style="margin: 0 0 0.3rem 0; color: #f1f5f9; font-size: 0.95rem; font-weight: 700;">Cancelar Importación</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.82rem;">
                            No modificar ningún dato en la base de datos. Usa esta opción si subiste por error un archivo antiguo o incorrecto.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-outline" style="white-space: nowrap;">
                        <i class="fa-solid fa-xmark"></i> Cancelar y Descartar
                    </button>
                </div>
            </form>

            <!-- OPCIÓN 3: FORZAR SOBRESCRITURA (PELIGROSA) -->
            <form method="POST" action="{{ route('aprendices.import.confirmar') }}" style="margin: 0;" onsubmit="return confirm('⚠️ ATENCIÓN CRÍTICA: Esta acción SOBRESCRIBIRÁ todas las calificaciones y convertirá los juicios aprobados en pendientes si así figura en el Excel. ¿Estás absolutamente seguro de forzar esta acción?');">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="accion" value="forzar">

                <div style="border: 1px dashed rgba(239,68,68,0.3); background: rgba(239,68,68,0.02); border-radius: 16px; padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px;">
                        <h4 style="margin: 0 0 0.3rem 0; color: #fca5a5; font-size: 0.95rem; font-weight: 700;">Sobrescritura Completa Forzada</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.82rem;">
                            Sobrescribirá toda la ficha con los datos del Excel sin excepción (incluyendo degradar calificaciones a pendientes).
                        </p>
                    </div>
                    <button type="submit" class="btn btn-danger" style="white-space: nowrap; font-size: 0.85rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Forzar Sobrescritura
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
