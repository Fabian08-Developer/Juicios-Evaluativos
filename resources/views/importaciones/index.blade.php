@extends('layouts.app')

@section('title', 'Historial de Importaciones')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="margin: 0; font-size: 1.75rem;">Historial de Importaciones</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Registro de todos los archivos procesados por el sistema</p>
    </div>
    <a href="{{ route('aprendices.upload') }}" class="btn btn-primary">
        <i class="fa-solid fa-cloud-arrow-up"></i> Nueva Importación
    </a>
</div>

<!-- Estadísticas rápidas -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background: rgba(57,169,0,0.1); color:#39A900; width:45px; height:45px; font-size:1.2rem;">
            <i class="fa-solid fa-file-import"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.5rem;">{{ $importaciones->total() }}</div>
            <div class="stat-label" style="font-size:0.7rem;">Total Importaciones</div>
        </div>
    </div>
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background: rgba(14,165,233,0.1); color:#0ea5e9; width:45px; height:45px; font-size:1.2rem;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.5rem;">{{ $totalAprendicesProcesados }}</div>
            <div class="stat-label" style="font-size:0.7rem;">Aprendices Procesados</div>
        </div>
    </div>
    <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
        <div class="icon-box" style="background: rgba(245,158,11,0.1); color:#f59e0b; width:45px; height:45px; font-size:1.2rem;">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.5rem;">
                {{ $ultimaImportacion ? $ultimaImportacion->created_at->diffForHumans() : 'N/A' }}
            </div>
            <div class="stat-label" style="font-size:0.7rem;">Última Importación</div>
        </div>
    </div>
</div>

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
                        background: {{ $imp->estado === 'exitoso' ? 'var(--primary)' : '#ef4444' }};
                        border: 2px solid {{ $imp->estado === 'exitoso' ? 'rgba(57,169,0,0.3)' : 'rgba(239,68,68,0.3)' }};
                        box-shadow: 0 0 8px {{ $imp->estado === 'exitoso' ? 'var(--primary-glow)' : 'rgba(239,68,68,0.4)' }};"></div>

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
                                     background: {{ $imp->estado === 'exitoso' ? 'rgba(57,169,0,0.1)' : 'rgba(239,68,68,0.1)' }};
                                     color: {{ $imp->estado === 'exitoso' ? 'var(--primary)' : '#fca5a5' }};
                                     border: 1px solid {{ $imp->estado === 'exitoso' ? 'rgba(57,169,0,0.2)' : 'rgba(239,68,68,0.2)' }};">
                            {{ $imp->estado === 'exitoso' ? '✓ Exitoso' : '✗ Error' }}
                        </span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                            {{ $imp->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); opacity: 0.7;">
                            {{ $imp->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

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
                        @if($imp->id_ficha)
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
                                     background: {{ $imp->estado === 'exitoso' ? 'rgba(57,169,0,0.1)' : 'rgba(239,68,68,0.1)' }};
                                     color: {{ $imp->estado === 'exitoso' ? 'var(--primary)' : '#fca5a5' }};
                                     border: 1px solid {{ $imp->estado === 'exitoso' ? 'rgba(57,169,0,0.2)' : 'rgba(239,68,68,0.2)' }};">
                            {{ $imp->estado === 'exitoso' ? '✓ Exitoso' : '✗ Error' }}
                        </span>
                    </td>
                    <td style="padding: 1rem; border-radius: 0 12px 12px 0; color: var(--text-muted); font-size: 0.85rem;">
                        {{ $imp->created_at->format('d/m/Y H:i') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 4rem; text-align: center; color: var(--text-muted);">
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
    </div>
    <div style="margin-top: 2rem; display: flex; justify-content: center;">
        {{ $importaciones->links() }}
    </div>
</div>

<style>
@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-20px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>

@endsection
