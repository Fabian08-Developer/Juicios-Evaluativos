@extends('layouts.app')

@section('title', 'Dashboard de Indicadores')

@section('content')
<!-- Filtros AJAX -->
<div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <div style="display: flex; gap: 1.5rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <label for="ficha_id" class="stat-label" style="display: block; margin-bottom: 0.5rem;">Filtrar por Ficha</label>
            <select id="ficha_id" name="ficha_id" class="form-control">
                <option value="">Todas las fichas</option>
                @foreach($fichas as $ficha)
                    <option value="{{ $ficha->Id_Ficha }}" {{ $fichaId == $ficha->Id_Ficha ? 'selected' : '' }}>
                        {{ $ficha->Id_Ficha }} — {{ $ficha->programa->Nombre ?? 'Sin programa' }}
                    </option>
                @endforeach
            </select>
        </div>
        @if($fichaId)
        <a href="{{ route('dashboard') }}" class="btn btn-outline" style="height: 42px;">
            <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar
        </a>
        @endif
        <!-- Indicador de actualización -->
        <div id="ajax-indicator" style="display:none; align-items:center; gap:8px; font-size:0.8rem; color:var(--primary);">
            <i class="fa-solid fa-spinner fa-spin"></i> Actualizando...
        </div>
    </div>
</div>

<!-- KPIs con contadores animados -->
<div class="grid">
    <!-- Total Aprendices -->
    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(14,165,233,0.1); color:#0ea5e9;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="stat-value" id="kpi-total" data-counter="{{ $totalAprendices }}">0</div>
            <div class="stat-label">Total Aprendices</div>
        </div>
    </div>

    <!-- Juicios Aprobados -->
    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(57,169,0,0.1); color:#39A900;">
            <i class="fa-solid fa-check-double"></i>
        </div>
        <div>
            <div class="stat-value" id="kpi-aprobados" data-counter="{{ $juiciosAprobados }}">0</div>
            <div class="stat-label">Juicios Aprobados</div>
        </div>
    </div>

    <!-- Juicios Pendientes -->
    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(245,158,11,0.1); color:#f59e0b;">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div>
            <div class="stat-value" id="kpi-pendientes" data-counter="{{ $juiciosPendientes }}">0</div>
            <div class="stat-label">Por Evaluar</div>
        </div>
    </div>

    <!-- 🚨 Aprendices en Riesgo -->
    <div class="card stat-card">
        <div class="icon-box" style="background: rgba(239,68,68,0.1); color:#ef4444;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <div class="stat-value" id="kpi-riesgo" data-counter="{{ $aprendicesEnRiesgo }}" style="{{ $aprendicesEnRiesgo > 0 ? 'color:#ef4444;' : '' }}">0</div>
            <div class="stat-label">En Riesgo</div>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1.5fr; gap: 2rem; margin-top: 0; align-items: start;">
    <!-- Gráfica Dona -->
    <div class="card" style="display:flex; flex-direction:column; min-height:480px;">
        <h3 style="margin-bottom:1.5rem;">Estado Global de Juicios</h3>
        <div style="flex:1; width:100%; position:relative; min-height:300px; display:flex; align-items:center; justify-content:center;">
            <div style="width:100%; height:300px; position:relative;">
                <canvas id="juiciosChart"></canvas>
                <div id="chart-empty" style="display:{{ ($juiciosAprobados == 0 && $juiciosPendientes == 0) ? 'flex' : 'none' }}; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); flex-direction:column; align-items:center; color:var(--text-muted); pointer-events:none;">
                    <i class="fa-solid fa-chart-pie" style="font-size:3rem; opacity:0.2;"></i>
                    <p style="margin-top:1rem; font-size:0.85rem;">Sin datos suficientes</p>
                </div>
            </div>
        </div>
        <div style="margin-top:1.5rem; display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div style="text-align:center; padding:1rem; background:rgba(57,169,0,0.05); border-radius:16px; border:1px solid rgba(57,169,0,0.1);">
                <div style="font-size:0.7rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.4rem; letter-spacing:0.05em;">Aprobados</div>
                <div style="font-size:1.5rem; font-weight:800; color:var(--primary);" id="legend-aprobados">{{ $juiciosAprobados }}</div>
            </div>
            <div style="text-align:center; padding:1rem; background:rgba(245,158,11,0.05); border-radius:16px; border:1px solid rgba(245,158,11,0.1);">
                <div style="font-size:0.7rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.4rem; letter-spacing:0.05em;">Pendientes</div>
                <div style="font-size:1.5rem; font-weight:800; color:#f59e0b;" id="legend-pendientes">{{ $juiciosPendientes }}</div>
            </div>
        </div>
    </div>

    <!-- Panel Derecho -->
    <div style="display:flex; flex-direction:column; gap:2rem;">
        <!-- Acciones Rápidas -->
        <div class="card">
            <h3 style="margin-bottom:1.25rem;">Acciones Rápidas</h3>
            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                <a href="{{ route('aprendices.upload') }}" class="btn btn-primary" style="flex:1; justify-content:center;">
                    <i class="fa-solid fa-file-import"></i> Importar Reporte
                </a>
                <a href="{{ route('aprendices.index') }}" class="btn btn-outline" style="flex:1; justify-content:center;">
                    <i class="fa-solid fa-users"></i> Ver Aprendices
                </a>
            </div>
        </div>

        <!-- Rendimiento por Ficha -->
        <div class="card">
            <h3 style="margin-bottom:1.5rem;">Rendimiento General por Ficha</h3>
            <div style="width:100%; height:260px;">
                <canvas id="generalChart"></canvas>
            </div>
        </div>

        <!-- 🚨 Aprendices en Riesgo -->
        @if($aprendicesRiesgoDetalle->count() > 0)
        <div class="card" style="border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.03);">
            <h3 style="margin-bottom:1.25rem; color:#ef4444; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Aprendices en Riesgo
            </h3>
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                @foreach($aprendicesRiesgoDetalle as $ar)
                <a href="{{ route('aprendices.show', $ar->Id_Aprendiz) }}" style="display:flex; align-items:center; gap:12px; padding:0.75rem 1rem; background:rgba(239,68,68,0.05); border-radius:12px; border:1px solid rgba(239,68,68,0.1); text-decoration:none; transition:all 0.2s;"
                   onmouseover="this.style.borderColor='rgba(239,68,68,0.3)'" onmouseout="this.style.borderColor='rgba(239,68,68,0.1)'">
                    <div style="width:36px;height:36px;background:rgba(239,68,68,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:800;color:#fca5a5;">
                        {{ strtoupper(substr($ar->Nombre,0,1).substr($ar->Apellido,0,1)) }}
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:0.85rem;color:#f1f5f9;">{{ $ar->Nombre }} {{ $ar->Apellido }}</div>
                        <div style="font-size:0.72rem;color:var(--text-muted);">{{ $ar->pendientes_count ?? 0 }}/{{ $ar->total_juicios ?? 0 }} pendientes</div>
                    </div>
                    <div style="font-size:0.75rem;font-weight:800;color:#ef4444;">
                        {{ ($ar->total_juicios ?? 0) > 0 ? round((($ar->pendientes_count ?? 0) / $ar->total_juicios) * 100) : 0 }}%
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($fichaId)
        <div class="card" style="background:linear-gradient(135deg,rgba(57,169,0,0.08),rgba(14,165,233,0.08)); border-color:rgba(57,169,0,0.2);">
            <h3 style="color:var(--primary); margin-bottom:0.75rem;">Resumen de Ficha</h3>
            <p style="font-size:0.9rem;"><strong>Ficha:</strong> {{ $fichaId }}</p>
            <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.4rem;">Estadísticas exclusivas de los aprendices vinculados a este grupo.</p>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ============ INICIALIZAR GRÁFICAS ============
    const aprobadosInit  = {{ $juiciosAprobados }};
    const pendientesInit = {{ $juiciosPendientes }};

    const ctxPie = document.getElementById('juiciosChart').getContext('2d');
    let juiciosChart = null;

    function crearGraficaDona(aprobados, pendientes) {
        if (juiciosChart) juiciosChart.destroy();
        if (aprobados === 0 && pendientes === 0) {
            document.getElementById('chart-empty').style.display = 'flex';
            return;
        }
        document.getElementById('chart-empty').style.display = 'none';
        juiciosChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Aprobados', 'Pendientes'],
                datasets: [{
                    data: [aprobados, pendientes],
                    backgroundColor: ['#39A900', '#f59e0b'],
                    borderColor: 'rgba(15,23,42,0.5)',
                    borderWidth: 2,
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                animation: { animateRotate: true, duration: 800 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const total = aprobados + pendientes;
                                const pct = Math.round((ctx.raw / total) * 100);
                                return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    const ctxBar = document.getElementById('generalChart').getContext('2d');
    const barLabels      = @json($statsPorFicha->pluck('Id_Ficha'));
    const barAprobados   = @json($statsPorFicha->pluck('aprobados'));
    const barPendientes  = @json($statsPorFicha->pluck('pendientes'));

    let generalChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [
                { label: 'Aprobados', data: barAprobados, backgroundColor: '#39A900', borderRadius: 6 },
                { label: 'Pendientes', data: barPendientes, backgroundColor: '#f59e0b', borderRadius: 6 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 700 },
            plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8', font: { size: 10 } } }
            }
        }
    });

    crearGraficaDona(aprobadosInit, pendientesInit);

    // ============ AJAX DASHBOARD ============
    const fichaSelect = document.getElementById('ficha_id');
    const indicator   = document.getElementById('ajax-indicator');

    fichaSelect.addEventListener('change', async function () {
        const fichaId = this.value;
        indicator.style.display = 'flex';

        try {
            const url = fichaId ? `/api/dashboard-stats?ficha_id=${fichaId}` : '/api/dashboard-stats';
            const res  = await fetch(url);
            const data = await res.json();

            // Actualizar KPIs con animación
            animateCounterTo(document.getElementById('kpi-total'),      data.totalAprendices);
            animateCounterTo(document.getElementById('kpi-aprobados'),  data.juiciosAprobados);
            animateCounterTo(document.getElementById('kpi-pendientes'), data.juiciosPendientes);
            animateCounterTo(document.getElementById('kpi-riesgo'),     data.aprendicesEnRiesgo);

            document.getElementById('legend-aprobados').textContent  = data.juiciosAprobados;
            document.getElementById('legend-pendientes').textContent = data.juiciosPendientes;

            // Actualizar dona
            crearGraficaDona(data.juiciosAprobados, data.juiciosPendientes);

            // Actualizar barras
            generalChart.data.labels                    = data.statsPorFicha.map(s => s.Id_Ficha);
            generalChart.data.datasets[0].data          = data.statsPorFicha.map(s => s.aprobados);
            generalChart.data.datasets[1].data          = data.statsPorFicha.map(s => s.pendientes);
            generalChart.update('active');

            // Actualizar URL sin recargar
            const newUrl = fichaId ? `?ficha_id=${fichaId}` : window.location.pathname;
            history.replaceState(null, '', newUrl);

        } catch (e) {
            showToast('Error al actualizar las estadísticas.', 'error');
        } finally {
            indicator.style.display = 'none';
        }
    });

    function animateCounterTo(el, target) {
        const duration = 700;
        const start    = parseInt(el.textContent) || 0;
        const startTime = performance.now();
        function update(time) {
            const progress = Math.min((time - startTime) / duration, 1);
            const eased    = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(start + (target - start) * eased);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }
});
</script>
@endsection
