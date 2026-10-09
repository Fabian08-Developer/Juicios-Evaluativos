@extends('layouts.app')

@section('title', 'Revisa antes de aplicar')

@php
    use App\Models\ImportacionCambio as C;
    $a           = $analisis;
    $conteo      = $a['conteo'];
    $nRevertidos = $conteo[C::JUICIO_REVERTIDO] ?? 0;
    $nMovidos    = count($a['movidos']);
    $hayRev      = $nRevertidos > 0;
    $hayMov      = $nMovidos > 0;
    $fichasOrigen = collect($a['movidos'])->pluck('ficha_actual')->unique()->sort()->values();
    $etiquetaFichas = $fichasOrigen->count() === 1 ? 'la ficha ' . $fichasOrigen->first() : 'otras fichas (' . $fichasOrigen->implode(', ') . ')';
    $otros = array_filter([
        ($conteo[C::APRENDIZ_NUEVO] ?? 0) ? ($conteo[C::APRENDIZ_NUEVO] . ' aprendiz(es) nuevo(s)') : null,
        ($conteo[C::APRENDIZ_AUSENTE] ?? 0) ? ($conteo[C::APRENDIZ_AUSENTE] . ' ausente(s) (no se borran)') : null,
        ($conteo[C::APRENDIZ_ESTADO] ?? 0) ? ($conteo[C::APRENDIZ_ESTADO] . ' cambio(s) de estado') : null,
    ]);
@endphp

@section('content')
<div style="max-width: 1100px; margin: 0 auto;">

    <a href="{{ route('aprendices.upload') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <i class="fa-solid fa-arrow-left"></i> Subir otro archivo
    </a>

    <!-- Encabezado -->
    <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 1rem;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); display: flex; align-items: center; justify-content: center; color: #fbbf24; font-size: 1.3rem; flex-shrink: 0;">
            <i class="fa-solid fa-scale-balanced"></i>
        </div>
        <div>
            <h2 style="margin: 0; font-size: 1.6rem; font-weight: 800; color: #fff;">Revisa antes de aplicar</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.3rem 0 0; line-height: 1.5;">
                El reporte <strong style="color: #fff;">{{ $nombre }}</strong> de la
                <strong style="color: var(--primary);">ficha {{ $a['ficha'] }}</strong> todavía <strong style="color: #fff;">no se aplicó</strong>:
                @if($hayRev && $hayMov)
                    desharía aprobaciones y sacaría aprendices de {{ $etiquetaFichas }}.
                @elseif($hayRev)
                    desharía aprobaciones que ya están registradas.
                @else
                    trae aprendices que hoy están en {{ $etiquetaFichas }}.
                @endif
                Elige cómo aplicarlo.
            </p>
        </div>
    </div>

    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 2rem;">
        @foreach(array_filter([
            ['fa-graduation-cap', $a['programa']],
            ['fa-table-list', number_format($a['registros']) . ' juicios en el archivo'],
            ['fa-users', $a['aprendices_archivo'] . ' aprendices'],
            $a['carga_inicial'] ? ['fa-flag-checkered', 'Primera carga de la ficha'] : null,
        ]) as [$icono, $texto])
            @if($texto)
                <span style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #cbd5e1; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid {{ $icono }}" style="color: var(--primary);"></i> {{ $texto }}
                </span>
            @endif
        @endforeach
    </div>

    <!-- Indicadores -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
        <div class="card stat-card" style="padding: 1.1rem 1.25rem; {{ $hayRev ? 'border-color: rgba(239,68,68,0.35); background: rgba(239,68,68,0.05);' : 'opacity: 0.55;' }}">
            <div class="icon-box" style="background: rgba(239,68,68,0.15); color: #ef4444; width: 42px; height: 42px; font-size: 1.1rem;"><i class="fa-solid fa-rotate-left"></i></div>
            <div>
                <div class="stat-value" style="font-size: 1.5rem; {{ $hayRev ? 'color: #ef4444;' : '' }}">{{ $nRevertidos }}</div>
                <div class="stat-label" style="font-size: 0.68rem;">Aprobados que se perderían</div>
            </div>
        </div>
        <div class="card stat-card" style="padding: 1.1rem 1.25rem; {{ $hayMov ? 'border-color: rgba(14,165,233,0.35); background: rgba(14,165,233,0.05);' : 'opacity: 0.55;' }}">
            <div class="icon-box" style="background: rgba(14,165,233,0.15); color: #0ea5e9; width: 42px; height: 42px; font-size: 1.1rem;"><i class="fa-solid fa-right-left"></i></div>
            <div>
                <div class="stat-value" style="font-size: 1.5rem; {{ $hayMov ? 'color: #38bdf8;' : '' }}">{{ $nMovidos }}</div>
                <div class="stat-label" style="font-size: 0.68rem;">Aprendices de otra ficha</div>
            </div>
        </div>
        <div class="card stat-card" style="padding: 1.1rem 1.25rem;">
            <div class="icon-box" style="background: rgba(57,169,0,0.15); color: var(--primary); width: 42px; height: 42px; font-size: 1.1rem;"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                @if($a['carga_inicial'])
                    <div class="stat-value" style="font-size: 1.1rem;">Carga inicial</div>
                    <div class="stat-label" style="font-size: 0.68rem;">Sin carga anterior con qué comparar</div>
                @else
                    <div class="stat-value" style="font-size: 1.5rem; color: var(--primary);">+{{ $conteo[C::JUICIO_APROBADO] ?? 0 }}</div>
                    <div class="stat-label" style="font-size: 0.68rem;">Nuevos aprobados en el reporte</div>
                @endif
            </div>
        </div>
    </div>

    @if($otros)
        <p style="margin: -1rem 0 2rem; color: var(--text-muted); font-size: 0.82rem;">
            <i class="fa-solid fa-circle-info"></i> Además: {{ implode(' · ', $otros) }}.
        </p>
    @endif

    @if($hayRev)
    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem; border-color: rgba(239,68,68,0.3);">
        <h3 style="margin: 0 0 0.4rem; color: #fca5a5; font-size: 1.05rem;"><i class="fa-solid fa-triangle-exclamation"></i> Aprobaciones que el reporte desharía</h3>
        <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
            Estos juicios están <strong style="color: var(--primary);">APROBADOS</strong> en el sistema y el reporte los trae
            <strong style="color: #fca5a5;">POR EVALUAR</strong>. Suele pasar al subir un reporte descargado antes que el último que se cargó.
        </p>
        <div style="max-height: 320px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem; background: rgba(0,0,0,0.25);">
                        <th style="padding: 0.65rem 1rem;">APRENDIZ</th>
                        <th style="padding: 0.65rem 1rem;">RESULTADOS DE APRENDIZAJE (RAP)</th>
                        <th style="padding: 0.65rem 1rem; text-align: right;">JUICIOS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($a['revertidos'] as $fila)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.6rem 1rem;">
                                <div style="font-weight: 600; color: #f1f5f9;">{{ $fila['nombre'] }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $fila['documento'] }}</div>
                            </td>
                            <td style="padding: 0.6rem 1rem; color: #cbd5e1;">
                                @foreach($fila['raps'] as $codigo)
                                    <span title="{{ $a['raps'][$codigo] ?? '' }}" style="display: inline-block; margin: 0.1rem 0.2rem 0.1rem 0; padding: 0.1rem 0.45rem; border-radius: 6px; background: rgba(255,255,255,0.05); font-size: 0.75rem;">{{ $codigo }}</span>
                                @endforeach
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: right; font-weight: 700; color: #fca5a5;">{{ count($fila['raps']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($hayMov)
    <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem; border-color: rgba(14,165,233,0.3);">
        <h3 style="margin: 0 0 0.4rem; color: #38bdf8; font-size: 1.05rem;"><i class="fa-solid fa-right-left"></i> Aprendices que hoy están en otra ficha</h3>
        <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">
            Cada aprendiz pertenece a una sola ficha. Si fue trasladado en Sofia Plus, trasládalo aquí también; si no, déjalo en su ficha
            (sus juicios de este reporte se registran igual).
        </p>
        <div style="max-height: 280px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem; background: rgba(0,0,0,0.25);">
                        <th style="padding: 0.65rem 1rem;">APRENDIZ</th>
                        <th style="padding: 0.65rem 1rem; text-align: right;">FICHA ACTUAL → FICHA DEL REPORTE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($a['movidos'] as $fila)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.6rem 1rem;">
                                <div style="font-weight: 600; color: #f1f5f9;">{{ $fila['nombre'] }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $fila['documento'] }}</div>
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: right; white-space: nowrap;">
                                {{ $fila['ficha_actual'] }} <i class="fa-solid fa-arrow-right" style="color: var(--text-muted); font-size: 0.75rem; margin: 0 0.3rem;"></i> <strong style="color: var(--primary);">{{ $a['ficha'] }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Decisión -->
    <div class="card" style="padding: 1.75rem; border-color: rgba(57,169,0,0.3);">
        <h3 style="margin: 0 0 0.35rem; font-size: 1.15rem; font-weight: 800; color: #fff;">¿Cómo quieres aplicar este reporte?</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 1.5rem;">Lo que elijas queda registrado en el historial de la importación.</p>

        @php
            $opciones = [];
            $opciones[] = [
                'accion' => 'preservar', 'recomendado' => true, 'icono' => 'fa-shield-halved', 'boton' => 'btn-primary',
                'titulo' => $hayRev && $hayMov ? 'Conservar los aprobados y dejar a cada aprendiz en su ficha'
                          : ($hayRev ? 'Conservar los aprobados' : 'Dejar a cada aprendiz en su ficha actual'),
                'texto'  => trim(($hayRev ? "Se aplica todo lo demás del reporte, pero los {$nRevertidos} juicio(s) aprobados no se tocan. " : '')
                          . ($hayMov ? "Los {$nMovidos} aprendiz(es) de otra ficha siguen en ella, con su estado de esa ficha." : '')),
                'etiqueta' => 'Aplicar conservando',
            ];
            if ($hayMov) {
                $opciones[] = [
                    'accion' => 'trasladar', 'icono' => 'fa-right-left', 'boton' => 'btn-outline',
                    'titulo' => "Trasladar a la ficha {$a['ficha']}" . ($hayRev ? ' (conservando los aprobados)' : ''),
                    'texto'  => "Los {$nMovidos} aprendiz(es) pasan a la ficha {$a['ficha']}; sus juicios anteriores se conservan."
                              . ($hayRev ? " Los {$nRevertidos} juicio(s) aprobados tampoco se tocan." : ''),
                    'etiqueta' => 'Trasladar',
                    'confirmar' => "¿Trasladar {$nMovidos} aprendiz(es) a la ficha {$a['ficha']}?",
                ];
            }
            if ($hayRev) {
                $opciones[] = [
                    'accion' => 'forzar', 'icono' => 'fa-triangle-exclamation', 'boton' => 'btn-danger', 'peligro' => true,
                    'titulo' => 'Aplicar el reporte tal cual',
                    'texto'  => "El reporte manda en todo: los {$nRevertidos} juicio(s) aprobados pasan a POR EVALUAR"
                              . ($hayMov ? " y los {$nMovidos} aprendiz(es) se trasladan" : '') . '. Úsalo solo si el reporte es el más reciente de Sofia Plus.',
                    'etiqueta' => 'Aplicar tal cual',
                    'confirmar' => "Los {$nRevertidos} juicio(s) aprobados pasarán a POR EVALUAR. ¿Aplicar el reporte tal cual?",
                ];
            }
            $opciones[] = [
                'accion' => 'cancelar', 'icono' => 'fa-xmark', 'boton' => 'btn-outline',
                'titulo' => 'Cancelar', 'texto' => 'No se modifica ningún dato. Útil si subiste el archivo equivocado.',
                'etiqueta' => 'Cancelar',
            ];
        @endphp

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($opciones as $o)
                <form method="POST" action="{{ route('aprendices.import.confirmar', $token) }}" class="form-decision" style="margin: 0;"
                      @isset($o['confirmar']) data-confirmar="{{ $o['confirmar'] }}" @endisset>
                    @csrf
                    <input type="hidden" name="accion" value="{{ $o['accion'] }}">
                    <div style="border-radius: 14px; padding: 1.1rem 1.35rem; display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; flex-wrap: wrap;
                                {{ ($o['recomendado'] ?? false) ? 'border: 2px solid var(--primary); background: rgba(57,169,0,0.06);'
                                   : (($o['peligro'] ?? false) ? 'border: 1px dashed rgba(239,68,68,0.35); background: rgba(239,68,68,0.03);'
                                   : 'border: 1px solid var(--glass-border); background: rgba(255,255,255,0.02);') }}">
                        <div style="flex: 1; min-width: 260px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.3rem; flex-wrap: wrap;">
                                @if($o['recomendado'] ?? false)
                                    <span style="background: var(--primary); color: #0b1120; font-size: 0.66rem; font-weight: 900; padding: 0.12rem 0.55rem; border-radius: 6px; text-transform: uppercase;">Recomendado</span>
                                @endif
                                <h4 style="margin: 0; font-size: 0.98rem; font-weight: 700; color: {{ ($o['peligro'] ?? false) ? '#fca5a5' : '#fff' }};">{{ $o['titulo'] }}</h4>
                            </div>
                            <p style="margin: 0; color: var(--text-muted); font-size: 0.82rem; line-height: 1.45;">{{ $o['texto'] }}</p>
                        </div>
                        <button type="submit" class="btn {{ $o['boton'] }}" style="white-space: nowrap;">
                            <i class="fa-solid {{ $o['icono'] }}"></i> {{ $o['etiqueta'] }}
                        </button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
</div>

<script>
    // Confirmación de las opciones delicadas y un solo envío (evita aplicar dos veces).
    document.querySelectorAll('.form-decision').forEach(form => {
        form.addEventListener('submit', e => {
            const pregunta = form.dataset.confirmar;
            if (pregunta && !confirm(pregunta)) {
                e.preventDefault();
                return;
            }
            document.querySelectorAll('.form-decision button').forEach(b => b.disabled = true);
            form.querySelector('button').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Aplicando…';
        });
    });
</script>
@endsection
