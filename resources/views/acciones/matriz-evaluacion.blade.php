@extends('layouts.app')

@section('title', 'Matriz Interactiva de Evaluación Rápida')

@section('content')
<div style="max-width: 1450px; margin: 0 auto;">

    <!-- Encabezado superior y selectores de Ficha y Competencia -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                <span style="background: rgba(57, 169, 0, 0.15); color: var(--primary); padding: 0.45rem 0.85rem; border-radius: 14px; font-size: 1.25rem;">
                    <i class="fa-solid fa-table-cells"></i>
                </span>
                Matriz Interactiva de Evaluación Rápida en Pantalla
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.4rem; max-width: 780px;">
                Califica directamente en vivo a toda la ficha cambiando interruptores (<strong>Toggle: Pendiente ↔ Aprobado</strong>) con autoguardado instantáneo y actualización de caché en tiempo real.
            </p>
        </div>

        <!-- Filtros Principales (Ficha, Competencia, Búsqueda y Estado) -->
        <form method="GET" action="{{ route('acciones.matriz') }}" id="form-matriz-filtros" style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center; background: rgba(0,0,0,0.3); padding: 0.75rem 1.2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);">
            
            <!-- Campo de búsqueda por texto / tarjeta / nombre -->
            <div style="display: flex; align-items: center; gap: 0.4rem; flex: 1 1 200px; min-width: 180px;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"><i class="fa-solid fa-magnifying-glass"></i></label>
                <input type="text" name="search" id="input-busqueda-matriz-server" value="{{ $search ?? '' }}"
                       placeholder="Buscar por nombre, tarjeta..." 
                       class="form-control" 
                       style="padding: 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; width: 100%;"
                       autocomplete="off">
            </div>

            <!-- Selector de Ficha -->
            <div style="display: flex; align-items: center; gap: 0.4rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ficha:</label>
                <select name="ficha_id" onchange="document.getElementById('form-matriz-filtros').submit()" class="form-control" style="padding: 0.45rem 2.2rem 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; width: auto; font-weight: 700; color: var(--primary);">
                    @foreach($fichas as $f)
                        <option value="{{ $f->Id_Ficha }}" {{ $fichaId == $f->Id_Ficha ? 'selected' : '' }}>
                            {{ $f->Id_Ficha }} — {{ $f->programa->Nombre ?? 'SENA' }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($competencias->isNotEmpty())
            <!-- Selector de Competencia -->
            <div style="display: flex; align-items: center; gap: 0.4rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Competencia:</label>
                <select name="competencia_id" onchange="document.getElementById('form-matriz-filtros').submit()" class="form-control" style="padding: 0.45rem 2.2rem 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; max-width: 320px;">
                    @foreach($competencias as $c)
                        <option value="{{ $c->Id_Competencia }}" {{ $competenciaId == $c->Id_Competencia ? 'selected' : '' }}>
                            [{{ $c->Codigo }}] {{ Str::limit($c->Nombre, 50) }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Selector de Estado -->
            <div style="display: flex; align-items: center; gap: 0.4rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Estado:</label>
                <select name="estado" onchange="document.getElementById('form-matriz-filtros').submit()" class="form-control" style="padding: 0.45rem 2.2rem 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; width: auto;">
                    <option value="">— Todos —</option>
                    <option value="EN FORMACION" {{ ($estadoFiltro ?? '') == 'EN FORMACION' ? 'selected' : '' }}>En Formación</option>
                    <option value="RETIRO VOLUNTARIO" {{ ($estadoFiltro ?? '') == 'RETIRO VOLUNTARIO' ? 'selected' : '' }}>Retiro Voluntario</option>
                    <option value="CANCELADO" {{ ($estadoFiltro ?? '') == 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                    <option value="TRASLADADO" {{ ($estadoFiltro ?? '') == 'TRASLADADO' ? 'selected' : '' }}>Trasladado</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.4rem; align-items: center;">
                <button type="submit" class="btn" style="background: var(--primary); color: #000; font-weight: 800; padding: 0.45rem 0.8rem; border-radius: 10px; font-size: 0.8rem;" title="Filtrar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                @if(!empty($search) || !empty($estadoFiltro))
                    <a href="{{ route('acciones.matriz', ['ficha_id' => $fichaId, 'competencia_id' => $competenciaId]) }}" class="btn" style="background: rgba(255,255,255,0.08); color: #fff; padding: 0.45rem 0.8rem; border-radius: 10px; font-size: 0.8rem;" title="Limpiar búsqueda">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Indicador flotante de Estado AJAX -->
    <div id="toast-autoguardado" style="position: fixed; bottom: 30px; right: 30px; background: #10b981; color: #fff; padding: 0.8rem 1.4rem; border-radius: 14px; font-weight: 800; font-size: 0.9rem; box-shadow: 0 10px 25px -5px rgba(16,185,129,0.5); display: flex; align-items: center; gap: 0.6rem; z-index: 9999; transform: translateY(100px); opacity: 0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 1.1rem;"></i>
        <span id="toast-mensaje">Autoguardado en servidor...</span>
    </div>

    <!-- Modal de Confirmación Personalizado -->
    <div id="modal-confirmar-overlay" style="
        position: fixed; inset: 0; z-index: 99999;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; pointer-events: none;
        transition: opacity 0.25s ease;
    ">
        <div id="modal-confirmar-box" style="
            background: linear-gradient(135deg, rgba(15,23,42,0.98) 0%, rgba(22,33,55,0.98) 100%);
            border: 1px solid rgba(57,169,0,0.3);
            border-radius: 20px;
            padding: 2rem 2.25rem;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 30px 80px -10px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.05);
            transform: scale(0.88) translateY(16px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
            opacity: 0;
        ">
            <!-- Icono -->
            <div style="text-align: center; margin-bottom: 1.25rem;">
                <div style="
                    display: inline-flex; align-items: center; justify-content: center;
                    width: 60px; height: 60px; border-radius: 50%;
                    background: rgba(16,185,129,0.15);
                    border: 2px solid rgba(16,185,129,0.35);
                    box-shadow: 0 0 24px rgba(16,185,129,0.2);
                    margin-bottom: 0.25rem;
                ">
                    <i class="fa-solid fa-check-double" style="font-size: 1.5rem; color: #10b981;"></i>
                </div>
            </div>

            <!-- Título -->
            <h3 style="
                text-align: center; font-size: 1.05rem; font-weight: 800;
                color: #f1f5f9; margin: 0 0 0.65rem;
                line-height: 1.4;
            ">Aprobar Todo en Pantalla</h3>

            <!-- Mensaje -->
            <p style="
                text-align: center; font-size: 0.9rem; color: #94a3b8;
                margin: 0 0 1.75rem; line-height: 1.6;
            ">
                ¿Deseas cambiar a
                <strong style="color: #10b981; font-weight: 800;">APROBADO</strong>
                todas las celdas visibles en pantalla para esta competencia?
            </p>

            <!-- Botones -->
            <div style="display: flex; gap: 0.75rem;">
                <button id="modal-btn-cancelar" style="
                    flex: 1; padding: 0.7rem 1rem;
                    border-radius: 12px; border: 1px solid rgba(255,255,255,0.12);
                    background: rgba(255,255,255,0.06); color: #94a3b8;
                    font-size: 0.9rem; font-weight: 700; cursor: pointer;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='rgba(255,255,255,0.11)';this.style.color='#f1f5f9';"
                   onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='#94a3b8';">
                    <i class="fa-solid fa-xmark" style="margin-right: 0.35rem;"></i>Cancelar
                </button>
                <button id="modal-btn-aceptar" style="
                    flex: 1; padding: 0.7rem 1rem;
                    border-radius: 12px; border: 1px solid rgba(16,185,129,0.4);
                    background: linear-gradient(135deg, rgba(16,185,129,0.25), rgba(5,150,105,0.35));
                    color: #10b981;
                    font-size: 0.9rem; font-weight: 800; cursor: pointer;
                    box-shadow: 0 4px 15px rgba(16,185,129,0.2);
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='linear-gradient(135deg, rgba(16,185,129,0.4), rgba(5,150,105,0.5))';this.style.boxShadow='0 6px 20px rgba(16,185,129,0.35)';this.style.color='#fff';"
                   onmouseout="this.style.background='linear-gradient(135deg, rgba(16,185,129,0.25), rgba(5,150,105,0.35))';this.style.boxShadow='0 4px 15px rgba(16,185,129,0.2)';this.style.color='#10b981';">
                    <i class="fa-solid fa-check-double" style="margin-right: 0.35rem;"></i>Aceptar
                </button>
            </div>
        </div>
    </div>

    @if($aprendices->isEmpty() || $resultados->isEmpty())
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <i class="fa-solid fa-folder-open" style="font-size: 3.5rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: #fff; font-weight: 800;">No hay datos para evaluar</h3>
            <p style="color: var(--text-muted); max-width: 450px; margin: 0.5rem auto 0;">Verifica que la ficha seleccionada tenga aprendices cargados y competencias asignadas con los filtros actuales.</p>
            @if(!empty($search) || !empty($estadoFiltro))
                <div style="margin-top: 1.5rem;">
                    <a href="{{ route('acciones.matriz', ['ficha_id' => $fichaId, 'competencia_id' => $competenciaId]) }}" class="btn btn-primary" style="padding: 0.6rem 1.2rem;">
                        <i class="fa-solid fa-rotate-left"></i> Restablecer Filtros
                    </a>
                </div>
            @endif
        </div>
    @else
        <!-- Barra de herramientas: Búsqueda rápida en vivo y Filtros rápidos de Progreso -->
        <div class="card" style="padding: 0.9rem 1.4rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: rgba(0,0,0,0.25);">
            
            <!-- Buscador reactivo instantáneo en cliente -->
            <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1 1 280px; min-width: 240px; position: relative;">
                <i class="fa-solid fa-bolt" style="color: var(--primary); font-size: 1rem;" title="Filtrado rápido en vivo"></i>
                <input type="text" id="input-matriz-live-search" placeholder="Filtrar en pantalla por nombre o tarjeta / documento..." 
                       class="form-control" style="padding: 0.45rem 0.85rem; font-size: 0.85rem; border-radius: 10px; width: 100%;" autocomplete="off">
            </div>

            <!-- Filtros rápidos por estado de avance -->
            <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                <button type="button" onclick="filtrarProgreso('todos')" id="btn-filtro-todos" class="btn active-filter-pill" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); background: rgba(57,169,0,0.2); color: #fff;">
                    Todos (<span id="pill-count-todos">{{ $aprendices->count() }}</span>)
                </button>
                <button type="button" onclick="filtrarProgreso('pendientes')" id="btn-filtro-pendientes" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 8px; border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.1); color: #ef4444;">
                    ⚠️ Con Pendientes (<span id="pill-count-pendientes">0</span>)
                </button>
                <button type="button" onclick="filtrarProgreso('aprobados')" id="btn-filtro-aprobados" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 8px; border: 1px solid rgba(16,185,129,0.25); background: rgba(16,185,129,0.1); color: #10b981;">
                    ✅ 100% Aprobados (<span id="pill-count-aprobados">0</span>)
                </button>
            </div>

            <!-- Acciones y contador de aprendices visibles -->
            <div style="display: flex; gap: 0.85rem; align-items: center;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">
                    Mostrando <strong id="contador-mostrando-matriz" style="color: #fff;">{{ $aprendices->count() }}</strong> de {{ $aprendices->count() }}
                </span>
                <button type="button" onclick="aprobarTodosLote()" class="btn" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 800; font-size: 0.8rem; padding: 0.45rem 0.9rem; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-check-double"></i> Aprobar Visibles
                </button>
            </div>
        </div>

        <!-- Matriz Hoja de Cálculo Web -->
        <div class="card" style="padding: 0; overflow-x: auto; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.85);">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;" id="tabla-matriz">
                <thead>
                    <tr style="background: rgba(0,0,0,0.6); border-bottom: 2px solid rgba(255,255,255,0.1);">
                        <!-- Columna fija Nombres -->
                        <th style="padding: 1.1rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; position: sticky; left: 0; background: #0f172a; z-index: 10; border-right: 1px solid rgba(255,255,255,0.08); width: 280px;">
                            Aprendiz / Documento
                        </th>

                        <!-- Columnas de Resultados de Aprendizaje -->
                        @foreach($resultados as $r)
                        <th style="padding: 1rem 0.85rem; text-align: center; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); border-right: 1px solid rgba(255,255,255,0.05); min-width: 160px; max-width: 220px;" title="{{ $r->Nombre }}">
                            <div style="color: var(--primary); font-weight: 800; font-size: 0.85rem; margin-bottom: 0.3rem;">
                                {{ $r->Codigo }}
                            </div>
                            <div style="font-size: 0.72rem; line-height: 1.3; font-weight: 600; color: #cbd5e1;">
                                {{ Str::limit($r->Nombre, 45) }}
                            </div>
                        </th>
                        @endforeach

                        <!-- Columna resumen fila -->
                        <th style="padding: 1rem; text-align: center; font-size: 0.75rem; font-weight: 800; color: #fbbf24; text-transform: uppercase; width: 120px; background: rgba(0,0,0,0.4);">
                            Avance Fila
                        </th>
                    </tr>
                </thead>
                <tbody id="tbody-matriz">
                    @foreach($aprendices as $ap)
                    @php
                        // Calcular aprobados en esta vista para la fila
                        $aprobadosFila = 0;
                        foreach ($resultados as $r) {
                            $key = "{$ap->Id_Aprendiz}-{$r->Id_Resultado}";
                            if (isset($juiciosMap[$key]) && $juiciosMap[$key] == 1) {
                                $aprobadosFila++;
                            }
                        }
                        $porcRow = $resultados->count() > 0 ? round(($aprobadosFila / $resultados->count()) * 100) : 0;
                        $searchKeywords = strtolower("{$ap->Nombre} {$ap->Apellido} {$ap->Documento} {$ap->Tipo_Documento} {$ap->Estado}");
                    @endphp
                    <tr class="fila-matriz-aprendiz" 
                        data-search="{{ $searchKeywords }}"
                        data-porc="{{ $porcRow }}"
                        data-aprobados="{{ $aprobadosFila }}"
                        data-total="{{ $resultados->count() }}"
                        data-estado="{{ $ap->Estado }}"
                        data-aprendiz-id="{{ $ap->Id_Aprendiz }}"
                        style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <!-- Columna fija: Aprendiz -->
                        <td style="padding: 0.85rem 1.25rem; position: sticky; left: 0; background: #0f172a; z-index: 5; border-right: 1px solid rgba(255,255,255,0.08);">
                            <div style="font-weight: 800; color: #fff; font-size: 0.9rem;">
                                <a href="{{ route('aprendices.show', $ap->Id_Aprendiz) }}" style="color: #fff; text-decoration: none;" target="_blank">
                                    {{ $ap->Nombre }} {{ $ap->Apellido }}
                                </a>
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); display: flex; justify-content: space-between; margin-top: 0.15rem;">
                                <span>Doc: <strong style="color: #cbd5e1;">{{ $ap->Documento }}</strong> @if($ap->Tipo_Documento)<span style="opacity: 0.7;">({{ $ap->Tipo_Documento }})</span>@endif</span>
                                <span class="badge {{ $ap->Estado == 'EN FORMACION' ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.62rem; padding: 0.1rem 0.4rem;">{{ $ap->Estado }}</span>
                            </div>
                        </td>

                        <!-- Celdas Interactivas de Juicios -->
                        @foreach($resultados as $r)
                        @php
                            $keyMap = "{$ap->Id_Aprendiz}-{$r->Id_Resultado}";
                            $estadoActual = $juiciosMap[$keyMap] ?? 0; // 0 por defecto
                        @endphp
                        <td style="padding: 0.85rem; text-align: center; border-right: 1px solid rgba(255,255,255,0.04); vertical-align: middle;">
                            
                            <!-- Toggle Button / Pill -->
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <button type="button" 
                                        id="btn-toggle-{{ $ap->Id_Aprendiz }}-{{ $r->Id_Resultado }}"
                                        data-aprendiz="{{ $ap->Id_Aprendiz }}"
                                        data-resultado="{{ $r->Id_Resultado }}"
                                        data-estado="{{ $estadoActual }}"
                                        onclick="toggleJuicio(this)"
                                        style="width: 100px; padding: 0.45rem 0; border-radius: 10px; font-weight: 800; font-size: 0.78rem; cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); border: 1px solid transparent; 
                                        {{ $estadoActual == 1 ? 'background: rgba(16,185,129,0.2); color: #10b981; border-color: rgba(16,185,129,0.4); box-shadow: 0 4px 12px -2px rgba(16,185,129,0.3);' : 'background: rgba(239,68,68,0.18); color: #ef4444; border-color: rgba(239,68,68,0.3);' }}">
                                    @if($estadoActual == 1)
                                        <i class="fa-solid fa-check"></i> Aprobado
                                    @else
                                        <i class="fa-solid fa-xmark"></i> Pendiente
                                    @endif
                                </button>
                            </div>

                        </td>
                        @endforeach

                        <!-- Columna resumen de fila -->
                        <td style="padding: 0.85rem; text-align: center; background: rgba(0,0,0,0.2); font-weight: 800;" id="row-progress-{{ $ap->Id_Aprendiz }}" data-aprobados="{{ $aprobadosFila }}" data-total="{{ $resultados->count() }}">
                            <div style="font-size: 0.95rem; color: {{ $porcRow >= 70 ? '#10b981' : '#ef4444' }};" class="porc-text">
                                {{ $porcRow }}%
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);" class="count-text">
                                {{ $aprobadosFila }}/{{ $resultados->count() }}
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    <tr id="tr-matriz-no-match" style="display: none;">
                        <td colspan="{{ $resultados->count() + 2 }}" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                            <i class="fa-solid fa-user-slash" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.5;"></i>
                            No se encontraron aprendices que coincidan con la búsqueda en esta pantalla.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

</div>

<!-- Script AJAX de Autoguardado Reactivo y Filtros en Vivo -->
<script>
    const csrfToken = "{{ csrf_token() }}";
    const updateUrl = "{{ route('acciones.matriz.actualizar') }}";
    let filtroProgresoActivo = 'todos';

    function toggleJuicio(btn) {
        const aprendizId = btn.getAttribute('data-aprendiz');
        const resultadoId = btn.getAttribute('data-resultado');
        const estadoActual = parseInt(btn.getAttribute('data-estado'));
        const nuevoEstado = estadoActual === 1 ? 0 : 1;

        // Feedback visual inmediato (Optimistic UI)
        actualizarEstiloBoton(btn, nuevoEstado);
        actualizarProgresoFila(aprendizId, nuevoEstado - estadoActual);

        // Envío AJAX al servidor
        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                id_aprendiz: aprendizId,
                id_resultado: resultadoId,
                estado: nuevoEstado
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast('✅ Guardado en servidor');
            } else {
                mostrarToast('❌ Error al guardar', true);
                // Revertir en caso de error
                actualizarEstiloBoton(btn, estadoActual);
                actualizarProgresoFila(aprendizId, -(nuevoEstado - estadoActual));
            }
        })
        .catch(error => {
            console.error('Error AJAX:', error);
            mostrarToast('❌ Error de red', true);
            actualizarEstiloBoton(btn, estadoActual);
            actualizarProgresoFila(aprendizId, -(nuevoEstado - estadoActual));
        });
    }

    function actualizarEstiloBoton(btn, estado) {
        btn.setAttribute('data-estado', estado);
        if (estado === 1) {
            btn.style.background = 'rgba(16,185,129,0.2)';
            btn.style.color = '#10b981';
            btn.style.borderColor = 'rgba(16,185,129,0.4)';
            btn.style.boxShadow = '0 4px 12px -2px rgba(16,185,129,0.3)';
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Aprobado';
        } else {
            btn.style.background = 'rgba(239,68,68,0.18)';
            btn.style.color = '#ef4444';
            btn.style.borderColor = 'rgba(239,68,68,0.3)';
            btn.style.boxShadow = 'none';
            btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Pendiente';
        }
    }

    function actualizarProgresoFila(aprendizId, delta) {
        const td = document.getElementById(`row-progress-${aprendizId}`);
        const tr = document.querySelector(`tr.fila-matriz-aprendiz[data-aprendiz-id="${aprendizId}"]`);
        if (!td) return;

        let aprobados = parseInt(td.getAttribute('data-aprobados')) + delta;
        let total = parseInt(td.getAttribute('data-total'));
        aprobados = Math.max(0, Math.min(total, aprobados));

        td.setAttribute('data-aprobados', aprobados);

        const porc = total > 0 ? Math.round((aprobados / total) * 100) : 0;
        const porcEl = td.querySelector('.porc-text');
        const countEl = td.querySelector('.count-text');

        if (porcEl) {
            porcEl.textContent = `${porc}%`;
            porcEl.style.color = porc >= 70 ? '#10b981' : '#ef4444';
        }
        if (countEl) {
            countEl.textContent = `${aprobados}/${total}`;
        }

        if (tr) {
            tr.setAttribute('data-porc', porc);
            tr.setAttribute('data-aprobados', aprobados);
        }

        actualizarContadoresPills();
    }

    let toastTimeout = null;
    function mostrarToast(mensaje, esError = false) {
        const toast = document.getElementById('toast-autoguardado');
        const span = document.getElementById('toast-mensaje');
        span.textContent = mensaje;

        if (esError) {
            toast.style.background = '#ef4444';
        } else {
            toast.style.background = '#10b981';
        }

        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.style.transform = 'translateY(100px)';
            toast.style.opacity = '0';
        }, 2500);
    }

    /* ── Modal de confirmación personalizado ── */
    const _modalOverlay = document.getElementById('modal-confirmar-overlay');
    const _modalBox     = document.getElementById('modal-confirmar-box');

    function abrirModalConfirmar(onAceptar) {
        if (!_modalOverlay || !_modalBox) return;
        _modalOverlay.style.opacity        = '1';
        _modalOverlay.style.pointerEvents  = 'all';
        _modalBox.style.opacity            = '1';
        _modalBox.style.transform          = 'scale(1) translateY(0)';

        const cerrar = () => {
            _modalOverlay.style.opacity       = '0';
            _modalOverlay.style.pointerEvents = 'none';
            _modalBox.style.opacity           = '0';
            _modalBox.style.transform         = 'scale(0.88) translateY(16px)';
        };

        document.getElementById('modal-btn-cancelar').onclick = cerrar;
        _modalOverlay.onclick = (e) => { if (e.target === _modalOverlay) cerrar(); };
        document.getElementById('modal-btn-aceptar').onclick = () => { cerrar(); onAceptar(); };
    }

    function aprobarTodosLote() {
        abrirModalConfirmar(() => { _ejecutarAprobacionLote(); });
    }

    function _ejecutarAprobacionLote() {
        // Solo seleccionar botones de filas actualmente VISIBLES
        const botones = document.querySelectorAll('tr.fila-matriz-aprendiz:not([style*="display: none"]) button[id^="btn-toggle-"]');
        let cambios = [];

        botones.forEach(btn => {
            if (parseInt(btn.getAttribute('data-estado')) === 0) {
                const id_aprendiz = btn.getAttribute('data-aprendiz');
                const id_resultado = btn.getAttribute('data-resultado');
                actualizarEstiloBoton(btn, 1);
                actualizarProgresoFila(id_aprendiz, 1);
                cambios.push({ id_aprendiz, id_resultado, estado: 1 });
            }
        });

        if (cambios.length === 0) {
            mostrarToast('✅ Todos los visibles ya están aprobados');
            return;
        }

        fetch("{{ route('acciones.matriz.lote') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ cambios })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(`✅ ${data.message}`);
            } else {
                mostrarToast('❌ Error al guardar en lote', true);
            }
        })
        .catch(err => {
            console.error('Error lote:', err);
            mostrarToast('❌ Error de red al guardar en lote', true);
        });
    }

    /* ── Filtrado Reactivo en Tiempo Real y Pastillas de Progreso ── */
    function filtrarProgreso(tipo) {
        filtroProgresoActivo = tipo;

        const btnTodos = document.getElementById('btn-filtro-todos');
        const btnPendientes = document.getElementById('btn-filtro-pendientes');
        const btnAprobados = document.getElementById('btn-filtro-aprobados');

        // Reset estilos
        if (btnTodos) {
            btnTodos.style.background = 'rgba(255,255,255,0.05)';
            btnTodos.style.color = 'var(--text-muted)';
        }
        if (btnPendientes) {
            btnPendientes.style.background = 'rgba(239,68,68,0.1)';
            btnPendientes.style.color = '#ef4444';
        }
        if (btnAprobados) {
            btnAprobados.style.background = 'rgba(16,185,129,0.1)';
            btnAprobados.style.color = '#10b981';
        }

        // Activar estilo seleccionado
        if (tipo === 'todos' && btnTodos) {
            btnTodos.style.background = 'rgba(57,169,0,0.25)';
            btnTodos.style.color = '#fff';
        } else if (tipo === 'pendientes' && btnPendientes) {
            btnPendientes.style.background = '#ef4444';
            btnPendientes.style.color = '#fff';
        } else if (tipo === 'aprobados' && btnAprobados) {
            btnAprobados.style.background = '#10b981';
            btnAprobados.style.color = '#fff';
        }

        aplicarFiltrosMatriz();
    }

    function aplicarFiltrosMatriz() {
        const inputLive = document.getElementById('input-matriz-live-search');
        const query = inputLive ? inputLive.value.trim().toLowerCase() : '';
        const filas = document.querySelectorAll('tr.fila-matriz-aprendiz');
        const noMatchTr = document.getElementById('tr-matriz-no-match');
        let visibles = 0;

        filas.forEach(fila => {
            const searchData = fila.getAttribute('data-search') || '';
            const porc = parseFloat(fila.getAttribute('data-porc') || 0);

            let coincideTexto = (query === '' || searchData.includes(query));
            let coincideProgreso = true;

            if (filtroProgresoActivo === 'pendientes') {
                coincideProgreso = (porc < 100);
            } else if (filtroProgresoActivo === 'aprobados') {
                coincideProgreso = (porc >= 100);
            }

            if (coincideTexto && coincideProgreso) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        if (noMatchTr) {
            noMatchTr.style.display = (visibles === 0 && filas.length > 0) ? '' : 'none';
        }

        const spanMostrando = document.getElementById('contador-mostrando-matriz');
        if (spanMostrando) {
            spanMostrando.textContent = visibles;
        }
    }

    function actualizarContadoresPills() {
        const filas = document.querySelectorAll('tr.fila-matriz-aprendiz');
        let total = filas.length;
        let pendientes = 0;
        let aprobados = 0;

        filas.forEach(fila => {
            const porc = parseFloat(fila.getAttribute('data-porc') || 0);
            if (porc >= 100) {
                aprobados++;
            } else {
                pendientes++;
            }
        });

        const pillTodos = document.getElementById('pill-count-todos');
        const pillPend = document.getElementById('pill-count-pendientes');
        const pillAprob = document.getElementById('pill-count-aprobados');

        if (pillTodos) pillTodos.textContent = total;
        if (pillPend) pillPend.textContent = pendientes;
        if (pillAprob) pillAprob.textContent = aprobados;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const inputLive = document.getElementById('input-matriz-live-search');
        if (inputLive) {
            inputLive.addEventListener('input', aplicarFiltrosMatriz);
        }
        actualizarContadoresPills();
    });
</script>
@endsection
