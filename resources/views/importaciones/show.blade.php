@extends('layouts.app')

@section('title', 'Cambios de la Importación')

@php
    use App\Models\ImportacionCambio as C;
    $resumen      = $importacion->resumen;
    $cargaInicial = $resumen['carga_inicial'] ?? false;
    $hayCambios   = $cambios->isNotEmpty();
    $tarjetas = [
        [C::JUICIO_APROBADO,  'fa-circle-check',        '#39A900', 'rgba(57,169,0,0.1)',   '+'],
        [C::JUICIO_REVERTIDO, 'fa-rotate-left',         '#ef4444', 'rgba(239,68,68,0.1)',  ''],
        [C::APRENDIZ_ESTADO,  'fa-user-pen',            '#f59e0b', 'rgba(245,158,11,0.1)', ''],
        [C::APRENDIZ_NUEVO,   'fa-user-plus',           '#0ea5e9', 'rgba(14,165,233,0.1)', ''],
        [C::APRENDIZ_AUSENTE, 'fa-user-slash',          '#94a3b8', 'rgba(148,163,184,0.1)', ''],
    ];
    $deAprendices = collect([C::APRENDIZ_ESTADO, C::APRENDIZ_NUEVO, C::APRENDIZ_AUSENTE, C::APRENDIZ_MOVIDO, C::APRENDIZ_NO_TRASLADADO])
        ->flatMap(fn ($t) => $cambios->get($t, collect()));
    // Decisión tomada en la pantalla «Revisa antes de aplicar» (sin decisión: 'consultar').
    $decision = [
        'preservar' => 'conservar los aprobados y dejar a cada aprendiz en su ficha',
        'trasladar' => 'trasladar a esta ficha conservando los aprobados',
        'forzar'    => 'aplicar el reporte tal cual',
    ][$resumen['politica'] ?? ''] ?? null;
@endphp

@section('content')

<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
    <div>
        <a href="{{ route('importaciones.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
            <i class="fa-solid fa-arrow-left"></i> Historial de importaciones
        </a>
        <h2 style="margin: 0; font-size: 1.6rem;">
            <i class="fa-solid fa-file-excel" style="color: var(--primary);"></i> {{ $importacion->nombre_archivo }}
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.4rem 0 0;">
            @if($importacion->id_ficha)
                Ficha <strong style="color: var(--primary);">{{ $importacion->id_ficha }}</strong> ·
            @endif
            {{ $importacion->created_at->format('d/m/Y H:i') }}
            @if($importacion->usuario) · subido por {{ $importacion->usuario->name }} @endif
            @if($anterior)
                · comparado con la carga del
                <a href="{{ route('importaciones.show', $anterior) }}" style="color: var(--accent);">{{ $anterior->created_at->format('d/m/Y H:i') }}</a>
            @endif
        </p>
    </div>
    @if($importacion->id_ficha && \App\Models\Ficha::whereKey($importacion->id_ficha)->exists())
        <a href="{{ route('fichas.historial', $importacion->id_ficha) }}" class="btn btn-outline">
            <i class="fa-solid fa-chart-line"></i> Línea de tiempo de la ficha
        </a>
    @endif
</div>

@include('importaciones._errores_filas')

@if(! $resumen)
    {{-- Importación fallida o sin registros: no hay foto ni cambios. --}}
    <div class="card" style="padding: 2rem; text-align: center; color: var(--text-muted);">
        <i class="fa-solid fa-circle-exclamation" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.75rem;"></i>
        Esta importación no registró datos ({{ $importacion->estado_visual['label'] }}).
        @if($importacion->detalle)
            <div style="margin-top: 0.75rem; font-size: 0.85rem; white-space: pre-line;">{{ $importacion->detalle }}</div>
        @endif
    </div>
@else

    @if($cargaInicial)
        <div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 2rem; border: 1px solid rgba(14,165,233,0.3); background: rgba(14,165,233,0.05);">
            <strong style="color: #7dd3fc;"><i class="fa-solid fa-flag-checkered"></i> Carga inicial de la ficha.</strong>
            <span style="color: var(--text-muted); font-size: 0.9rem;">Se guardó la foto de la ficha; desde la próxima importación verás aquí qué cambió.</span>
        </div>
    @elseif(! $hayCambios)
        <div class="card" style="padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
            <strong><i class="fa-solid fa-equals" style="color: var(--text-muted);"></i> Sin cambios frente a la carga anterior.</strong>
            <span style="color: var(--text-muted); font-size: 0.9rem;">El reporte trae exactamente lo mismo que ya estaba registrado.</span>
        </div>
    @endif

    @if($decision)
        <div class="card" style="padding: 1.1rem 1.5rem; margin-bottom: 2rem; border: 1px solid rgba(245,158,11,0.3); background: rgba(245,158,11,0.05);">
            <strong style="color: #fcd34d;"><i class="fa-solid fa-scale-balanced"></i> Aplicado con tu decisión: {{ $decision }}.</strong>
            <span style="color: var(--text-muted); font-size: 0.88rem;">
                {{ collect([
                    $importacion->conteoCambios(C::JUICIO_PROTEGIDO) ? $importacion->conteoCambios(C::JUICIO_PROTEGIDO) . ' aprobado(s) protegido(s)' : null,
                    $importacion->conteoCambios(C::APRENDIZ_NO_TRASLADADO) ? $importacion->conteoCambios(C::APRENDIZ_NO_TRASLADADO) . ' aprendiz(es) no trasladado(s)' : null,
                    $importacion->conteoCambios(C::APRENDIZ_MOVIDO) ? $importacion->conteoCambios(C::APRENDIZ_MOVIDO) . ' trasladado(s) a esta ficha' : null,
                    $importacion->conteoCambios(C::JUICIO_REVERTIDO) ? $importacion->conteoCambios(C::JUICIO_REVERTIDO) . ' aprobación(es) revertida(s)' : null,
                ])->filter()->implode(' · ') }}
            </span>
        </div>
    @endif

    @if(! $cargaInicial)
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
        @foreach($tarjetas as [$tipo, $icono, $color, $fondo, $prefijo])
            @php $n = $importacion->conteoCambios($tipo); @endphp
            <div class="card stat-card" style="padding: 1.1rem 1.25rem; {{ $n === 0 ? 'opacity: 0.55;' : '' }}">
                <div class="icon-box" style="background: {{ $fondo }}; color: {{ $color }}; width: 42px; height: 42px; font-size: 1.1rem;">
                    <i class="fa-solid {{ $icono }}"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.5rem; {{ $n > 0 ? "color: {$color};" : '' }}">{{ $n > 0 ? $prefijo : '' }}{{ $n }}</div>
                    <div class="stat-label" style="font-size: 0.68rem;">{{ C::ETIQUETAS[$tipo] }}</div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    @if($importacion->conteoCambios(C::JUICIO_REVERTIDO) > 0)
        <div class="card" style="padding: 1.5rem; margin-bottom: 2rem; border: 1px solid rgba(239,68,68,0.35); background: rgba(239,68,68,0.05);">
            <h3 style="margin: 0 0 0.5rem; color: #fca5a5; font-size: 1.05rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> Aprobaciones revertidas
            </h3>
            <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.85rem;">
                Estos juicios estaban APROBADOS y este reporte los trae POR EVALUAR. Si subiste un reporte más antiguo
                por error, vuelve a importar el más reciente para restaurarlos.
            </p>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                @foreach($cambios->get(C::JUICIO_REVERTIDO) as $c)
                    <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 0.5rem 0;">{{ $c->aprendiz?->nombre_completo ?? '—' }}</td>
                        <td style="padding: 0.5rem 0; color: var(--text-muted);">{{ $c->resultado->Codigo ?? '—' }} · {{ \Illuminate\Support\Str::limit($c->resultado->Nombre ?? '', 70) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if($importacion->conteoCambios(C::JUICIO_PROTEGIDO) > 0)
        <div class="card" style="padding: 1.5rem; margin-bottom: 2rem; border: 1px solid rgba(14,165,233,0.3); background: rgba(14,165,233,0.04);">
            <h3 style="margin: 0 0 0.5rem; color: #7dd3fc; font-size: 1.05rem;">
                <i class="fa-solid fa-shield-halved"></i> Aprobados protegidos
            </h3>
            <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.85rem;">
                El reporte traía estos juicios POR EVALUAR; por tu decisión se conservaron APROBADOS.
            </p>
            <div style="max-height: 320px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    @foreach($cambios->get(C::JUICIO_PROTEGIDO) as $c)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.5rem 0;">{{ $c->aprendiz?->nombre_completo ?? '—' }}</td>
                            <td style="padding: 0.5rem 0; color: var(--text-muted);">{{ $c->resultado->Codigo ?? '—' }} · {{ \Illuminate\Support\Str::limit($c->resultado->Nombre ?? '', 70) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem; align-items: start;">

        @if($aprobadosPorAprendiz->isNotEmpty())
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 1rem; font-size: 1.05rem;"><i class="fa-solid fa-user-check" style="color: var(--primary);"></i> Quién avanzó</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem;">
                        <th style="padding: 0.5rem 0;">APRENDIZ</th><th>NUEVOS APROBADOS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aprobadosPorAprendiz as $fila)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.55rem 0;">
                                @if($fila['aprendiz'])
                                    <a href="{{ route('aprendices.show', $fila['aprendiz']->Id_Aprendiz) }}" style="color: #f1f5f9; text-decoration: none; font-weight: 600;">{{ $fila['aprendiz']->nombre_completo }}</a>
                                    <div style="color: var(--text-muted); font-size: 0.72rem;">{{ $fila['aprendiz']->Documento }}</div>
                                @else — @endif
                            </td>
                            <td>
                                <strong style="color: var(--primary);">+{{ $fila['cambios']->count() }}</strong>
                                <span style="color: var(--text-muted); font-size: 0.72rem;">
                                    RAP {{ $fila['cambios']->map(fn ($c) => $c->resultado->Codigo ?? '?')->take(6)->implode(', ') }}{{ $fila['cambios']->count() > 6 ? ', …' : '' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 1rem; font-size: 1.05rem;"><i class="fa-solid fa-list-check" style="color: var(--accent);"></i> Qué se aprobó</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem;">
                        <th style="padding: 0.5rem 0;">RESULTADO DE APRENDIZAJE</th><th style="text-align: right;">APRENDICES</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aprobadosPorRap as $fila)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.55rem 0;">
                                <strong>{{ $fila['resultado']->Codigo ?? '—' }}</strong>
                                <span style="color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($fila['resultado']->Nombre ?? '', 60) }}</span>
                                <div style="color: var(--text-muted); font-size: 0.72rem;">{{ $fila['resultado']?->competencia?->Codigo }} {{ \Illuminate\Support\Str::limit($fila['resultado']?->competencia?->Nombre ?? '', 50) }}</div>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary);">{{ $fila['aprendices'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($deAprendices->isNotEmpty())
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 1rem; font-size: 1.05rem;"><i class="fa-solid fa-users-gear" style="color: #f59e0b;"></i> Cambios en los aprendices</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <tbody>
                    @foreach($deAprendices as $c)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.55rem 0;">
                                @if($c->aprendiz)
                                    <a href="{{ route('aprendices.show', $c->aprendiz->Id_Aprendiz) }}" style="color: #f1f5f9; text-decoration: none; font-weight: 600;">{{ $c->aprendiz->nombre_completo }}</a>
                                @else — @endif
                            </td>
                            <td style="color: var(--text-muted);">{{ [
                                C::APRENDIZ_ESTADO  => 'Cambió de estado',
                                C::APRENDIZ_NUEVO   => 'Nuevo en la ficha',
                                C::APRENDIZ_AUSENTE => 'No vino en el reporte',
                                C::APRENDIZ_MOVIDO  => 'Llegó de otra ficha',
                                C::APRENDIZ_NO_TRASLADADO => 'Se dejó en su ficha',
                            ][$c->tipo] ?? $c->tipo }}</td>
                            <td style="text-align: right;">
                                @if($c->tipo === C::APRENDIZ_MOVIDO)
                                    Ficha {{ $c->valor_anterior }} → {{ $c->valor_nuevo }}
                                @elseif($c->tipo === C::APRENDIZ_NO_TRASLADADO)
                                    Sigue en la ficha {{ $c->valor_anterior }}
                                @elseif($c->tipo === C::APRENDIZ_AUSENTE)
                                    Estaba {{ $c->valor_anterior }}
                                @elseif($c->valor_anterior)
                                    {{ $c->valor_anterior }} → <strong>{{ $c->valor_nuevo }}</strong>
                                @else
                                    {{ $c->valor_nuevo }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 1rem; font-size: 1.05rem;"><i class="fa-solid fa-camera" style="color: var(--text-muted);"></i> Foto de la ficha tras esta carga</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <tr><td style="padding: 0.4rem 0; color: var(--text-muted);">Aprendices</td><td style="text-align: right; font-weight: 700;">{{ $resumen['aprendices'] }}</td></tr>
                @foreach($resumen['por_estado'] as $estado => $n)
                    <tr><td style="padding: 0.4rem 0 0.4rem 1rem; color: var(--text-muted);">{{ $estado }}</td><td style="text-align: right;">{{ $n }}</td></tr>
                @endforeach
                <tr style="border-top: 1px solid rgba(255,255,255,0.05);"><td style="padding: 0.4rem 0; color: var(--text-muted);">Juicios aprobados (en formación)</td><td style="text-align: right; font-weight: 700; color: var(--primary);">{{ $resumen['aprobados_en_formacion'] }}</td></tr>
                <tr><td style="padding: 0.4rem 0; color: var(--text-muted);">Juicios por evaluar (en formación)</td><td style="text-align: right; font-weight: 700;">{{ $resumen['pendientes_en_formacion'] }}</td></tr>
            </table>
        </div>
    </div>
@endif

@endsection
