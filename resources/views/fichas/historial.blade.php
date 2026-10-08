@extends('layouts.app')

@section('title', 'Línea de Tiempo de la Ficha')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
    <div>
        <a href="{{ route('fichas.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
            <i class="fa-solid fa-arrow-left"></i> Fichas
        </a>
        <h2 style="margin: 0; font-size: 1.6rem;">Ficha <span style="color: var(--primary);">{{ $ficha->Id_Ficha }}</span></h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.4rem 0 0;">{{ $ficha->programa->Nombre ?? 'Sin programa' }} · {{ $cargas->count() }} carga(s) con historial</p>
    </div>
    <a href="{{ route('aprendices.upload') }}" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Subir reporte</a>
</div>

@if($cargas->isEmpty())
    <div class="card" style="padding: 3rem; text-align: center; color: var(--text-muted);">
        <i class="fa-solid fa-chart-line" style="font-size: 2.5rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
        Esta ficha todavía no tiene historial. Cada reporte que subas desde ahora queda registrado aquí.
    </div>
@else
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin: 0 0 0.25rem; font-size: 1.05rem;">Avance de los aprendices en formación</h3>
        <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.8rem;">Juicios aprobados y por evaluar tras cada carga (sin contar retirados ni trasladados).</p>
        <div style="position: relative; height: 320px;">
            <canvas id="grafico-historial" aria-label="Juicios aprobados y por evaluar por carga" role="img"></canvas>
        </div>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <h3 style="margin: 0 0 1rem; font-size: 1.05rem;">Cargas</h3>
        <style>#tabla-cargas th, #tabla-cargas td { padding: 0.6rem 0.75rem; } #tabla-cargas th:first-child, #tabla-cargas td:first-child { padding-left: 0; }</style>
        <div style="overflow-x: auto;">
            <table id="tabla-cargas" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem;">
                        <th style="padding: 0.6rem 0;">FECHA</th>
                        <th>ARCHIVO</th>
                        <th>SUBIDO POR</th>
                        <th style="text-align: right;">APROBADOS</th>
                        <th style="text-align: right;">POR EVALUAR</th>
                        <th>CAMBIOS FRENTE A LA ANTERIOR</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cargas->reverse() as $c)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.6rem 0; white-space: nowrap;">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                            <td style="color: #f1f5f9;">{{ $c->nombre_archivo }}</td>
                            <td style="color: var(--text-muted);">{{ $c->usuario->name ?? '—' }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ $c->resumen['aprobados_en_formacion'] ?? 0 }}</td>
                            <td style="text-align: right;">{{ $c->resumen['pendientes_en_formacion'] ?? 0 }}</td>
                            <td>@include('importaciones._insignias', ['imp' => $c])</td>
                            <td style="text-align: right;"><a href="{{ route('importaciones.show', $c) }}" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; white-space: nowrap;">Ver cambios</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const canvas = document.getElementById('grafico-historial');
            if (!canvas || typeof Chart === 'undefined') return;

            const serie = @json($serie);
            const muted = '#94a3b8';

            // Etiqueta directa al final de cada línea (además de la leyenda), en tinta de texto.
            const etiquetasDirectas = {
                id: 'etiquetasDirectas',
                afterDatasetsDraw(chart) {
                    const { ctx } = chart;
                    ctx.save();
                    ctx.font = '600 11px Inter, sans-serif';
                    ctx.fillStyle = muted;
                    ctx.textBaseline = 'middle';
                    chart.data.datasets.forEach((ds, i) => {
                        const puntos = chart.getDatasetMeta(i).data;
                        const ultimo = puntos[puntos.length - 1];
                        if (ultimo) ctx.fillText(ds.label, ultimo.x + 10, ultimo.y);
                    });
                    ctx.restore();
                },
            };

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: serie.etiquetas,
                    datasets: [
                        { label: 'Aprobados', data: serie.aprobados, borderColor: '#39A900', backgroundColor: '#39A900' },
                        { label: 'Por evaluar', data: serie.pendientes, borderColor: '#6366f1', backgroundColor: '#6366f1' },
                    ].map(ds => ({ ...ds, borderWidth: 2, pointRadius: 4, pointHoverRadius: 6, pointBorderColor: '#172032', pointBorderWidth: 2, tension: 0 })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { right: 80 } },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: muted, font: { size: 11 }, usePointStyle: true } },
                        tooltip: { padding: 10 },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: muted, font: { size: 10 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: muted, font: { size: 10 }, precision: 0 } },
                    },
                },
                plugins: [etiquetasDirectas],
            });
        })();
    </script>
@endif

@endsection
