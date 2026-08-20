@extends('layouts.app')

@section('title', 'Bandeja de Remisiones y Alertas')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-bullhorn" style="color: #ef4444;"></i>
            Bandeja de Remisiones y Alertas a Bienestar
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.3rem;">
            Trazabilidad y seguimiento a los aprendices escalados a Bienestar al Aprendiz y Coordinación
        </p>
    </div>
    
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('acciones.diagnostico') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Nueva Alerta Masiva
        </a>
    </div>
</div>

<!-- ===================== KPIs DE SEGUIMIENTO ===================== -->
<div class="grid">
    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(14,165,233,0.1); color: #0ea5e9;">
            <i class="fa-solid fa-folder-open"></i>
        </div>
        <div>
            <div class="stat-value" data-counter="{{ $totalCasos }}">0</div>
            <div class="stat-label">Total Remisiones</div>
        </div>
    </div>

    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(239,68,68,0.1); color: #ef4444;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <div class="stat-value" style="color: #ef4444;" data-counter="{{ $pendientesCount }}">0</div>
            <div class="stat-label">Pendientes de Citación</div>
        </div>
    </div>

    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
            <i class="fa-solid fa-user-clock"></i>
        </div>
        <div>
            <div class="stat-value" style="color: #f59e0b;" data-counter="{{ $seguimientoCount }}">0</div>
            <div class="stat-label">En Acompañamiento</div>
        </div>
    </div>

    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(57,169,0,0.1); color: var(--primary);">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="stat-value" style="color: var(--primary);" data-counter="{{ $atendidosCount }}">0</div>
            <div class="stat-label">Atendidos / Resueltos</div>
        </div>
    </div>
</div>

<!-- ===================== FILTROS ===================== -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('remisiones.index') }}" style="display: flex; gap: 1.25rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <label for="ficha" class="stat-label" style="display: block; margin-bottom: 0.5rem;">Filtrar por Ficha</label>
            <select name="ficha" id="ficha" class="form-control" onchange="this.form.submit()">
                <option value="">Todas las Fichas</option>
                @foreach($fichas as $f)
                    <option value="{{ $f->Id_Ficha }}" {{ request('ficha') == $f->Id_Ficha ? 'selected' : '' }}>
                        {{ $f->Id_Ficha }} — {{ $f->programa->Nombre ?? 'Sin programa' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label for="estado" class="stat-label" style="display: block; margin-bottom: 0.5rem;">Estado del Caso</label>
            <select name="estado" id="estado" class="form-control" onchange="this.form.submit()">
                <option value="">Todos los Estados</option>
                <option value="PENDIENTE" {{ request('estado') == 'PENDIENTE' ? 'selected' : '' }}>🔴 Pendiente de Citación</option>
                <option value="EN_SEGUIMIENTO" {{ request('estado') == 'EN_SEGUIMIENTO' ? 'selected' : '' }}>🟡 En Acompañamiento</option>
                <option value="ATENDIDO" {{ request('estado') == 'ATENDIDO' ? 'selected' : '' }}>🟢 Atendido por Bienestar</option>
                <option value="CERRADO" {{ request('estado') == 'CERRADO' ? 'selected' : '' }}>⚪ Caso Cerrado</option>
            </select>
        </div>

        @if(request('ficha') || request('estado'))
            <a href="{{ route('remisiones.index') }}" class="btn btn-outline" style="height: 42px;">
                <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar
            </a>
        @endif
    </form>
</div>

<!-- ===================== TABLA DE REMISIONES ===================== -->
<!-- ===================== TABLA DE REMISIONES ===================== -->
<div class="card" style="padding: 1.25rem;">
    <div style="overflow-x: hidden;">
        <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.4rem; vertical-align: middle;">
            <thead>
                <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 0.6rem 0.75rem; width: 14%;">RADICADO</th>
                    <th style="padding: 0.6rem 0.75rem; width: 22%;">APRENDIZ</th>
                    <th style="padding: 0.6rem 0.75rem; width: 18%;">FICHA</th>
                    <th style="padding: 0.6rem 0.75rem; width: 14%; text-align: center;">RIESGO</th>
                    <th style="padding: 0.6rem 0.75rem; width: 9%; text-align: center;">PENDIENTES</th>
                    <th style="padding: 0.6rem 0.75rem; width: 13%;">ESTADO</th>
                    <th style="padding: 0.6rem 0.75rem; width: 10%; text-align: right;">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($remisiones as $rem)
                @php
                    $badge = $rem->estado_badge;
                    $telSimulado = '57300' . substr($rem->aprendiz->Documento ?? '1000000', -7);
                    $msjWp = urlencode("Hola {$rem->aprendiz->Nombre}, te contactamos desde Bienestar al Aprendiz / Coordinación SENA respecto a tu caso académico en la ficha {$rem->Id_Ficha} (Radicado: {$rem->radicado}). Te solicitamos confirmar tu asistencia a la asesoría.");
                @endphp
                <tr style="background: rgba(255,255,255,0.02); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                    <!-- Radicado & Fecha -->
                    <td style="padding: 0.75rem; border-radius: 10px 0 0 10px;">
                        <div style="font-weight: 800; color: #ffffff; font-size: 0.82rem; white-space: nowrap;">
                            {{ $rem->radicado ?? ('REM-' . $rem->id) }}
                        </div>
                        <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px; white-space: nowrap;">
                            {{ $rem->created_at->format('d/m/y H:i') }}
                        </div>
                    </td>

                    <!-- Aprendiz -->
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(57,169,0,0.12); border: 1px solid rgba(57,169,0,0.25); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800; color: var(--primary); flex-shrink: 0;">
                                {{ strtoupper(substr($rem->aprendiz->Nombre ?? 'A', 0, 1) . substr($rem->aprendiz->Apellido ?? 'P', 0, 1)) }}
                            </div>
                            <div style="min-width: 0; overflow: hidden;">
                                <div style="font-weight: 700; color: #f1f5f9; font-size: 0.84rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $rem->aprendiz->Nombre ?? '' }} {{ $rem->aprendiz->Apellido ?? '' }}">
                                    {{ $rem->aprendiz->Nombre ?? 'N/A' }} {{ $rem->aprendiz->Apellido ?? '' }}
                                </div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">
                                    CC {{ $rem->aprendiz->Documento ?? '---' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Ficha & Programa -->
                    <td style="padding: 0.75rem;">
                        <div style="font-weight: 800; color: var(--primary); font-size: 0.85rem;">
                            {{ $rem->Id_Ficha }}
                        </div>
                        <div style="font-size: 0.68rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                             title="{{ $rem->ficha->programa->Nombre ?? 'Sin programa asignado' }}">
                            {{ $rem->ficha->programa->Nombre ?? '---' }}
                        </div>
                    </td>

                    <!-- Riesgo -->
                    <td style="padding: 0.75rem; text-align: center; white-space: nowrap;">
                        @if($rem->score_riesgo >= 70)
                            <span style="display: inline-block; background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); padding: 0.25rem 0.55rem; border-radius: 14px; font-size: 0.7rem; font-weight: 800;">
                                🔴 Crítico ({{ $rem->score_riesgo }}%)
                            </span>
                        @else
                            <span style="display: inline-block; background: rgba(245,158,11,0.15); color: #fde68a; border: 1px solid rgba(245,158,11,0.3); padding: 0.25rem 0.55rem; border-radius: 14px; font-size: 0.7rem; font-weight: 800;">
                                🟡 Moderado ({{ $rem->score_riesgo }}%)
                            </span>
                        @endif
                    </td>

                    <!-- Pendientes -->
                    <td style="padding: 0.75rem; text-align: center; white-space: nowrap;">
                        <span style="font-size: 1.05rem; font-weight: 900; color: #ef4444;">
                            {{ $rem->total_pendientes }}
                        </span>
                        <div style="font-size: 0.65rem; color: var(--text-muted); line-height: 1;">
                            pendientes
                        </div>
                    </td>

                    <!-- Estado y Selector Rápido -->
                    <td style="padding: 0.75rem;">
                        <form method="POST" action="{{ route('remisiones.estado', $rem->id) }}" style="margin: 0;">
                            @csrf
                            <select name="estado_remision" onchange="this.form.submit()" class="form-control"
                                    style="padding: 0.35rem 0.5rem; font-size: 0.72rem; font-weight: 700; width: 100%; background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; border: 1px solid {{ $badge['border'] }}; cursor: pointer; border-radius: 8px;">
                                <option value="PENDIENTE" {{ $rem->estado_remision == 'PENDIENTE' ? 'selected' : '' }} style="background: #0f172a; color: #fca5a5;">🔴 Pendiente</option>
                                <option value="EN_SEGUIMIENTO" {{ $rem->estado_remision == 'EN_SEGUIMIENTO' ? 'selected' : '' }} style="background: #0f172a; color: #fde68a;">🟡 Acompañamiento</option>
                                <option value="ATENDIDO" {{ $rem->estado_remision == 'ATENDIDO' ? 'selected' : '' }} style="background: #0f172a; color: #86efac;">🟢 Atendido</option>
                                <option value="CERRADO" {{ $rem->estado_remision == 'CERRADO' ? 'selected' : '' }} style="background: #0f172a; color: #cbd5e1;">⚪ Cerrado</option>
                            </select>
                        </form>
                    </td>

                    <!-- Acciones -->
                    <td style="padding: 0.75rem; text-align: right; border-radius: 0 10px 10px 0; white-space: nowrap;">
                        <div style="display: flex; gap: 4px; justify-content: flex-end; align-items: center;">
                            <!-- Descargar Oficio PDF -->
                            <a href="{{ route('remisiones.oficio-pdf', ['radicado' => $rem->radicado]) }}"
                               class="btn btn-outline"
                               style="padding: 0; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; color: #60a5fa; border-radius: 6px; font-size: 0.75rem;"
                               title="Descargar Oficio en PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>

                            <!-- Ver Expediente / Plan de Salvación -->
                            <a href="{{ route('acciones.simulador', $rem->Id_Aprendiz) }}"
                               class="btn btn-outline"
                               style="padding: 0; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; color: var(--primary); border-radius: 6px; font-size: 0.75rem;"
                               title="Plan de Salvación">
                                <i class="fa-solid fa-chart-line"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
                        No hay remisiones registradas con los filtros seleccionados.
                        <div style="margin-top: 1rem;">
                            <a href="{{ route('acciones.diagnostico') }}" class="btn btn-primary" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-bullhorn"></i> Emitir Alerta desde el Semáforo de Deserción
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 2rem; display: flex; justify-content: center;">
        {{ $remisiones->links() }}
    </div>
</div>
@endsection
