{{-- Filas del reporte que no se pudieron importar (se muestran tras subir un archivo). --}}
@if(session('warning_errores'))
<div class="card" style="margin-bottom: 2rem; border: 1px solid rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05); padding: 1.5rem;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #f59e0b;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.25rem;"></i>
            </div>
            <div>
                <h4 style="margin: 0 0 0.4rem 0; color: #fde68a; font-size: 1rem; font-weight: 700;">
                    Filas omitidas durante la importación
                </h4>
                <p style="margin: 0 0 0.8rem 0; font-size: 0.85rem; color: var(--text-muted);">
                    Las siguientes filas del documento presentaron inconsistencias y no pudieron procesarse:
                </p>
                <div style="max-height: 180px; overflow-y: auto; background: rgba(0,0,0,0.25); border-radius: 10px; padding: 0.75rem 1rem; border: 1px solid rgba(255,255,255,0.05);">
                    <ul style="margin: 0; padding-left: 1.1rem; font-size: 0.8rem; color: #fde68a; line-height: 1.6;">
                        @foreach(session('warning_errores') as $errorFila)
                            <li>
                                <strong>Fila {{ $errorFila['fila'] ?? '?' }}:</strong>
                                {{ $errorFila['dato'] ? "Dato: [{$errorFila['dato']}] — " : "" }}
                                <span style="color: #fca5a5;">{{ $errorFila['error'] ?? 'Error desconocido' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
