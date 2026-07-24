@extends('layouts.app')

@section('title', 'Historial de Juicios Evaluativos')

@section('content')
<div class="card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; color: var(--text-muted); font-size: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <th style="padding: 1rem 0;">APRENDIZ</th>
                    <th style="padding: 1rem 0;">COMPETENCIA / RAP</th>
                    <th style="padding: 1rem 0;">ESTADO</th>
                    <th style="padding: 1rem 0;">FECHA</th>
                    <th style="padding: 1rem 0;">INSTRUCTOR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($juicios as $juicio)
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 1rem 0;">
                        <div style="font-weight: 600;">{{ $juicio->aprendiz->Nombre }} {{ $juicio->aprendiz->Apellido }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $juicio->aprendiz->Documento }}</div>
                    </td>
                    <td style="padding: 1rem 0;">
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--accent);">
                            {{ $juicio->resultado->competencias->first()->Codigo ?? 'N/A' }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">RAP: {{ $juicio->resultado->Codigo }}</div>
                    </td>
                    <td style="padding: 1rem 0;">
                        @if($juicio->Estado == 1)
                            <span style="color: var(--primary); font-weight: 700;"><i class="fa-solid fa-circle-check"></i> APROBADO</span>
                        @else
                            <span style="color: #f59e0b; font-weight: 700;"><i class="fa-solid fa-circle-exclamation"></i> PENDIENTE</span>
                        @endif
                    </td>
                    <td style="padding: 1rem 0; font-size: 0.85rem;">
                        {{ $juicio->Fecha ? $juicio->Fecha->format('d/m/Y') : 'Sin fecha' }}
                    </td>
                    <td style="padding: 1rem 0; font-size: 0.85rem; color: var(--text-muted);">
                        {{ $juicio->funcionario->Nombre ?? 'Sistema' }} {{ $juicio->funcionario->Apellido ?? '' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 2rem;">
        {{ $juicios->links() }}
    </div>
</div>
@endsection
