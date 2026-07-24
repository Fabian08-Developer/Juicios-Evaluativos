@extends('layouts.app')

@section('title', 'Expediente Académico')

@section('content')

@php
    $totalJuicios    = $aprendiz->juicios->count();
    $totalAprobados  = $aprendiz->juicios->where('Estado', 1)->count();
    $porcentajeGlobal = $totalJuicios > 0 ? round(($totalAprobados / $totalJuicios) * 100) : 0;
    $badgeClass = 'badge-info';
    if($aprendiz->Estado == 'EN FORMACION') $badgeClass = 'badge-success';
    if(in_array($aprendiz->Estado, ['RETIRO VOLUNTARIO', 'TRASLADADO'])) $badgeClass = 'badge-warning';
@endphp

<!-- Barra superior de acciones rápidas -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; background: rgba(15,23,42,0.6); padding: 1rem 1.5rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);">
    <div>
        <a href="{{ route('aprendices.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-arrow-left"></i> Volver al listado general
        </a>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="{{ route('acciones.simulador', $aprendiz->Id_Aprendiz) }}" class="btn btn-primary" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); font-weight: 800; box-shadow: 0 8px 20px -4px var(--primary-glow);">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Simular Plan de Salvación
        </a>
        <a href="{{ route('aprendices.pdf', $aprendiz->Id_Aprendiz) }}" class="btn" style="background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); font-weight: 800;">
            <i class="fa-solid fa-file-pdf"></i> Expediente PDF
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start;">

    <!-- ===== COLUMNA IZQUIERDA: Perfil ===== -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Tarjeta de Perfil -->
        <div class="card" style="text-align: center; position: relative; overflow: hidden;">
            <div style="position:absolute;top:-40px;right:-40px;width:120px;height:120px;background:var(--primary-glow);filter:blur(50px);opacity:0.3;"></div>

            <!-- Avatar -->
            <div style="position:relative; width:110px; height:110px; margin:0 auto 1.25rem;">
                <div style="width:100%; height:100%; background:linear-gradient(135deg,var(--primary),var(--primary-dark)); border-radius:28px; display:flex; align-items:center; justify-content:center; font-size:2.75rem; color:white; font-weight:900; transform:rotate(-5deg); box-shadow:0 15px 30px -5px var(--primary-glow);">
                    <span style="transform:rotate(5deg);">{{ strtoupper(substr($aprendiz->Nombre,0,1).substr($aprendiz->Apellido,0,1)) }}</span>
                </div>
            </div>

            <h2 style="font-size:1.4rem; font-weight:800; color:#fff; margin-bottom:0.5rem;">
                {{ $aprendiz->Nombre }} {{ $aprendiz->Apellido }}
            </h2>
            <span class="badge {{ $badgeClass }}" style="margin-bottom:1.75rem; display:inline-block;">{{ $aprendiz->Estado }}</span>

            <div style="border-top:1px solid var(--glass-border); padding-top:1.5rem; text-align:left; display:flex; flex-direction:column; gap:1.25rem;">
                <div>
                    <label style="display:block;color:var(--text-muted);font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Documento</label>
                    <div style="font-weight:700;color:#f1f5f9;">{{ $aprendiz->Tipo_Documento }} {{ $aprendiz->Documento }}</div>
                </div>
                <div>
                    <label style="display:block;color:var(--text-muted);font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Número de Ficha</label>
                    <div style="font-weight:800;color:var(--primary);font-size:1.1rem;">{{ $aprendiz->Id_Ficha }}</div>
                </div>
                <div>
                    <label style="display:block;color:var(--text-muted);font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Programa</label>
                    <div style="font-weight:600;color:#f1f5f9;font-size:0.85rem;line-height:1.4;">{{ $aprendiz->ficha->programa->Nombre ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Resumen Global -->
        <div class="card" style="text-align:center;">
            <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:1rem;">Avance Global</div>
            <div style="font-size:3rem;font-weight:900;color:{{ $porcentajeGlobal >= 70 ? 'var(--primary)' : ($porcentajeGlobal >= 40 ? '#f59e0b' : '#ef4444') }}; line-height:1; margin-bottom:0.5rem;">{{ $porcentajeGlobal }}%</div>
            <div style="color:var(--text-muted);font-size:0.85rem;margin-bottom:1.5rem;">{{ $totalAprobados }} de {{ $totalJuicios }} juicios aprobados</div>
            <div style="height:10px;background:rgba(255,255,255,0.06);border-radius:6px;overflow:hidden;">
                <div id="global-bar" style="width:0%;height:100%;background:linear-gradient(to right,{{ $porcentajeGlobal >= 70 ? 'var(--primary), #4ade80' : ($porcentajeGlobal >= 40 ? '#f59e0b, #fbbf24' : '#ef4444, #f87171') }});border-radius:6px;transition:width 1.2s cubic-bezier(0.4,0,0.2,1);box-shadow:0 0 15px rgba(57,169,0,0.3);"></div>
            </div>
        </div>

        <!-- Mini Radar Chart -->
        @if($avancePorCompetencia->count() > 0)
        <div class="card" style="text-align:center;">
            <h4 style="margin-bottom:1.25rem;color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;letter-spacing:0.05em;">Mapa de Competencias</h4>
            <canvas id="radarChart" style="max-width:100%;"></canvas>
        </div>
        @endif

    </div>

    <!-- ===== COLUMNA DERECHA: Competencias ===== -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
            <h3 style="margin:0;font-size:1.4rem;display:flex;align-items:center;gap:10px;">
                <i class="fa-solid fa-chart-pie" style="color:var(--primary);"></i>
                Progreso por Competencias
            </h3>
            <span style="font-size:0.8rem;background:rgba(14,165,233,0.1);color:var(--accent);padding:0.4rem 0.8rem;border-radius:8px;font-weight:700;">
                {{ $aprendiz->juicios->count() }} Juicios
            </span>
        </div>

        <div style="display:flex;flex-direction:column;gap:1rem;">
            @foreach($avancePorCompetencia as $codigoKey => $data)
            <div class="competencia-item" style="background:rgba(15,23,42,0.6);border-radius:20px;border:1px solid var(--glass-border);overflow:hidden;transition:all 0.3s;">
                <!-- Header Accordion -->
                <div class="competencia-header" style="padding:1.25rem 1.5rem;cursor:pointer;" onclick="toggleCompetencia('comp-{{ $loop->index }}')">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.875rem;">
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:0.2rem;">
                                <span style="background:var(--primary);color:#000;font-size:0.65rem;font-weight:900;padding:0.15rem 0.5rem;border-radius:5px;letter-spacing:0.05em;">COMP</span>
                                <span style="font-weight:800;color:#fff;">{{ $data['codigo'] }}</span>
                            </div>
                            <div style="font-size:0.82rem;color:#f1f5f9;line-height:1.4;opacity:0.85;">{{ $data['nombre'] }}</div>
                        </div>
                        <div style="text-align:right;margin-left:1.5rem;">
                            <div style="font-size:1.25rem;font-weight:900;color:{{ $data['porcentaje'] >= 70 ? 'var(--primary)' : '#f59e0b' }};">{{ round($data['porcentaje']) }}%</div>
                            <i class="fa-solid fa-chevron-down" id="icon-comp-{{ $loop->index }}" style="color:var(--text-muted);transition:transform 0.3s;font-size:0.9rem;margin-top:4px;"></i>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <div style="flex:1;height:8px;background:rgba(255,255,255,0.05);border-radius:4px;overflow:hidden;">
                            <div class="comp-bar" data-width="{{ $data['porcentaje'] }}" style="width:0%;height:100%;background:{{ $data['porcentaje'] >= 70 ? 'linear-gradient(to right,var(--primary),#4ade80)' : 'linear-gradient(to right,#f59e0b,#fbbf24)' }};border-radius:4px;transition:width 1.2s cubic-bezier(0.4,0,0.2,1);"></div>
                        </div>
                        <span style="font-size:0.8rem;font-weight:700;color:#fff;min-width:55px;text-align:right;">{{ $data['aprobados'] }}/{{ $data['total'] }}</span>
                    </div>
                </div>

                <!-- Cuerpo del Accordion -->
                <div id="comp-{{ $loop->index }}" style="display:none;padding:0 1.5rem 1.5rem;border-top:1px solid var(--glass-border);background:rgba(0,0,0,0.2);">
                    <div style="margin-top:1.25rem;display:flex;flex-direction:column;gap:0.75rem;">
                        <div style="font-size:0.6rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.1em;">Resultados de Aprendizaje</div>
                        @foreach($data['juicios'] as $juicio)
                        <div style="display:flex;align-items:center;gap:12px;padding:1rem 1.25rem;background:rgba(255,255,255,0.03);border-radius:14px;border:1px solid rgba(255,255,255,0.05);">
                            <div style="width:38px;height:38px;background:{{ $juicio->Estado==1 ? 'rgba(57,169,0,0.1)' : 'rgba(245,158,11,0.1)' }};border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid {{ $juicio->Estado==1 ? 'rgba(57,169,0,0.2)' : 'rgba(245,158,11,0.2)' }};flex-shrink:0;">
                                <i class="{{ $juicio->Estado==1 ? 'fa-solid fa-check' : 'fa-solid fa-clock' }}" style="color:{{ $juicio->Estado==1 ? 'var(--primary)' : '#f59e0b' }};"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:0.15rem;">
                                    <span style="font-size:0.72rem;font-weight:800;color:#fff;opacity:0.7;">{{ $juicio->resultado->Codigo ?? '' }}</span>
                                    <span style="font-size:0.65rem;font-weight:700;color:{{ $juicio->Estado==1 ? 'var(--primary)' : '#f59e0b' }};">{{ $juicio->Estado==1 ? '✓ APROBADO' : '⏳ POR APROBAR' }}</span>
                                </div>
                                <div style="font-size:0.78rem;color:#cbd5e1;line-height:1.4;">{{ $juicio->resultado->Nombre ?? 'Sin nombre' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach

            @if($avancePorCompetencia->isEmpty())
            <div style="text-align:center;padding:4rem 2rem;">
                <i class="fa-solid fa-folder-open" style="font-size:4rem;display:block;margin-bottom:1.5rem;color:var(--text-muted);opacity:0.2;"></i>
                <h4 style="color:var(--text-main);margin-bottom:0.5rem;">Sin registros</h4>
                <p style="color:var(--text-muted);font-size:0.9rem;">No se han encontrado juicios evaluativos para este aprendiz.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Botones de acción -->
<div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;">
    <a href="{{ route('aprendices.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Volver al Listado
    </a>
    <a href="{{ route('aprendices.pdf', $aprendiz->Id_Aprendiz) }}" class="btn btn-primary" target="_blank">
        <i class="fa-solid fa-file-pdf"></i> Descargar PDF
    </a>
    <button onclick="window.print()" class="btn btn-outline">
        <i class="fa-solid fa-print"></i> Imprimir
    </button>
</div>

<script>
    function toggleCompetencia(id) {
        const el   = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        const show = el.style.display === 'none';
        el.style.display  = show ? 'block' : 'none';
        icon.style.transform = show ? 'rotate(180deg)' : 'rotate(0deg)';
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Barra global
        setTimeout(() => {
            document.getElementById('global-bar').style.width = '{{ $porcentajeGlobal }}%';
        }, 200);

        // Barras de competencia
        document.querySelectorAll('.comp-bar').forEach(bar => {
            setTimeout(() => { bar.style.width = bar.dataset.width + '%'; }, 300);
        });

        // Radar Chart
        @if($avancePorCompetencia->count() > 0)
        const radarCtx = document.getElementById('radarChart').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: @json($avancePorCompetencia->pluck('codigo')->values()),
                datasets: [{
                    label: 'Avance (%)',
                    data: @json($avancePorCompetencia->pluck('porcentaje')->values()),
                    backgroundColor: 'rgba(57, 169, 0, 0.15)',
                    borderColor: '#39A900',
                    borderWidth: 2,
                    pointBackgroundColor: '#39A900',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                animation: { duration: 1000 },
                scales: {
                    r: {
                        min: 0, max: 100,
                        angleLines: { color: 'rgba(255,255,255,0.08)' },
                        grid:       { color: 'rgba(255,255,255,0.08)' },
                        ticks: { display: false, stepSize: 25 },
                        pointLabels: { color: '#94a3b8', font: { size: 10, weight: '700' } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.dataset.label}: ${Math.round(ctx.raw)}%`
                        }
                    }
                }
            }
        });
        @endif
    });
</script>

@endsection
