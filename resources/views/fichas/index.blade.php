@extends('layouts.app')

@section('title', 'Gestión de Fichas')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="margin: 0; font-size: 1.75rem;">Administración de Fichas</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Configura los grupos y programas de formación</p>
    </div>
    <a href="{{ route('fichas.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Nueva Ficha
    </a>
</div>

@if(session('error'))
    <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; color: #fca5a5; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
    </div>
@endif

<div class="card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
            <thead>
                <tr style="text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 1rem;">NÚMERO DE FICHA</th>
                    <th style="padding: 1rem;">PROGRAMA</th>
                    <th style="padding: 1rem; text-align: right;">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fichas as $ficha)
                <tr style="background: rgba(255,255,255,0.02); transition: background 0.3s;">
                    <td style="padding: 1.25rem 1rem; border-radius: 12px 0 0 12px;">
                        <div style="font-weight: 800; color: var(--primary); font-size: 1.1rem;">{{ $ficha->Id_Ficha }}</div>
                    </td>
                    <td style="padding: 1.25rem 1rem;">
                        <div style="font-weight: 600; color: #f1f5f9;">{{ $ficha->programa->Nombre ?? 'N/A' }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Cod: {{ $ficha->programa->Codigo ?? '---' }}</div>
                    </td>
                    <td style="padding: 1.25rem 1rem; text-align: right; border-radius: 0 12px 12px 0;">
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <a href="{{ route('fichas.edit', $ficha->Id_Ficha) }}" class="btn btn-outline" style="padding: 0.5rem; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button type="button"
                                    onclick="openDeleteModal('{{ $ficha->Id_Ficha }}', '{{ addslashes($ficha->programa->Nombre ?? 'Sin programa') }}', '{{ route('fichas.destroy', $ficha->Id_Ficha) }}')"
                                    class="btn btn-outline"
                                    style="padding: 0.5rem; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; color: #fca5a5; transition: all 0.2s;"
                                    onmouseover="this.style.color='#ef4444'; this.style.borderColor='rgba(239,68,68,0.4)'; this.style.background='rgba(239,68,68,0.1)'"
                                    onmouseout="this.style.color='#fca5a5'; this.style.borderColor='var(--glass-border)'; this.style.background='rgba(255,255,255,0.03)'"
                                    title="Eliminar Ficha">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
                        No hay fichas registradas actualmente.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 2.5rem; display: flex; justify-content: center;">
        {{ $fichas->links() }}
    </div>
</div>

<!-- ======================== MODAL ESTILIZADO DE ELIMINACIÓN ======================== -->
<div id="deleteModalBackdrop"
     style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 99999; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.25s ease;"
     onclick="handleBackdropClick(event)">

    <div id="deleteModalCard"
         style="background: linear-gradient(145deg, #182234, #0f172a); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.85), 0 10px 25px -5px rgba(0, 0, 0, 0.5); border-radius: 24px; padding: 2.25rem; max-width: 480px; width: 92%; transform: scale(0.92) translateY(15px); transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative; overflow: hidden;">

        <!-- Botón cerrar (X) -->
        <button type="button" onclick="closeDeleteModal()"
                style="position: absolute; top: 1.25rem; right: 1.25rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 10px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); cursor: pointer; transition: all 0.2s;"
                onmouseover="this.style.color='#fff'; this.style.background='rgba(255,255,255,0.1)'"
                onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(255,255,255,0.05)'">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Ícono de Alerta -->
        <div style="width: 64px; height: 64px; border-radius: 20px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #ef4444; font-size: 1.75rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <!-- Textos del Modal -->
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.45rem; font-weight: 800; color: #ffffff;">
                ¿Eliminar Ficha?
            </h3>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0; line-height: 1.5;">
                Estás a punto de eliminar esta ficha. Se borrarán permanentemente todos los <strong style="color: #fca5a5;">aprendices</strong> y sus <strong style="color: #fca5a5;">juicios evaluativos</strong>.
            </p>
        </div>

        <!-- Tarjeta Informativa de la Ficha Seleccionada -->
        <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 1rem 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(57, 169, 0, 0.12); border: 1px solid rgba(57, 169, 0, 0.25); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.2rem; flex-shrink: 0;">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <div style="overflow: hidden; flex: 1;">
                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em;">Ficha Seleccionada</div>
                <div id="modalFichaNumber" style="font-weight: 800; font-size: 1.15rem; color: #ffffff; margin-top: 2px;">---</div>
                <div id="modalFichaPrograma" style="font-size: 0.8rem; color: var(--text-muted); text-overflow: ellipsis; white-space: nowrap; overflow: hidden; margin-top: 2px;">---</div>
            </div>
        </div>

        <!-- Acciones del Modal -->
        <form id="deleteFichaForm" method="POST" style="display: flex; gap: 1rem;">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteModal()" class="btn btn-outline"
                    style="flex: 1; justify-content: center; padding: 0.85rem 1rem; font-size: 0.92rem; font-weight: 600;">
                Cancelar
            </button>
            <button type="submit" id="modalConfirmBtn" class="btn"
                    style="flex: 1.2; justify-content: center; padding: 0.85rem 1rem; font-size: 0.92rem; font-weight: 700; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.35); transition: all 0.2s;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239, 68, 68, 0.5)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(239, 68, 68, 0.35)'">
                <i class="fa-solid fa-trash-can" style="margin-right: 6px;"></i> Eliminar Ficha
            </button>
        </form>
    </div>
</div>

<script>
    const deleteModalBackdrop = document.getElementById('deleteModalBackdrop');
    const deleteModalCard     = document.getElementById('deleteModalCard');
    const deleteFichaForm     = document.getElementById('deleteFichaForm');
    const modalFichaNumber    = document.getElementById('modalFichaNumber');
    const modalFichaPrograma  = document.getElementById('modalFichaPrograma');
    const modalConfirmBtn     = document.getElementById('modalConfirmBtn');

    function openDeleteModal(fichaId, programaNombre, actionUrl) {
        modalFichaNumber.textContent   = 'Ficha ' + fichaId;
        modalFichaPrograma.textContent = programaNombre || 'Sin programa asignado';
        deleteFichaForm.action         = actionUrl;

        deleteModalBackdrop.style.display = 'flex';
        // Forzar reflow para animación
        void deleteModalBackdrop.offsetWidth;

        deleteModalBackdrop.style.opacity = '1';
        deleteModalCard.style.transform   = 'scale(1) translateY(0)';
        document.body.style.overflow      = 'hidden';
    }

    function closeDeleteModal() {
        deleteModalBackdrop.style.opacity = '0';
        deleteModalCard.style.transform   = 'scale(0.92) translateY(15px)';
        document.body.style.overflow      = '';

        setTimeout(() => {
            deleteModalBackdrop.style.display = 'none';
        }, 250);
    }

    function handleBackdropClick(e) {
        if (e.target === deleteModalBackdrop) {
            closeDeleteModal();
        }
    }

    // Escuchar tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && deleteModalBackdrop.style.display === 'flex') {
            closeDeleteModal();
        }
    });

    // Feedback visual en el botón al enviar
    deleteFichaForm.addEventListener('submit', () => {
        modalConfirmBtn.disabled = true;
        modalConfirmBtn.style.opacity = '0.75';
        modalConfirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right: 6px;"></i> Eliminando...';
    });
</script>
@endsection
