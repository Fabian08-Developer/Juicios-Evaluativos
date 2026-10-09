@extends('layouts.app')

@section('title', 'Comparar cargas')

@php
    use App\Models\ImportacionCambio as C;
    $ra = $a->resumen;
    $rb = $b->resumen;
    $signo = fn (int $n) => $n > 0 ? "+{$n}" : (string) $n;
    $tiles = [
        [C::JUICIO_APROBADO,  'fa-circle-check', '#39A900', '+'],
        [C::JUICIO_REVERTIDO, 'fa-rotate-left',  '#ef4444', ''],
        [C::JUICIO_PROTEGIDO, 'fa-shield-halved', '#38bdf8', ''],
        [C::APRENDIZ_ESTADO,  'fa-user-pen',     '#f59e0b', ''],
        [C::APRENDIZ_NUEVO,   'fa-user-plus',    '#0ea5e9', ''],
        [C::APRENDIZ_AUSENTE, 'fa-user-slash',   '#94a3b8', ''],
    ];
@endphp

@section('content')
<style>
    .comparar-hitos { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1.25rem; align-items: stretch; margin-bottom: 2rem; }
    .comparar-flecha { display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 90px; }
    @media (max-width: 860px) {
        .comparar-hitos { grid-template-columns: 1fr; }
        .comparar-flecha { flex-direction: row; gap: 0.75rem; }
        .comparar-flecha .fa-arrow-right { transform: rotate(90deg); }
    }
    .tabla-comparar th, .tabla-comparar td { padding: 0.55rem 0.75rem; }
    .tabla-comparar th:first-child, .tabla-comparar td:first-child { padding-left: 0; }
</style>

<div style="max-width: 1150px; margin: 0 auto;">
    <a href="{{ route('importaciones.index', ['ficha' => $a->id_ficha]) }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <i class="fa-solid fa-arrow-left"></i> Historial de importaciones
    </a>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.75rem;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 46px; height: 46px; border-radius: 14px; background: rgba(57,169,0,0.15); border: 1px solid rgba(57,169,0,0.3); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.3rem; flex-shrink: 0;">
                <i class="fa-solid fa-code-compare"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.6rem; font-weight: 800; color: #fff;">Avance de la ficha {{ $a->id_ficha }}</h2>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0.25rem 0 0;">
                    {{ $a->ficha?->programa?->Nombre ?? 'Programa Sofia Plus' }} · {{ $dias }} día(s) entre ambas cargas
                </p>
            </div>
        </div>
        @if($fichaExiste)
            <a href="{{ route('fichas.historial', $a->id_ficha) }}" class="btn btn-outline"><i class="fa-solid fa-chart-line"></i> Línea de tiempo</a>
        @endif
    </div>

    <!-- Las dos fotos -->
    <div class="comparar-hitos">
        @foreach([['Desde', $a, $ra, 'rgba(255,255,255,0.1)', '#94a3b8'], ['Hasta', $b, $rb, 'rgba(57,169,0,0.35)', 'var(--primary)']] as [$titulo, $imp, $r, $borde, $color])
            @if($loop->last)
                <div class="comparar-flecha">
                    <div style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid var(--primary); background: rgba(57,169,0,0.1); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.4rem; text-align: center;">{{ $dias }} día(s)<br>{{ $tramo->count() }} carga(s)</div>
                </div>
            @endif
            <div class="card" style="padding: 1.5rem; border-color: {{ $borde }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <span style="color: {{ $color }}; font-size: 0.72rem; font-weight: 800; text-transform: uppercase;">{{ $titulo }}</span>
                    <a href="{{ route('importaciones.show', $imp) }}" style="font-size: 0.75rem; color: var(--accent);">#{{ $imp->id }} · ver cambios</a>
                </div>
                <div style="font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $imp->nombre_archivo }}">
                    <i class="fa-solid fa-file-excel" style="color: var(--primary);"></i> {{ $imp->nombre_archivo }}
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin: 0.3rem 0 1rem;">
                    {{ $imp->created_at->format('d/m/Y H:i') }}@if($imp->usuario) · {{ $imp->usuario->name }}@endif
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <tr><td style="padding: 0.3rem 0; color: var(--text-muted);">Aprendices</td><td style="text-align: right; font-weight: 700;">{{ $r['aprendices'] }}</td></tr>
                    <tr><td style="padding: 0.3rem 0; color: var(--text-muted);">Aprobados (en formación)</td><td style="text-align: right; font-weight: 700; color: var(--primary);">{{ $r['aprobados_en_formacion'] }}</td></tr>
                    <tr><td style="padding: 0.3rem 0; color: var(--text-muted);">Por evaluar (en formación)</td><td style="text-align: right; font-weight: 700;">{{ $r['pendientes_en_formacion'] }}</td></tr>
                </table>
            </div>
        @endforeach
    </div>

    <!-- Diferencia -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin: 0 0 1.25rem; font-size: 1.05rem;"><i class="fa-solid fa-chart-line" style="color: var(--primary);"></i> Qué cambió entre ambas cargas</h3>
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div style="background: rgba(57,169,0,0.06); border: 1px solid rgba(57,169,0,0.25); border-radius: 14px; padding: 1rem;">
                <div style="font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Aprobados (en formación)</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: {{ $delta['aprobados'] >= 0 ? 'var(--primary)' : '#ef4444' }};">{{ $signo($delta['aprobados']) }}</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 14px; padding: 1rem;">
                <div style="font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Por evaluar (en formación)</div>
                <div style="font-size: 1.6rem; font-weight: 800;">{{ $signo($delta['pendientes']) }}</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 14px; padding: 1rem;">
                <div style="font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Aprendices</div>
                <div style="font-size: 1.6rem; font-weight: 800;">{{ $signo($delta['aprendices']) }}</div>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @foreach($tiles as [$tipo, $icono, $color, $prefijo])
                @php $n = $conteo[$tipo] ?? 0; @endphp
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.78rem; background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); {{ $n ? '' : 'opacity: 0.45;' }}">
                    <i class="fa-solid {{ $icono }}" style="color: {{ $color }};"></i>
                    <strong style="color: {{ $n ? $color : 'inherit' }};">{{ $n ? $prefijo : '' }}{{ $n }}</strong>
                    <span style="color: var(--text-muted);">{{ mb_strtolower(C::ETIQUETAS[$tipo]) }}</span>
                </span>
            @endforeach
        </div>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 1.5rem; align-items: start;">
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 0.35rem; font-size: 1.05rem;"><i class="fa-solid fa-user-check" style="color: var(--primary);"></i> Quién avanzó</h3>
            <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.8rem;">Efecto neto: un juicio aprobado y luego revertido no cuenta.</p>
            @if($porAprendiz->isEmpty())
                <div style="color: var(--text-muted); font-size: 0.85rem;">Ningún juicio cambió entre estas cargas.</div>
            @else
                <div style="max-height: 420px; overflow-y: auto;">
                    <table class="tabla-comparar" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem;">
                                <th>APRENDIZ</th><th style="text-align: right;">APROBADOS</th><th style="text-align: right;">REVERTIDOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($porAprendiz as $fila)
                                <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                                    <td>
                                        @if($fila['aprendiz'])
                                            <a href="{{ route('aprendices.show', $fila['aprendiz']->Id_Aprendiz) }}" style="color: #f1f5f9; text-decoration: none; font-weight: 600;">{{ $fila['aprendiz']->nombre_completo }}</a>
                                            <div style="color: var(--text-muted); font-size: 0.72rem;">{{ $fila['aprendiz']->Documento }}</div>
                                        @else — @endif
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: var(--primary);">{{ $fila['avances'] ? '+' . $fila['avances'] : '—' }}</td>
                                    <td style="text-align: right; font-weight: 700; color: #fca5a5;">{{ $fila['retrocesos'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 1rem; font-size: 1.05rem;"><i class="fa-solid fa-timeline" style="color: var(--accent);"></i> Cargas en este tramo</h3>
            <table class="tabla-comparar" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                @foreach($tramo as $carga)
                    <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                        <td style="white-space: nowrap; color: var(--text-muted);">{{ $carga->created_at->format('d/m/Y H:i') }}</td>
                        <td>@include('importaciones._insignias', ['imp' => $carga])</td>
                        <td style="text-align: right;"><a href="{{ route('importaciones.show', $carga) }}" style="color: var(--accent); font-size: 0.78rem; white-space: nowrap;">Ver →</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection
