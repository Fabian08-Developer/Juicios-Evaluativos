@extends('layouts.app')

@section('title', 'Matriz Interactiva de Evaluación Rápida')

@section('content')
<div style="max-width: 1450px; margin: 0 auto;">

    <!-- Encabezado superior y selectores de Ficha y Competencia -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1.5rem;">
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

        <!-- Filtros de Ficha y Competencia -->
        <form method="GET" action="{{ route('acciones.matriz') }}" id="form-matriz-filtros" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; background: rgba(0,0,0,0.3); padding: 0.6rem 1.2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
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
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Competencia:</label>
                <select name="competencia_id" onchange="document.getElementById('form-matriz-filtros').submit()" class="form-control" style="padding: 0.45rem 2.2rem 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 10px; max-width: 380px;">
                    @foreach($competencias as $c)
                        <option value="{{ $c->Id_Competencia }}" {{ $competenciaId == $c->Id_Competencia ? 'selected' : '' }}>
                            [{{ $c->Codigo }}] {{ Str::limit($c->Nombre, 55) }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>
    </div>

    <!-- Indicador flotante de Estado AJAX -->
    <div id="toast-autoguardado" style="position: fixed; bottom: 30px; right: 30px; background: #10b981; color: #fff; padding: 0.8rem 1.4rem; border-radius: 14px; font-weight: 800; font-size: 0.9rem; box-shadow: 0 10px 25px -5px rgba(16,185,129,0.5); display: flex; align-items: center; gap: 0.6rem; z-index: 9999; transform: translateY(100px); opacity: 0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 1.1rem;"></i>
        <span id="toast-mensaje">Autoguardado en servidor...</span>
    </div>

    @if($aprendices->isEmpty() || $resultados->isEmpty())
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <i class="fa-solid fa-folder-open" style="font-size: 3.5rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: #fff; font-weight: 800;">No hay datos para evaluar</h3>
            <p style="color: var(--text-muted); max-width: 450px; margin: 0.5rem auto 0;">Verifica que la ficha seleccionada tenga aprendices cargados y competencias asignadas.</p>
        </div>
    @else
        <!-- Panel de instrucciones y acciones rápidas -->
        <div class="card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: rgba(0,0,0,0.25);">
            <div style="display: flex; align-items: center; gap: 1.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span><i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> <strong>Instrucciones:</strong> Haz clic directamente en el interruptor de cualquier celda para alternar entre <em>Aprobado</em> y <em>Pendiente</em>.</span>
                <span style="display: flex; align-items: center; gap: 0.4rem;"><span style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; display: inline-block;"></span> Aprobado (1)</span>
                <span style="display: flex; align-items: center; gap: 0.4rem;"><span style="width: 12px; height: 12px; background: #ef4444; border-radius: 50%; display: inline-block;"></span> Pendiente (0)</span>
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="button" onclick="aprobarTodosLote()" class="btn" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 800; font-size: 0.8rem; padding: 0.5rem 1rem; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-check-double"></i> Aprobar Todo en Pantalla
                </button>
            </div>
        </div>

        <!-- Matriz Hoja de Cálculo Web -->
        <div class="card" style="padding: 0; overflow-x: auto; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.85);">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
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
                <tbody>
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
                    @endphp
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <!-- Columna fija: Aprendiz -->
                        <td style="padding: 0.85rem 1.25rem; position: sticky; left: 0; background: #0f172a; z-index: 5; border-right: 1px solid rgba(255,255,255,0.08);">
                            <div style="font-weight: 800; color: #fff; font-size: 0.9rem;">
                                <a href="{{ route('aprendices.show', $ap->Id_Aprendiz) }}" style="color: #fff; text-decoration: none;" target="_blank">
                                    {{ $ap->Nombre }} {{ $ap->Apellido }}
                                </a>
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); display: flex; justify-content: space-between; margin-top: 0.15rem;">
                                <span>Doc: {{ $ap->Documento }}</span>
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
                </tbody>
            </table>
        </div>
    @endif

</div>

<!-- Script AJAX de Autoguardado Reactivo -->
<script>
    const csrfToken = "{{ csrf_token() }}";
    const updateUrl = "{{ route('acciones.matriz.actualizar') }}";

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

    function aprobarTodosLote() {
        if (!confirm('¿Deseas cambiar a APROBADO todas las celdas en pantalla para esta competencia?')) return;
        
        const botones = document.querySelectorAll('button[id^="btn-toggle-"]');
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
            mostrarToast('✅ Todos ya están aprobados');
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
</script>
@endsection
