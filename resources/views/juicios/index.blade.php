@extends('layouts.app')

@section('title', 'Juicios Evaluativos')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-clipboard-check" style="color: var(--primary);"></i>
            Juicios Evaluativos
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.3rem;">
            Estado de cada aprendiz por competencia y resultado de aprendizaje (RAP), según el último reporte de Sofia Plus
        </p>
    </div>
    <a href="{{ route('aprendices.upload') }}" class="btn btn-primary">
        <i class="fa-solid fa-cloud-arrow-up"></i> Importar Reporte Sofia Plus
    </a>
</div>

<!-- Indicadores (según la ficha y la búsqueda elegidas) -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    @foreach([
        ['fa-clipboard-list', '#0ea5e9', 'rgba(14,165,233,0.1)', $totalJuicios, 'Total juicios', null],
        ['fa-circle-check', 'var(--primary)', 'rgba(57,169,0,0.1)', $totalAprobados, 'Aprobados', 'var(--primary)'],
        ['fa-clock', '#f59e0b', 'rgba(245,158,11,0.1)', $totalPendientes, 'Por evaluar', '#f59e0b'],
    ] as [$icono, $color, $fondo, $valor, $etiqueta, $colorValor])
        <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
            <div class="icon-box" style="background: {{ $fondo }}; color: {{ $color }}; width: 45px; height: 45px; font-size: 1.2rem;">
                <i class="fa-solid {{ $icono }}"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size: 1.6rem; {{ $colorValor ? "color: {$colorValor};" : '' }}">{{ number_format($valor) }}</div>
                <div class="stat-label" style="font-size: 0.72rem;">{{ $etiqueta }}</div>
            </div>
        </div>
    @endforeach
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('juicios.index') }}" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 2; min-width: 220px;">
            <label for="buscar" class="stat-label" style="display: block; margin-bottom: 0.5rem;">Buscar aprendiz</label>
            <input type="text" name="buscar" id="buscar" class="form-control" placeholder="Nombre, apellido o documento..." value="{{ request('buscar') }}">
        </div>
        <div style="flex: 1; min-width: 160px;">
            <label for="ficha" class="stat-label" style="display: block; margin-bottom: 0.5rem;">Ficha</label>
            <select name="ficha" id="ficha" class="form-control" onchange="this.form.submit()">
                <option value="">Todas las fichas</option>
                @foreach($fichas as $f)
                    <option value="{{ $f->Id_Ficha }}" @selected(request('ficha') == $f->Id_Ficha)>{{ $f->Id_Ficha }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1; min-width: 160px;">
            <label for="estado" class="stat-label" style="display: block; margin-bottom: 0.5rem;">Estado del juicio</label>
            <select name="estado" id="estado" class="form-control" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" @selected(request('estado') === '1')>Aprobado</option>
                <option value="0" @selected(request('estado') === '0')>Por evaluar</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px;">
            <i class="fa-solid fa-magnifying-glass"></i> Filtrar
        </button>
        @if(request()->filled('buscar') || request()->filled('ficha') || request()->filled('estado'))
            <a href="{{ route('juicios.index') }}" class="btn btn-outline" style="height: 42px;">
                <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar
            </a>
        @endif
    </form>
</div>

<!-- Tabla -->
<div class="card" style="padding: 1.25rem;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.4rem;">
            <thead>
                <tr style="text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 0.75rem 1rem;">Aprendiz</th>
                    <th style="padding: 0.75rem 1rem;">Ficha</th>
                    <th style="padding: 0.75rem 1rem;">Competencia / RAP</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Estado</th>
                    <th style="padding: 0.75rem 1rem;">Fecha / Instructor</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($juicios as $juicio)
                <tr style="background: rgba(255,255,255,0.02);">
                    <td style="padding: 0.85rem 1rem; border-radius: 10px 0 0 10px;">
                        <div style="font-weight: 700; color: #f1f5f9; font-size: 0.88rem;">{{ $juicio->aprendiz->Nombre ?? 'N/A' }} {{ $juicio->aprendiz->Apellido ?? '' }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">{{ $juicio->aprendiz->Tipo_Documento ?? 'CC' }} {{ $juicio->aprendiz->Documento ?? '—' }}</div>
                    </td>
                    <td style="padding: 0.85rem 1rem;">
                        <span style="font-weight: 800; color: var(--primary); font-size: 0.85rem;">{{ $juicio->aprendiz->Id_Ficha ?? '—' }}</span>
                    </td>
                    <td style="padding: 0.85rem 1rem; max-width: 340px;">
                        <div style="font-size: 0.78rem; font-weight: 700; color: var(--accent);">{{ $juicio->resultado->competencia->Codigo ?? 'Competencia' }}</div>
                        <div style="font-size: 0.75rem; color: #cbd5e1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $juicio->resultado->Nombre ?? '' }}">
                            RAP {{ $juicio->resultado->Codigo ?? '' }} — {{ $juicio->resultado->Nombre ?? 'Sin descripción' }}
                        </div>
                    </td>
                    <td style="padding: 0.85rem 1rem; text-align: center; white-space: nowrap;">
                        @if($juicio->Estado == 1)
                            <span style="display: inline-block; background: rgba(57,169,0,0.12); color: var(--primary); border: 1px solid rgba(57,169,0,0.25); padding: 0.3rem 0.7rem; border-radius: 14px; font-size: 0.75rem; font-weight: 800;">
                                <i class="fa-solid fa-circle-check"></i> APROBADO
                            </span>
                        @else
                            <span style="display: inline-block; background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.25); padding: 0.3rem 0.7rem; border-radius: 14px; font-size: 0.75rem; font-weight: 800;">
                                <i class="fa-solid fa-clock"></i> POR EVALUAR
                            </span>
                        @endif
                    </td>
                    <td style="padding: 0.85rem 1rem; font-size: 0.8rem; color: var(--text-muted);">
                        <div style="white-space: nowrap;">{{ $juicio->Fecha ? $juicio->Fecha->format('d/m/Y') : 'Sin fecha' }}</div>
                        @if($juicio->funcionario)
                            <div style="font-size: 0.72rem;">{{ $juicio->funcionario->Nombre }} {{ $juicio->funcionario->Apellido }}</div>
                        @endif
                    </td>
                    <td style="padding: 0.85rem 1rem; text-align: right; border-radius: 0 10px 10px 0; white-space: nowrap;">
                        @if($juicio->aprendiz)
                            <a href="{{ route('aprendices.show', $juicio->aprendiz->Id_Aprendiz) }}" class="btn btn-outline" style="padding: 0.4rem 0.75rem; font-size: 0.75rem;">
                                <i class="fa-solid fa-user"></i> Expediente
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
                        No hay juicios evaluativos con los filtros elegidos.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 2rem; display: flex; justify-content: center;">
        {{ $juicios->links() }}
    </div>
</div>
@endsection
