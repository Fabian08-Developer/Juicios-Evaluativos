@extends('layouts.app')

@section('title', 'Listado Maestro de Aprendices')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="margin: 0; font-size: 1.75rem;">Listado Maestro</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Gestiona y consulta la información de los aprendices</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <!-- 📊 Exportar Excel -->
        <a href="{{ route('aprendices.export.excel', request()->all()) }}" class="btn btn-outline">
            <i class="fa-solid fa-file-excel" style="color: var(--primary);"></i> Exportar Excel
        </a>
        <a href="{{ route('aprendices.upload') }}" class="btn btn-primary">
            <i class="fa-solid fa-cloud-arrow-up"></i> Carga Masiva
        </a>
    </div>
</div>

<!-- Tarjetas de Estadísticas con contadores animados -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background:rgba(14,165,233,0.1);color:#0ea5e9;width:45px;height:45px;font-size:1.2rem;border-radius:14px;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="stat-value" data-counter="{{ $totalAprendices }}" style="font-size:1.5rem;">0</div>
            <div class="stat-label" style="font-size:0.7rem;">Total</div>
        </div>
    </div>
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background:rgba(57,169,0,0.1);color:#39A900;width:45px;height:45px;font-size:1.2rem;border-radius:14px;">
            <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div>
            <div class="stat-value" data-counter="{{ $enFormacion }}" style="font-size:1.5rem;">0</div>
            <div class="stat-label" style="font-size:0.7rem;">En Formación</div>
        </div>
    </div>
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background:rgba(245,158,11,0.1);color:#f59e0b;width:45px;height:45px;font-size:1.2rem;border-radius:14px;">
            <i class="fa-solid fa-user-minus"></i>
        </div>
        <div>
            <div class="stat-value" data-counter="{{ $retiroVoluntario }}" style="font-size:1.5rem;">0</div>
            <div class="stat-label" style="font-size:0.7rem;">Retiro Vol.</div>
        </div>
    </div>
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background:rgba(148,163,184,0.1);color:#94a3b8;width:45px;height:45px;font-size:1.2rem;border-radius:14px;">
            <i class="fa-solid fa-right-left"></i>
        </div>
        <div>
            <div class="stat-value" data-counter="{{ $traslado }}" style="font-size:1.5rem;">0</div>
            <div class="stat-label" style="font-size:0.7rem;">Trasladados</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <form action="{{ route('aprendices.index') }}" method="GET"
          style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
        <div>
            <label style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);margin-bottom:0.5rem;text-transform:uppercase;">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nombre, apellido o documento...">
        </div>
        <div>
            <label style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);margin-bottom:0.5rem;text-transform:uppercase;">Ficha</label>
            <select name="ficha" class="form-control">
                <option value="">Todas</option>
                @foreach($fichas as $ficha)
                    <option value="{{ $ficha->Id_Ficha }}" {{ request('ficha') == $ficha->Id_Ficha ? 'selected' : '' }}>
                        {{ $ficha->Id_Ficha }} – {{ $ficha->programa->Nombre ?? 'S/P' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);margin-bottom:0.5rem;text-transform:uppercase;">Estado</label>
            <select name="estado" class="form-control">
                <option value="">Todos</option>
                <option value="EN FORMACION"      {{ request('estado') == 'EN FORMACION'      ? 'selected' : '' }}>En Formación</option>
                <option value="TRASLADADO"         {{ request('estado') == 'TRASLADADO'         ? 'selected' : '' }}>Trasladado</option>
                <option value="RETIRO VOLUNTARIO"  {{ request('estado') == 'RETIRO VOLUNTARIO'  ? 'selected' : '' }}>Retiro Voluntario</option>
            </select>
        </div>
        <div style="display:flex;gap:0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding:0.6rem 1rem;">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <a href="{{ route('aprendices.index') }}" class="btn btn-outline" style="padding:0.6rem 1rem;" title="Limpiar">
                <i class="fa-solid fa-rotate-left"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <div style="overflow-x: auto;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 0.4rem;">
            <thead>
                <tr style="text-align:left;color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;">
                    <th style="padding:0.75rem 1rem;">Documento</th>
                    <th style="padding:0.75rem 1rem;">Nombre Completo</th>
                    <th style="padding:0.75rem 1rem;">Ficha / Programa</th>
                    <th style="padding:0.75rem 1rem;">Estado</th>
                    <th style="padding:0.75rem 1rem;text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($aprendices as $aprendiz)
                <tr style="background:rgba(255,255,255,0.02);transition:background 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.05)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                    <td style="padding:1.1rem 1rem;border-radius:12px 0 0 12px;">
                        <span style="color:var(--text-muted);font-size:0.65rem;font-weight:700;display:block;">{{ $aprendiz->Tipo_Documento }}</span>
                        <div style="font-weight:700;color:#fff;">{{ $aprendiz->Documento }}</div>
                    </td>
                    <td style="padding:1.1rem 1rem;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:800;color:#fff;flex-shrink:0;">
                                {{ strtoupper(substr($aprendiz->Nombre,0,1).substr($aprendiz->Apellido,0,1)) }}
                            </div>
                            <span style="font-weight:600;color:#f1f5f9;">{{ $aprendiz->Nombre }} {{ $aprendiz->Apellido }}</span>
                        </div>
                    </td>
                    <td style="padding:1.1rem 1rem;">
                        <div style="font-weight:700;color:var(--primary);">{{ $aprendiz->Id_Ficha }}</div>
                        <div style="font-size:0.72rem;color:var(--text-muted);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $aprendiz->ficha->programa->Nombre ?? 'Sin programa' }}
                        </div>
                    </td>
                    <td style="padding:1.1rem 1rem;">
                        @php
                            $bc = 'badge-info';
                            if($aprendiz->Estado == 'EN FORMACION') $bc = 'badge-success';
                            if(in_array($aprendiz->Estado, ['RETIRO VOLUNTARIO','TRASLADADO'])) $bc = 'badge-warning';
                        @endphp
                        <span class="badge {{ $bc }}">{{ $aprendiz->Estado }}</span>
                    </td>
                    <td style="padding:1.1rem 1rem;text-align:right;border-radius:0 12px 12px 0;">
                        <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                            <a href="{{ route('aprendices.show', $aprendiz->Id_Aprendiz) }}" class="btn btn-outline" style="padding:0.45rem 0.9rem;font-size:0.8rem;" title="Ver expediente">
                                <i class="fa-solid fa-folder-open"></i> Expediente
                            </a>
                            <a href="{{ route('aprendices.pdf', $aprendiz->Id_Aprendiz) }}" class="btn btn-outline" style="padding:0.45rem 0.75rem;font-size:0.8rem;" title="Descargar PDF" target="_blank">
                                <i class="fa-solid fa-file-pdf" style="color:#fca5a5;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:4rem;text-align:center;color:var(--text-muted);">
                        <i class="fa-solid fa-user-slash" style="font-size:3rem;margin-bottom:1rem;display:block;opacity:0.2;"></i>
                        No se encontraron aprendices con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:2rem;display:flex;justify-content:center;">
        {{ $aprendices->links() }}
    </div>
</div>
@endsection
