@extends('layouts.app')

@section('title', 'Semáforo Predictivo de Deserción Académica')

@section('content')
<div style="max-width: 1300px; margin: 0 auto;">

    <!-- Encabezado -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 0.45rem 0.85rem; border-radius: 14px; font-size: 1.25rem;">
                    <i class="fa-solid fa-traffic-light"></i>
                </span>
                Diagnóstico Predictivo de Deserción (IA & Heurística)
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.4rem; max-width: 720px;">
                Algoritmo heurístico multifactorial que asigna un <strong>Score de Riesgo (0-100)</strong> basado en carga de pendientes y estado académico para detonar alertas tempranas.
            </p>
        </div>

        <!-- Barra de filtros -->
        <form method="GET" action="{{ route('acciones.diagnostico') }}" id="form-diagnostico-filtros" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; background: rgba(0,0,0,0.25); padding: 0.75rem 1.2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);">
            
            <!-- Campo de búsqueda por texto / tarjeta / nombre -->
            <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1 1 240px; min-width: 220px; position: relative;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"><i class="fa-solid fa-magnifying-glass"></i></label>
                <input type="text" name="search" id="input-busqueda-desercion" value="{{ $search ?? '' }}"
                       placeholder="Buscar por nombre, tarjeta / documento..." 
                       class="form-control" 
                       style="padding: 0.45rem 0.85rem; font-size: 0.85rem; border-radius: 10px; width: 100%;"
                       autocomplete="off">
            </div>

            <!-- Selector de Ficha -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ficha:</label>
                <select name="ficha_id" onchange="this.form.submit()" class="form-control" style="padding: 0.45rem 2.2rem 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; width: auto;">
                    <option value="">— Todas las Fichas —</option>
                    @foreach($fichas as $f)
                        <option value="{{ $f->Id_Ficha }}" {{ $fichaId == $f->Id_Ficha ? 'selected' : '' }}>{{ $f->Id_Ficha }} — {{ $f->programa->Nombre ?? 'SENA' }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Selector de Semáforo -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Semáforo:</label>
                <select name="semaforo" onchange="this.form.submit()" class="form-control" style="padding: 0.45rem 2.2rem 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; width: auto;">
                    <option value="">— Todos los Niveles —</option>
                    <option value="critico" {{ $semaforoFiltro == 'critico' ? 'selected' : '' }}>🔴 Crítico (Score ≥ 75)</option>
                    <option value="moderado" {{ $semaforoFiltro == 'moderado' ? 'selected' : '' }}>🟡 Moderado (Score 40-74)</option>
                    <option value="estable" {{ $semaforoFiltro == 'estable' ? 'selected' : '' }}>🟢 Estable (Score < 40)</option>
                </select>
            </div>

            <!-- Selector de Estado Académico -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Estado:</label>
                <select name="estado" onchange="this.form.submit()" class="form-control" style="padding: 0.45rem 2.2rem 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; width: auto;">
                    <option value="">— Todos —</option>
                    <option value="EN FORMACION" {{ ($estadoFiltro ?? '') == 'EN FORMACION' ? 'selected' : '' }}>En Formación</option>
                    <option value="RETIRO VOLUNTARIO" {{ ($estadoFiltro ?? '') == 'RETIRO VOLUNTARIO' ? 'selected' : '' }}>Retiro Voluntario</option>
                    <option value="CANCELADO" {{ ($estadoFiltro ?? '') == 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                    <option value="TRASLADADO" {{ ($estadoFiltro ?? '') == 'TRASLADADO' ? 'selected' : '' }}>Trasladado</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button type="submit" class="btn" style="background: var(--primary); color: #000; font-weight: 800; padding: 0.45rem 0.9rem; border-radius: 10px; font-size: 0.8rem;" title="Filtrar">
                    <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                </button>

                @if($fichaId || $semaforoFiltro || !empty($search) || !empty($estadoFiltro))
                    <a href="{{ route('acciones.diagnostico') }}" class="btn" style="background: rgba(255,255,255,0.08); color: #fff; padding: 0.45rem 0.9rem; border-radius: 10px; font-size: 0.8rem;" title="Limpiar todos los filtros">
                        <i class="fa-solid fa-rotate-left"></i> Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tarjetas de Semáforo -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Tarjeta Crítico -->
        <a href="{{ route('acciones.diagnostico', array_filter(['ficha_id' => $fichaId, 'semaforo' => 'critico', 'search' => $search, 'estado' => $estadoFiltro])) }}" style="text-decoration: none;">
            <div class="card" style="padding: 1.5rem; border-color: {{ $semaforoFiltro == 'critico' ? '#ef4444' : 'rgba(239,68,68,0.2)' }}; background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(15,23,42,0.6)); position: relative; overflow: hidden; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-weight: 800; font-size: 0.85rem; color: #ef4444; text-transform: uppercase;">🔴 Riesgo Crítico</span>
                    <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444; font-size: 1.25rem;"></i>
                </div>
                <div style="font-size: 2.25rem; font-weight: 900; color: #fff;">{{ $conteoCritico }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Score ≥ 75 pts • Intervención urgente</div>
            </div>
        </a>

        <!-- Tarjeta Moderado -->
        <a href="{{ route('acciones.diagnostico', array_filter(['ficha_id' => $fichaId, 'semaforo' => 'moderado', 'search' => $search, 'estado' => $estadoFiltro])) }}" style="text-decoration: none;">
            <div class="card" style="padding: 1.5rem; border-color: {{ $semaforoFiltro == 'moderado' ? '#f59e0b' : 'rgba(245,158,11,0.2)' }}; background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(15,23,42,0.6)); position: relative; overflow: hidden; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-weight: 800; font-size: 0.85rem; color: #f59e0b; text-transform: uppercase;">🟡 Riesgo Moderado</span>
                    <i class="fa-solid fa-bell" style="color: #f59e0b; font-size: 1.25rem;"></i>
                </div>
                <div style="font-size: 2.25rem; font-weight: 900; color: #fff;">{{ $conteoModerado }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Score 40-74 pts • Plan de seguimiento</div>
            </div>
        </a>

        <!-- Tarjeta Estable -->
        <a href="{{ route('acciones.diagnostico', array_filter(['ficha_id' => $fichaId, 'semaforo' => 'estable', 'search' => $search, 'estado' => $estadoFiltro])) }}" style="text-decoration: none;">
            <div class="card" style="padding: 1.5rem; border-color: {{ $semaforoFiltro == 'estable' ? '#10b981' : 'rgba(16,185,129,0.2)' }}; background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(15,23,42,0.6)); position: relative; overflow: hidden; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-weight: 800; font-size: 0.85rem; color: #10b981; text-transform: uppercase;">🟢 Situación Estable</span>
                    <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 1.25rem;"></i>
                </div>
                <div style="font-size: 2.25rem; font-weight: 900; color: #fff;">{{ $conteoEstable }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Score < 40 pts • Al día con su proceso</div>
            </div>
        </a>

    </div>

    <!-- Formulario para Alerta Masiva -->
    <form method="POST" action="{{ route('acciones.alerta-masiva') }}" id="form-alerta-masiva">
        @csrf

        <div class="card" style="padding: 1.5rem;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
                <h2 style="font-size: 1.2rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid fa-users" style="color: var(--primary);"></i>
                    Listado de Aprendices Evaluados (<span id="total-aprendices-conteo">{{ $aprendices->count() }}</span>)
                </h2>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <span id="contador-seleccionados" style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted);">0 seleccionados</span>
                    <button type="submit" id="btn-submit-masivo" disabled
                            class="btn" style="background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 800; padding: 0.6rem 1.25rem; border-radius: 12px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.6rem;">
                        <i class="fa-solid fa-bullhorn"></i> Emitir Alerta Oficial a Bienestar / Coordinación
                    </button>
                </div>
            </div>

            <!-- Tabla de Diagnóstico -->
            <div style="background: rgba(0,0,0,0.25); border-radius: 14px; border: 1px solid rgba(255,255,255,0.06); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;" id="tabla-diagnostico">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.08);">
                            <th style="padding: 1rem; width: 40px; text-align: center;">
                                <input type="checkbox" id="check-all" onclick="toggleSelectAll(this)" style="width: 16px; height: 16px; cursor: pointer;">
                            </th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aprendiz / Documento</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ficha</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Estado Sistema</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pendientes</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 180px;">Score de Riesgo IA</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Semáforo</th>
                            <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Acciones Directas</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-diagnostico">
                        @forelse($aprendices as $ap)
                        @php
                            $searchKeywords = strtolower("{$ap->Nombre} {$ap->Apellido} {$ap->Documento} {$ap->Tipo_Documento} {$ap->Id_Ficha} {$ap->Estado}");
                        @endphp
                        <tr class="fila-aprendiz-desercion" data-search="{{ $searchKeywords }}" style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem; text-align: center;">
                                <input type="checkbox" name="aprendices_ids[]" value="{{ $ap->Id_Aprendiz }}" class="check-item" onclick="updateCounter()" style="width: 16px; height: 16px; cursor: pointer;">
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-weight: 800; color: #fff; font-size: 0.9rem;" class="nombre-aprendiz">{{ $ap->Nombre }} {{ $ap->Apellido }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <span>Doc: <strong style="color: #cbd5e1;">{{ $ap->Documento }}</strong></span>
                                    @if($ap->Tipo_Documento)
                                        <span style="margin-left: 0.4rem; opacity: 0.7;">({{ $ap->Tipo_Documento }})</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 1rem; font-weight: 700; color: var(--primary);">
                                {{ $ap->Id_Ficha }}
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge {{ $ap->Estado == 'EN FORMACION' ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.7rem;">
                                    {{ $ap->Estado }}
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-weight: 800; color: #f1f5f9; font-size: 0.9rem;">{{ $ap->pendientes_count }} / {{ $ap->total_juicios }}</div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);">pendientes</div>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="flex: 1; background: rgba(255,255,255,0.08); height: 8px; border-radius: 999px; overflow: hidden;">
                                        <div style="width: {{ $ap->score_riesgo }}%; background: {{ $ap->semaforo_color }}; height: 100%; border-radius: 999px;"></div>
                                    </div>
                                    <span style="font-weight: 900; font-size: 0.95rem; color: {{ $ap->semaforo_color }}; width: 45px; text-align: right;">
                                        {{ $ap->score_riesgo }} pt
                                    </span>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="font-weight: 800; color: {{ $ap->semaforo_color }}; font-size: 0.85rem;">
                                    {{ $ap->semaforo_label }}
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                    <!-- Botón Simulador de Salvación -->
                                    <a href="{{ route('acciones.simulador', $ap->Id_Aprendiz) }}" 
                                       title="Simulador de Salvación Académica"
                                       style="width: 32px; height: 32px; background: rgba(57,169,0,0.15); color: var(--primary); border: 1px solid rgba(57,169,0,0.3); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; text-decoration: none;">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </a>

                                    <!-- Botón Expediente -->
                                    <a href="{{ route('aprendices.show', $ap->Id_Aprendiz) }}" 
                                       title="Ver Expediente Completo"
                                       style="width: 32px; height: 32px; background: rgba(255,255,255,0.06); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; text-decoration: none;">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="tr-no-data">
                            <td colspan="8" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                                No se encontraron aprendices que coincidan con los criterios de filtro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </form>

</div>

<script>
    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.fila-aprendiz-desercion:not([style*="display: none"]) .check-item');
        checkboxes.forEach(cb => cb.checked = source.checked);
        updateCounter();
    }

    function updateCounter() {
        const checkedCount = document.querySelectorAll('.check-item:checked').length;
        const counterSpan = document.getElementById('contador-seleccionados');
        const submitBtn = document.getElementById('btn-submit-masivo');

        counterSpan.textContent = `${checkedCount} seleccionados`;

        if (checkedCount > 0) {
            submitBtn.disabled = false;
            submitBtn.style.background = '#ef4444';
            submitBtn.style.color = '#fff';
            submitBtn.style.boxShadow = '0 6px 16px -4px rgba(239,68,68,0.5)';
        } else {
            submitBtn.disabled = true;
            submitBtn.style.background = 'rgba(239,68,68,0.2)';
            submitBtn.style.color = '#ef4444';
            submitBtn.style.boxShadow = 'none';
        }
    }

    // Filtrado reactivo en tiempo real al escribir en el buscador
    const inputSearch = document.getElementById('input-busqueda-desercion');
    if (inputSearch) {
        inputSearch.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            const filas = document.querySelectorAll('.fila-aprendiz-desercion');
            let visibles = 0;

            filas.forEach(fila => {
                const searchData = fila.getAttribute('data-search') || '';
                if (query === '' || searchData.includes(query)) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                    // Desmarcar si queda oculto para evitar remisiones accidentales
                    const cb = fila.querySelector('.check-item');
                    if (cb && cb.checked) {
                        cb.checked = false;
                    }
                }
            });

            updateCounter();
            const totalConteoSpan = document.getElementById('total-aprendices-conteo');
            if (totalConteoSpan) {
                totalConteoSpan.textContent = visibles;
            }
        });
    }
</script>
@endsection
