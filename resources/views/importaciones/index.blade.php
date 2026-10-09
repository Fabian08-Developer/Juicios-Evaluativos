@extends('layouts.app')

@section('title', 'Historial de Importaciones')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="margin: 0; font-size: 1.75rem;">Historial de Importaciones</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Cada reporte de Sofia Plus procesado, qué cambió y cómo avanzó cada ficha</p>
    </div>
    <a href="{{ route('aprendices.upload') }}" class="btn btn-primary">
        <i class="fa-solid fa-cloud-arrow-up"></i> Nueva Importación
    </a>
</div>

<!-- Filtro por ficha -->
<div class="card" style="margin-bottom: 2rem; padding: 1.1rem 1.5rem;">
    <form method="GET" action="{{ route('importaciones.index') }}" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <label for="ficha" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
            <i class="fa-solid fa-filter"></i> Ficha
        </label>
        <select name="ficha" id="ficha" class="form-control" style="flex: 1; min-width: 240px; padding: 0.6rem 1rem; font-size: 0.9rem;" onchange="this.form.submit()">
            <option value="">Todas las fichas</option>
            @foreach($fichas as $f)
                <option value="{{ $f->Id_Ficha }}" @selected($fichaFiltro === (string) $f->Id_Ficha)>
                    {{ $f->Id_Ficha }} — {{ $f->programa->Nombre ?? 'Sin programa' }}
                </option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-outline">Filtrar</button></noscript>
        @if($fichaFiltro)
            <a href="{{ route('importaciones.index') }}" class="btn btn-outline" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                <i class="fa-solid fa-rotate-left"></i> Ver todas
            </a>
        @endif
    </form>
</div>

<!-- Indicadores -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    @foreach([
        ['fa-file-import', '#39A900', 'rgba(57,169,0,0.1)', number_format($totalImportaciones), 'Reportes procesados'],
        ['fa-users', '#0ea5e9', 'rgba(14,165,233,0.1)', number_format($totalAprendicesProcesados), 'Aprendices procesados'],
        ['fa-shield-halved', '#a855f7', 'rgba(168,85,247,0.1)', $tasaExito === null ? '—' : $tasaExito . '%', 'Cargas sin error'],
        ['fa-calendar-check', '#f59e0b', 'rgba(245,158,11,0.1)', $ultimaImportacion ? $ultimaImportacion->created_at->locale('es')->diffForHumans() : 'N/A', 'Última importación'],
    ] as [$icono, $color, $fondo, $valor, $etiqueta])
        <div class="card stat-card" style="padding: 1.25rem 1.5rem;">
            <div class="icon-box" style="background: {{ $fondo }}; color: {{ $color }}; width: 45px; height: 45px; font-size: 1.2rem;">
                <i class="fa-solid {{ $icono }}"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size: {{ $loop->last ? '1.15rem' : '1.5rem' }};">{{ $valor }}</div>
                <div class="stat-label" style="font-size: 0.7rem;">{{ $etiqueta }}</div>
            </div>
        </div>
    @endforeach
</div>

<!-- Comparador de dos cargas de la misma ficha -->
@if($comparables->isNotEmpty())
    @php
        $porDefecto = $comparables->first();   // ficha con la carga más reciente
        $soloUna    = $comparables->count() === 1;
    @endphp
    <div class="card" style="margin-bottom: 2rem; padding: 1.5rem; border-color: rgba(57,169,0,0.3);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.1rem;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(57,169,0,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0;">
                <i class="fa-solid fa-code-compare"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.05rem;">Comparar dos cargas</h3>
                <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted);">Elige dos momentos de una misma ficha para ver cuánto avanzó entre ambos.</p>
            </div>
        </div>
        <form method="GET" action="{{ route('importaciones.comparar') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; align-items: end;">
            @if($fichaFiltro)<input type="hidden" name="ficha" value="{{ $fichaFiltro }}">@endif
            {{-- Por defecto: de la carga más antigua a la más reciente de la ficha. --}}
            @foreach([['id_inicial', 'Desde', 'last'], ['id_final', 'Hasta', 'first']] as [$campo, $titulo, $posicion])
                <div>
                    <label for="{{ $campo }}" style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase;">{{ $titulo }}</label>
                    <select name="{{ $campo }}" id="{{ $campo }}" class="form-control" style="padding: 0.6rem 0.85rem; font-size: 0.85rem;" required>
                        @foreach($comparables as $ficha => $cargas)
                            @unless($soloUna)<optgroup label="Ficha {{ $ficha }}">@endunless
                            @foreach($cargas as $c)
                                <option value="{{ $c->id }}" @selected($cargas === $porDefecto && $loop->{$posicion})>
                                    {{ $c->created_at->format('d/m/Y H:i') }} — {{ $c->nombre_archivo }}
                                </option>
                            @endforeach
                            @unless($soloUna)</optgroup>@endunless
                        @endforeach
                    </select>
                </div>
            @endforeach
            <button type="submit" class="btn btn-primary" style="white-space: nowrap; justify-content: center;">
                <i class="fa-solid fa-chart-line"></i> Comparar
            </button>
        </form>
    </div>
@endif

<!-- Línea de tiempo -->
<div class="card" style="padding: 1.75rem;">
    <h3 style="margin: 0 0 1.5rem; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-timeline" style="color: var(--primary);"></i> Línea de tiempo
        @if($fichaFiltro)<span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted);">(ficha {{ $fichaFiltro }})</span>@endif
    </h3>

    @if($importaciones->isEmpty())
        <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
            <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
            No hay importaciones registradas{{ $fichaFiltro ? ' para esta ficha' : ' todavía' }}.
            <div style="margin-top: 1rem;">
                <a href="{{ route('aprendices.upload') }}" class="btn btn-primary" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Subir un reporte
                </a>
            </div>
        </div>
    @else
        <div style="position: relative; padding-left: 30px;">
            <div style="position: absolute; left: 10px; top: 8px; bottom: 8px; width: 2px; background: linear-gradient(to bottom, var(--primary), rgba(57,169,0,0.05));"></div>

            @foreach($importaciones as $imp)
                <div style="position: relative; margin-bottom: 1.1rem; animation: fadeInLeft 0.4s ease {{ min($loop->index, 8) * 0.05 }}s both;">
                    <div style="position: absolute; left: -25px; top: 18px; width: 12px; height: 12px; border-radius: 50%;
                                background: {{ $imp->estado_visual['dot_bg'] }}; border: 2px solid {{ $imp->estado_visual['dot_border'] }};
                                box-shadow: 0 0 8px {{ $imp->estado_visual['dot_glow'] }};"></div>

                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.1rem 1.35rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 260px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 0.35rem;">
                                    <i class="fa-solid fa-file-excel" style="color: var(--primary);"></i>
                                    <span style="font-weight: 700; color: #f1f5f9; font-size: 0.95rem; word-break: break-word;">{{ $imp->nombre_archivo }}</span>
                                    @if($imp->id_ficha)
                                        <a href="{{ route('importaciones.index', ['ficha' => $imp->id_ficha]) }}" title="Ver solo esta ficha" style="text-decoration: none; background: rgba(57,169,0,0.12); color: var(--primary); border: 1px solid rgba(57,169,0,0.25); border-radius: 6px; padding: 0.1rem 0.5rem; font-size: 0.72rem; font-weight: 800;">
                                            Ficha {{ $imp->id_ficha }}
                                        </a>
                                    @endif
                                </div>
                                <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                                    @if($imp->ficha?->programa)<span>{{ $imp->ficha->programa->Nombre }}</span>@endif
                                    <span><i class="fa-solid fa-users"></i> {{ $imp->aprendices_procesados }} aprendices</span>
                                    <span><i class="fa-solid fa-stopwatch"></i> {{ $imp->duracion_segundos }}s</span>
                                    @if($imp->usuario)<span><i class="fa-solid fa-user"></i> {{ $imp->usuario->name }}</span>@endif
                                </div>
                                @include('importaciones._insignias', ['imp' => $imp])
                            </div>
                            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem;">
                                <span style="display: inline-block; padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700;
                                             background: {{ $imp->estado_visual['badge_bg'] }}; color: {{ $imp->estado_visual['badge_color'] }};
                                             border: 1px solid {{ $imp->estado_visual['badge_border'] }};">
                                    {{ $imp->estado_visual['label'] }}
                                </span>
                                <div style="font-size: 0.8rem; color: #f1f5f9;">{{ $imp->created_at->format('d/m/Y H:i') }}</div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $imp->created_at->locale('es')->diffForHumans() }}</div>
                                <div style="display: flex; gap: 0.4rem; margin-top: 0.2rem;">
                                    <button type="button" class="btn btn-outline btn-vista-rapida" data-url="{{ route('importaciones.json', $imp) }}" style="padding: 0.35rem 0.7rem; font-size: 0.75rem;" title="Vista rápida">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                    <a href="{{ route('importaciones.show', $imp) }}" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; white-space: nowrap;">Ver cambios</a>
                                    @if($imp->id_ficha && isset($fichasExistentes[$imp->id_ficha]))
                                        <a href="{{ route('fichas.historial', $imp->id_ficha) }}" class="btn btn-outline" style="padding: 0.35rem 0.7rem; font-size: 0.75rem;" title="Línea de tiempo de la ficha">
                                            <i class="fa-solid fa-chart-line"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
            {{ $importaciones->links() }}
        </div>
    @endif
</div>

<!-- Vista rápida (datos desde importaciones.json) -->
<div id="modal-vista-rapida" role="dialog" aria-modal="true" aria-labelledby="vr-titulo" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; padding: 1.75rem; background: rgba(15,23,42,0.97);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; gap: 1rem;">
            <div style="min-width: 0;">
                <h3 id="vr-titulo" style="margin: 0; font-size: 1.05rem; color: #fff; overflow-wrap: anywhere;">Importación</h3>
                <span id="vr-subtitulo" style="font-size: 0.75rem; color: var(--text-muted);"></span>
            </div>
            <button type="button" id="vr-cerrar" aria-label="Cerrar" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <dl id="vr-datos" style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; margin: 0; font-size: 0.85rem;"></dl>
        <div style="margin-top: 1rem;">
            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Detalle</div>
            <div id="vr-detalle" style="background: rgba(0,0,0,0.3); border-radius: 8px; padding: 0.75rem; font-size: 0.8rem; color: #cbd5e1; white-space: pre-line;"></div>
        </div>
        <div style="margin-top: 1.25rem; text-align: right;">
            <a id="vr-enlace" href="#" class="btn btn-primary" style="font-size: 0.85rem;">Ver cambios completos</a>
        </div>
    </div>
</div>

<style>
@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-15px); }
    to   { opacity: 1; transform: translateX(0); }
}
#vr-datos dt { color: var(--text-muted); }
#vr-datos dd { margin: 0; color: #f1f5f9; text-align: right; overflow-wrap: anywhere; }
</style>

<script>
(function () {
    const modal = document.getElementById('modal-vista-rapida');
    const datos = document.getElementById('vr-datos');
    const $ = id => document.getElementById(id);

    function fila(etiqueta, valor) {
        const dt = document.createElement('dt');
        const dd = document.createElement('dd');
        dt.textContent = etiqueta;
        dd.textContent = valor ?? '—';
        datos.append(dt, dd);
    }

    function cerrar() { modal.style.display = 'none'; }

    async function abrir(url) {
        modal.style.display = 'flex';
        $('vr-titulo').textContent = 'Cargando…';
        $('vr-subtitulo').textContent = '';
        $('vr-detalle').textContent = '';
        datos.replaceChildren();
        try {
            const r = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error(r.status);
            const d = await r.json();
            const res = d.resumen || {};
            $('vr-titulo').textContent = d.nombre_archivo;
            $('vr-subtitulo').textContent = `#${d.id} · ${d.fecha_formateada} (${d.hace})`;
            fila('Ficha', d.id_ficha);
            fila('Programa', d.programa);
            fila('Estado', d.estado_etiqueta);
            fila('Subido por', d.subido_por);
            fila('Aprendices procesados', d.aprendices_procesados);
            fila('Duración', `${d.duracion_segundos} s`);
            if (d.resumen) {
                fila('Aprobados (en formación)', res.aprobados_en_formacion);
                fila('Por evaluar (en formación)', res.pendientes_en_formacion);
            }
            $('vr-detalle').textContent = d.detalle || 'Sin detalles registrados.';
            $('vr-enlace').href = d.url;
        } catch (e) {
            $('vr-titulo').textContent = 'No se pudo cargar la importación.';
        }
    }

    document.querySelectorAll('.btn-vista-rapida').forEach(b => b.addEventListener('click', () => abrir(b.dataset.url)));
    $('vr-cerrar').addEventListener('click', cerrar);
    modal.addEventListener('click', e => { if (e.target === modal) cerrar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });
})();
</script>

@endsection
