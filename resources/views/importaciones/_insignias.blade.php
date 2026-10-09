{{-- Insignias con el resumen de cambios de una importación ($imp). --}}
@php
    use App\Models\ImportacionCambio as C;
    $r = $imp->resumen;
    $insignias = $r && ! ($r['carga_inicial'] ?? false) ? array_filter([
        [$imp->conteoCambios(C::JUICIO_APROBADO), '+%d aprobados', '#86efac', 'rgba(57,169,0,0.12)'],
        [$imp->conteoCambios(C::JUICIO_REVERTIDO), '%d revertidos', '#fca5a5', 'rgba(239,68,68,0.12)'],
        [$imp->conteoCambios(C::JUICIO_PROTEGIDO), '%d protegidos', '#7dd3fc', 'rgba(14,165,233,0.12)'],
        [$imp->conteoCambios(C::APRENDIZ_ESTADO), '%d cambio(s) de estado', '#fde68a', 'rgba(245,158,11,0.12)'],
        [$imp->conteoCambios(C::APRENDIZ_NUEVO), '%d nuevo(s)', '#7dd3fc', 'rgba(14,165,233,0.12)'],
        [$imp->conteoCambios(C::APRENDIZ_AUSENTE), '%d ausente(s)', '#cbd5e1', 'rgba(148,163,184,0.12)'],
    ], fn ($i) => $i[0] > 0) : [];
@endphp
@if(! $r)
    <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
@elseif($r['carga_inicial'] ?? false)
    <span style="padding: 0.2rem 0.55rem; border-radius: 20px; font-size: 0.68rem; font-weight: 700; color: #7dd3fc; background: rgba(14,165,233,0.12);">Carga inicial</span>
@elseif(! $insignias)
    <span style="color: var(--text-muted); font-size: 0.75rem;">Sin cambios</span>
@else
    @foreach($insignias as [$n, $formato, $color, $fondo])
        <span style="display: inline-block; margin: 0.1rem 0.2rem 0.1rem 0; padding: 0.2rem 0.55rem; border-radius: 20px; font-size: 0.68rem; font-weight: 700; color: {{ $color }}; background: {{ $fondo }};">{{ sprintf($formato, $n) }}</span>
    @endforeach
@endif
