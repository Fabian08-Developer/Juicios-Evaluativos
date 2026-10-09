@extends('layouts.app')

@section('title', 'Historial y Trazabilidad de Importaciones')

@section('content')

<!-- Header Principal -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #fff;">Historial y Trazabilidad de Importaciones</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
            Línea de tiempo cronológica, auditoría técnica y evolución de reportes de Sofia Plus
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('aprendices.upload') }}" class="btn btn-primary">
            <i class="fa-solid fa-cloud-arrow-up"></i> Nueva Importación
        </a>
    </div>
</div>

<!-- Barra de Filtro por Ficha -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem 1.5rem;">
    <form method="GET" action="{{ route('importaciones.index') }}" id="filtro-ficha-form" style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 280px;">
            <label for="ficha" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                <i class="fa-solid fa-filter"></i> Filtrar por Ficha:
            </label>
            <select name="ficha" id="ficha" class="form-control" style="padding: 0.65rem 1rem; font-size: 0.9rem;" onchange="this.form.submit()">
                <option value="">— Todas las Fichas (Línea de tiempo global) —</option>
                @foreach($fichas as $f)
                    <option value="{{ $f->Id_Ficha }}" {{ $fichaFiltro == $f->Id_Ficha ? 'selected' : '' }}>
                        Ficha {{ $f->Id_Ficha }} — {{ $f->programa->Nombre ?? 'Sin programa' }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($fichaFiltro)
        <a href="{{ route('importaciones.index') }}" class="btn btn-outline" style="padding: 0.65rem 1rem; font-size: 0.85rem;" title="Restablecer a vista global">
            <i class="fa-solid fa-rotate-left"></i> Ver Todo
        </a>
        @endif
    </form>
</div>

<!-- Estadísticas Rápidas (KPIs) -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background: rgba(57,169,0,0.12); color: var(--primary); width:46px; height:46px; font-size:1.25rem;">
            <i class="fa-solid fa-timeline"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.6rem;">{{ $totalImportaciones }}</div>
            <div class="stat-label" style="font-size:0.72rem;">Reportes Registrados</div>
        </div>
    </div>

    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background: rgba(14,165,233,0.12); color:#0ea5e9; width:46px; height:46px; font-size:1.25rem;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.6rem;">{{ number_format($totalAprendicesProcesados) }}</div>
            <div class="stat-label" style="font-size:0.72rem;">Registros Procesados</div>
        </div>
    </div>

    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background: rgba(168,85,247,0.12); color:#a855f7; width:46px; height:46px; font-size:1.25rem;">
            <i class="fa-solid fa-shield-check"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.6rem;">{{ $tasaExito }}%</div>
            <div class="stat-label" style="font-size:0.72rem;">Tasa de Éxito</div>
        </div>
    </div>

    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background: rgba(245,158,11,0.12); color:#f59e0b; width:46px; height:46px; font-size:1.25rem;">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.2rem; margin-top: 0.2rem;">
                {{ $ultimaImportacion ? $ultimaImportacion->created_at->diffForHumans() : 'Sin registros' }}
            </div>
            <div class="stat-label" style="font-size:0.72rem;">Última Actualización</div>
        </div>
    </div>
</div>

<<<<<<< Updated upstream
<!-- Línea de Tiempo -->
@if($importaciones->count() > 0)
<div class="card" style="padding: 2rem; margin-bottom: 2rem;">
    <h3 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-timeline" style="color: var(--primary);"></i>
        Línea de Tiempo
    </h3>
    <div style="position: relative; padding-left: 30px;">
        <!-- Línea vertical -->
        <div style="position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, var(--primary), transparent);"></div>

        @foreach($importaciones->take(5) as $imp)
        <div style="position: relative; margin-bottom: 1.5rem; animation: fadeInLeft 0.5s ease {{ $loop->index * 0.1 }}s both;">
            <!-- Punto en la línea -->
            <div style="position: absolute; left: -25px; top: 6px; width: 12px; height: 12px; border-radius: 50%;
                        background: {{ $imp->estado_visual['dot_bg'] }};
                        border: 2px solid {{ $imp->estado_visual['dot_border'] }};
                        box-shadow: 0 0 8px {{ $imp->estado_visual['dot_glow'] }};"></div>

            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.25rem 1.5rem; transition: all 0.3s;"
                 onmouseover="this.style.borderColor='rgba(57,169,0,0.3)'; this.style.background='rgba(57,169,0,0.03)'"
                 onmouseout="this.style.borderColor='var(--glass-border)'; this.style.background='rgba(255,255,255,0.03)'">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.4rem;">
                            <i class="fa-solid fa-file-excel" style="color: var(--primary);"></i>
                            <span style="font-weight: 700; color: #f1f5f9; font-size: 0.95rem;">{{ $imp->nombre_archivo }}</span>
                        </div>
                        <div style="display: flex; gap: 1.5rem; font-size: 0.8rem; color: var(--text-muted);">
                            @if($imp->id_ficha)
                            <span><i class="fa-solid fa-folder"></i> Ficha {{ $imp->id_ficha }}</span>
                            @endif
                            <span><i class="fa-solid fa-users"></i> {{ $imp->aprendices_procesados }} aprendices</span>
                            <span><i class="fa-solid fa-stopwatch"></i> {{ $imp->duracion_segundos }}s</span>
                        </div>
                        @if($imp->detalle)
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted); font-style: italic;">{{ $imp->detalle }}</div>
                        @endif
                    </div>
                    <div style="text-align: right; min-width: 150px;">
                        <span style="display: inline-block; padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700;
                                     background: {{ $imp->estado_visual['badge_bg'] }};
                                     color: {{ $imp->estado_visual['badge_color'] }};
                                     border: 1px solid {{ $imp->estado_visual['badge_border'] }};">
                            {{ $imp->estado_visual['label'] }}
                        </span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                            {{ $imp->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); opacity: 0.7;">
                            {{ $imp->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
=======
<!-- Herramienta: Comparador de Hitos Temporales (Time Machine) -->
@if($importacionesComparador->count() >= 2)
<div class="card" style="margin-bottom: 2.5rem; padding: 1.75rem; border-color: rgba(57,169,0,0.3); background: linear-gradient(135deg, rgba(57,169,0,0.04), rgba(15,23,42,0.8));">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(57,169,0,0.15); border: 1px solid rgba(57,169,0,0.3); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                <i class="fa-solid fa-code-compare"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #fff;">Comparador de Evolución Temporal</h3>
                <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted);">
                    Selecciona dos momentos de la cohorte para medir el avance académico neto entre ambos reportes
                </p>
>>>>>>> Stashed changes
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('importaciones.comparar') }}" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1.25rem; align-items: end;">
        <div>
            <label for="id_inicial" style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);margin-bottom:0.4rem;text-transform:uppercase;">
                Hito 1 (Reporte Anterior)
            </label>
            <select name="id_inicial" id="id_inicial" class="form-control" style="padding: 0.65rem 0.85rem; font-size: 0.85rem;" required>
                @foreach($importacionesComparador as $item)
                    <option value="{{ $item->id }}" {{ $loop->last ? 'selected' : '' }}>
                        #{{ $item->id }} — {{ $item->nombre_archivo }} ({{ $item->created_at->format('d/m/Y') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="id_final" style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);margin-bottom:0.4rem;text-transform:uppercase;">
                Hito 2 (Reporte Posterior)
            </label>
            <select name="id_final" id="id_final" class="form-control" style="padding: 0.65rem 0.85rem; font-size: 0.85rem;" required>
                @foreach($importacionesComparador as $item)
                    <option value="{{ $item->id }}" {{ $loop->first ? 'selected' : '' }}>
                        #{{ $item->id }} — {{ $item->nombre_archivo }} ({{ $item->created_at->format('d/m/Y') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.7rem 1.5rem; font-size: 0.9rem; font-weight: 700; white-space: nowrap;">
                <i class="fa-solid fa-chart-line"></i> Comparar Evolución
            </button>
        </div>
    </form>
</div>
@endif

<<<<<<< Updated upstream
<!-- Tabla Completa -->
<div class="card">
    <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-table-list" style="color: var(--accent);"></i>
        Registro Completo
    </h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
            <thead>
                <tr style="text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 1rem;">#</th>
                    <th style="padding: 1rem;">ARCHIVO</th>
                    <th style="padding: 1rem;">FICHA</th>
                    <th style="padding: 1rem;">APRENDICES</th>
                    <th style="padding: 1rem;">DURACIÓN</th>
                    <th style="padding: 1rem;">ESTADO</th>
                    <th style="padding: 1rem;">CAMBIOS</th>
                    <th style="padding: 1rem;">FECHA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($importaciones as $imp)
                <tr style="background: rgba(255,255,255,0.02); transition: background 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.05)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                    <td style="padding: 1rem; border-radius: 12px 0 0 12px; color: var(--text-muted); font-size: 0.8rem;">
                        #{{ $imp->id }}
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-file-excel" style="color: var(--primary); font-size: 1.1rem;"></i>
                            <span style="font-weight: 600; color: #f1f5f9; font-size: 0.9rem;">{{ $imp->nombre_archivo }}</span>
                        </div>
                    </td>
                    <td style="padding: 1rem;">
                        @if($imp->id_ficha && isset($fichasExistentes[$imp->id_ficha]))
                            <a href="{{ route('fichas.historial', $imp->id_ficha) }}" title="Línea de tiempo de la ficha" style="font-weight: 700; color: var(--primary); text-decoration: none;">{{ $imp->id_ficha }} <i class="fa-solid fa-chart-line" style="font-size: 0.75rem;"></i></a>
                        @elseif($imp->id_ficha)
                            <span style="font-weight: 700; color: var(--primary);">{{ $imp->id_ficha }}</span>
                        @else
                            <span style="color: var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td style="padding: 1rem;">
                        <span style="font-weight: 700; font-size: 1.1rem; color: #f1f5f9;">{{ $imp->aprendices_procesados }}</span>
                    </td>
                    <td style="padding: 1rem; color: var(--text-muted);">
                        <i class="fa-solid fa-stopwatch"></i> {{ $imp->duracion_segundos }}s
                    </td>
                    <td style="padding: 1rem;">
                        <span style="padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700;
                                     background: {{ $imp->estado_visual['badge_bg'] }};
                                     color: {{ $imp->estado_visual['badge_color'] }};
                                     border: 1px solid {{ $imp->estado_visual['badge_border'] }};">
                            {{ $imp->estado_visual['label'] }}
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        @include('importaciones._insignias', ['imp' => $imp])
                    </td>
                    <td style="padding: 1rem; border-radius: 0 12px 12px 0; color: var(--text-muted); font-size: 0.85rem; white-space: nowrap;">
                        {{ $imp->created_at->format('d/m/Y H:i') }}
                        <div><a href="{{ route('importaciones.show', $imp) }}" style="color: var(--accent); font-size: 0.75rem;">Ver cambios →</a></div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
                        No hay importaciones registradas todavía.
                        <div style="margin-top: 1rem;">
                            <a href="{{ route('aprendices.upload') }}" class="btn btn-primary" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Hacer primera importación
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
=======
<!-- Línea de Tiempo Dinámica -->
@if($importaciones->count() > 0)
<div class="card" style="padding: 2rem; margin-bottom: 2.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; font-size: 1.25rem; font-weight: 800; color: #fff;">
            <i class="fa-solid fa-timeline" style="color: var(--primary);"></i>
            Línea de Tiempo de Procesamiento
            @if($fichaFiltro)
                <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted); margin-left: 0.5rem;">
                    (Ficha {{ $fichaFiltro }})
                </span>
            @endif
        </h3>
        <span style="font-size: 0.8rem; color: var(--text-muted);">
            Mostrando {{ $importaciones->count() }} hito(s) de importación
        </span>
>>>>>>> Stashed changes
    </div>

    <div style="position: relative; padding-left: 32px;">
        <!-- Línea vertical conectora -->
        <div style="position: absolute; left: 13px; top: 12px; bottom: 20px; width: 2px; background: linear-gradient(to bottom, var(--primary), rgba(57,169,0,0.1));"></div>

        @foreach($importaciones as $imp)
        <div style="position: relative; margin-bottom: 2rem; animation: fadeInLeft 0.4s ease {{ $loop->index * 0.05 }}s both;">
            
            <!-- Nodo / Indicador en la línea -->
            <div style="position: absolute; left: -26px; top: 18px; width: 16px; height: 16px; border-radius: 50%;
                        background: {{ $imp->estado === 'exitoso' ? 'var(--primary)' : ($imp->estado === 'con_advertencias' ? '#f59e0b' : '#ef4444') }};
                        border: 3px solid rgba(15,23,42,0.9);
                        box-shadow: 0 0 10px {{ $imp->estado === 'exitoso' ? 'var(--primary-glow)' : 'rgba(239,68,68,0.5)' }};"></div>

            <!-- Contenido de la Tarjeta del Hito -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.5rem; transition: all 0.25s;"
                 onmouseover="this.style.borderColor='rgba(57,169,0,0.4)'; this.style.background='rgba(255,255,255,0.04)'"
                 onmouseout="this.style.borderColor='var(--glass-border)'; this.style.background='rgba(255,255,255,0.02)'">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; flex-wrap: wrap;">
                    
                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.5rem; flex-wrap: wrap;">
                            <i class="fa-solid fa-file-excel" style="color: var(--primary); font-size: 1.2rem;"></i>
                            <span style="font-weight: 800; color: #fff; font-size: 1.05rem;">{{ $imp->nombre_archivo }}</span>
                            
                            @if($imp->id_ficha)
                            <a href="{{ route('importaciones.index', ['ficha' => $imp->id_ficha]) }}" style="text-decoration: none;" title="Filtrar por esta ficha">
                                <span style="background: rgba(57,169,0,0.12); color: var(--primary); border: 1px solid rgba(57,169,0,0.25); border-radius: 6px; padding: 0.15rem 0.55rem; font-size: 0.72rem; font-weight: 800;">
                                    Ficha {{ $imp->id_ficha }}
                                </span>
                            </a>
                            @endif

                            @if($imp->ficha && $imp->ficha->programa)
                            <span style="color: var(--text-muted); font-size: 0.8rem;">
                                • {{ $imp->ficha->programa->Nombre }}
                            </span>
                            @endif
                        </div>

                        <!-- Badges de Avance y Métricas -->
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.75rem; font-size: 0.8rem;">
                            <span style="color: #cbd5e1; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fa-solid fa-users" style="color: #0ea5e9;"></i> {{ $imp->aprendices_procesados }} aprendices
                            </span>
                            <span style="color: #cbd5e1; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fa-solid fa-stopwatch" style="color: #a855f7;"></i> {{ $imp->duracion_segundos }}s ({{ $imp->velocidad }} reg/s)
                            </span>

                            @if($imp->nuevos_aprobados > 0)
                            <span style="background: rgba(57,169,0,0.15); color: var(--primary); border: 1px solid rgba(57,169,0,0.3); padding: 0.15rem 0.55rem; border-radius: 6px; font-weight: 800;">
                                <i class="fa-solid fa-arrow-trend-up"></i> +{{ $imp->nuevos_aprobados }} nuevos aprobados
                            </span>
                            @endif

                            @if($imp->regresiones_protegidas > 0)
                            <span style="background: rgba(56,189,248,0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3); padding: 0.15rem 0.55rem; border-radius: 6px; font-weight: 800;">
                                <i class="fa-solid fa-shield-halved"></i> 🛡️ {{ $imp->regresiones_protegidas }} juicios protegidos
                            </span>
                            @endif
                        </div>

                        @if($imp->detalle)
                        <div style="margin-top: 0.75rem; font-size: 0.82rem; color: #94a3b8; line-height: 1.4; background: rgba(0,0,0,0.15); padding: 0.5rem 0.75rem; border-radius: 8px;">
                            {{ $imp->detalle }}
                        </div>
                        @endif
                    </div>

                    <!-- Estado y Acciones del Hito -->
                    <div style="text-align: right; min-width: 140px; display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                        <span style="display: inline-block; padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase;
                                     background: {{ $imp->estado === 'exitoso' ? 'rgba(57,169,0,0.15)' : ($imp->estado === 'con_advertencias' ? 'rgba(245,158,11,0.15)' : 'rgba(239,68,68,0.15)') }};
                                     color: {{ $imp->estado === 'exitoso' ? 'var(--primary)' : ($imp->estado === 'con_advertencias' ? '#fbbf24' : '#fca5a5') }};
                                     border: 1px solid {{ $imp->estado === 'exitoso' ? 'rgba(57,169,0,0.3)' : ($imp->estado === 'con_advertencias' ? 'rgba(245,158,11,0.3)' : 'rgba(239,68,68,0.3)') }};">
                            {{ $imp->estado === 'exitoso' ? '✓ Exitoso' : ($imp->estado === 'con_advertencias' ? '⚠ Advertencias' : '✗ Error') }}
                        </span>

                        <div style="font-size: 0.82rem; color: #f1f5f9; font-weight: 600;">
                            {{ $imp->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                            {{ $imp->created_at->diffForHumans() }}
                        </div>

                        <button type="button" onclick="abrirModalAuditoria({{ $imp->id }})" class="btn btn-outline" style="margin-top: 0.4rem; padding: 0.35rem 0.75rem; font-size: 0.75rem;" title="Ver auditoría técnica de esta importación">
                            <i class="fa-solid fa-magnifying-glass-chart"></i> Auditar
                        </button>
                    </div>

                </div>

            </div>
        </div>
        @endforeach

    </div>

    <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
        {{ $importaciones->links() }}
    </div>
</div>
@else
<div class="card" style="padding: 4rem; text-align: center; color: var(--text-muted); margin-bottom: 2.5rem;">
    <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.25;"></i>
    <h4 style="color: #fff; margin-bottom: 0.5rem;">No hay importaciones registradas</h4>
    <p style="font-size: 0.85rem; max-width: 420px; margin: 0 auto 1.5rem;">
        Aún no se han procesado reportes en el sistema para esta selección.
    </p>
    <a href="{{ route('aprendices.upload') }}" class="btn btn-primary" style="font-size: 0.85rem;">
        <i class="fa-solid fa-cloud-arrow-up"></i> Realizar Primera Importación
    </a>
</div>
@endif

<!-- Modal de Auditoría Técnica de la Importación -->
<div id="modal-auditoria" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 90%; max-width: 600px; padding: 2rem; background: rgba(15,23,42,0.95); border-color: rgba(57,169,0,0.3); box-shadow: 0 20px 40px rgba(0,0,0,0.5); animation: zoomIn 0.25s ease;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(57,169,0,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; color: #fff;" id="modal-titulo">Auditoría de Importación</h3>
                    <span style="font-size: 0.75rem; color: var(--text-muted);" id="modal-subtitulo">ID #---</span>
                </div>
            </div>
            <button type="button" onclick="cerrarModalAuditoria()" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer; padding: 0.25rem 0.5rem;"
                    onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="modal-cuerpo" style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem;">
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: var(--text-muted);">Archivo Original:</span>
                <strong style="color: #fff;" id="modal-archivo">---</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: var(--text-muted);">Ficha Asignada:</span>
                <strong style="color: var(--primary);" id="modal-ficha">---</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: var(--text-muted);">Programa:</span>
                <span style="color: #cbd5e1; text-align: right; max-width: 320px;" id="modal-programa">---</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: var(--text-muted);">Estado del Proceso:</span>
                <span id="modal-estado" style="font-weight: 700;">---</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: var(--text-muted);">Registros Procesados:</span>
                <strong style="color: #fff;" id="modal-procesados">---</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: var(--text-muted);">Duración / Velocidad:</span>
                <span style="color: #cbd5e1;" id="modal-velocidad">---</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: var(--text-muted);">Fecha y Hora de Carga:</span>
                <span style="color: #cbd5e1;" id="modal-fecha">---</span>
            </div>

            <div style="margin-top: 0.5rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.4rem;">
                    Registro Detallado del Motor:
                </label>
                <div style="background: rgba(0,0,0,0.3); border-radius: 8px; padding: 0.75rem; font-size: 0.8rem; color: #cbd5e1; line-height: 1.4;" id="modal-detalle">
                    ---
                </div>
            </div>
        </div>

        <div style="margin-top: 1.75rem; text-align: right;">
            <button type="button" onclick="cerrarModalAuditoria()" class="btn btn-outline" style="padding: 0.6rem 1.5rem;">
                Cerrar Auditoría
            </button>
        </div>
    </div>
</div>

<style>
@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-15px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to   { opacity: 1; transform: scale(1); }
}
</style>

<script>
function abrirModalAuditoria(id) {
    const modal = document.getElementById('modal-auditoria');
    modal.style.display = 'flex';

    document.getElementById('modal-archivo').textContent = 'Cargando...';
    document.getElementById('modal-ficha').textContent = '...';
    document.getElementById('modal-programa').textContent = '...';
    document.getElementById('modal-procesados').textContent = '...';
    document.getElementById('modal-velocidad').textContent = '...';
    document.getElementById('modal-fecha').textContent = '...';
    document.getElementById('modal-detalle').textContent = 'Consultando registro de auditoría...';

    fetch(`/importaciones/${id}/json`)
        .then(response => {
            if (!response.ok) throw new Error('Error al consultar auditoría');
            return response.json();
        })
        .then(data => {
            document.getElementById('modal-subtitulo').textContent = `Auditoría ID #${data.id}`;
            document.getElementById('modal-archivo').textContent = data.nombre_archivo;
            document.getElementById('modal-ficha').textContent = `Ficha ${data.id_ficha}`;
            document.getElementById('modal-programa').textContent = data.programa;
            document.getElementById('modal-procesados').textContent = `${data.aprendices_procesados} registros`;
            document.getElementById('modal-velocidad').textContent = `${data.duracion_segundos}s (${data.velocidad} reg/s)`;
            document.getElementById('modal-fecha').textContent = `${data.fecha_formateada} (${data.hace_tiempo})`;
            document.getElementById('modal-detalle').textContent = data.detalle;

            const estadoEl = document.getElementById('modal-estado');
            estadoEl.textContent = data.estado === 'exitoso' ? '✓ Exitoso' : (data.estado === 'con_advertencias' ? '⚠ Con Advertencias' : '✗ Error');
            estadoEl.style.color = data.estado === 'exitoso' ? 'var(--primary)' : (data.estado === 'con_advertencias' ? '#fbbf24' : '#fca5a5');
        })
        .catch(err => {
            document.getElementById('modal-detalle').textContent = 'No se pudo cargar la información de auditoría.';
        });
}

function cerrarModalAuditoria() {
    document.getElementById('modal-auditoria').style.display = 'none';
}

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalAuditoria();
});
</script>

@endsection
