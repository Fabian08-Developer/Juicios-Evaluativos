@extends('layouts.app')

@section('title', 'Nueva Ficha')

@section('content')
<div class="card" style="max-width: 700px; margin: 0 auto; padding: 3rem;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <div style="width: 80px; height: 80px; background: rgba(14, 165, 233, 0.1); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 1px solid rgba(14, 165, 233, 0.2);">
            <i class="fa-solid fa-folder-plus" style="font-size: 2rem; color: var(--accent);"></i>
        </div>
        <h2 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Registrar Nueva Ficha</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Completa los detalles para habilitar un nuevo grupo de formación</p>
    </div>

    @if($errors->any())
        <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 1.25rem; border-radius: 12px; margin-bottom: 2.5rem; color: #fca5a5;">
            <ul style="margin: 0; padding-left: 1.2rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('fichas.store') }}" method="POST">
        @csrf
        
        <div style="margin-bottom: 1.5rem;">
            <label for="Id_Ficha" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Número de Ficha</label>
            <input type="number" name="Id_Ficha" id="Id_Ficha" class="form-control" value="{{ old('Id_Ficha') }}" required placeholder="Ej. 2500000">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="Id_Programa" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Programa de Formación</label>
            <select name="Id_Programa" id="Id_Programa" required class="form-control">
                <option value="">-- Selecciona un programa --</option>
                @foreach($programas as $programa)
                    <option value="{{ $programa->Id_Programa }}" {{ old('Id_Programa') == $programa->Id_Programa ? 'selected' : '' }}>
                        {{ $programa->Nombre }} ({{ $programa->Codigo }})
                    </option>
                @endforeach
            </select>
        </div>



        <div style="display: flex; gap: 1rem;">
            <a href="{{ route('fichas.index') }}" class="btn btn-outline" style="flex: 1; justify-content: center; padding: 1rem;">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary" style="flex: 2; justify-content: center; padding: 1rem;">
                <i class="fa-solid fa-save"></i> Guardar Ficha
            </button>
        </div>
    </form>
</div>
@endsection
