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
                            <form action="{{ route('fichas.destroy', $ficha->Id_Ficha) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta ficha?')" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline" style="padding: 0.5rem; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; color: #fca5a5;" title="Eliminar">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 4rem; text-align: center; color: var(--text-muted);">
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
@endsection
