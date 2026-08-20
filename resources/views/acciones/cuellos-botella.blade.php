@extends('layouts.app')

@section('title', 'Detector de Cuellos de Botella Académicos')

@section('content')
<div style="max-width: 1250px; margin: 0 auto;">

    <!-- Encabezado superior -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 0.45rem 0.85rem; border-radius: 14px; font-size: 1.25rem;">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                </span>
                Detector de Cuellos de Botella de la Ficha
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.4rem; max-width: 680px;">
                Identifica qué competencias y resultados de aprendizaje específicos concentran la mayor reprobación o pendientes en el grupo para focalizar sesiones pedagógicas de refuerzo.
            </p>
        </div>

        <!-- Selector de Ficha -->
        <form method="GET" action="{{ route('acciones.cuellos-botella') }}" style="display: flex; gap: 0.75rem; align-items: center; background: rgba(0,0,0,0.25); padding: 0.5rem 0.8rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);">
            <label for="ficha_id" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ficha:</label>
            <select name="ficha_id" id="ficha_id" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 0.4rem 2.2rem 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 10px;">
                <option value="">— Todas las Fichas —</option>
                @foreach($fichas as $f)
                    <option value="{{ $f->Id_Ficha }}" {{ $fichaId == $f->Id_Ficha ? 'selected' : '' }}>
                        {{ $f->Id_Ficha }} — {{ $f->programa->Nombre ?? 'SENA' }}
                    </option>
                @endforeach
            </select>
            @if($competenciaSeleccionadaId)
                <input type="hidden" name="competencia_id" value="{{ $competenciaSeleccionadaId }}">
            @endif
        </form>
    </div>

    <!-- Contenido principal: Grid de Ranking y Panel de Grupo de Refuerzo -->
    <div style="display: grid; grid-template-columns: {{ $grupoRefuerzo->isNotEmpty() ? '1.2fr 1fr' : '1fr' }}; gap: 2rem; align-items: start;">
        
        <!-- COLUMNA 1: Ranking de Competencias -->
        <div class="card" style="padding: 1.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.2rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid fa-chart-column" style="color: #f59e0b;"></i> Ranking de Competencias Críticas
                </h2>
                <span class="badge badge-info">{{ $rankingCompetencias->count() }} Competencias Analizadas</span>
            </div>

            @if($rankingCompetencias->isEmpty())
                <div style="text-align: center; padding: 3rem 1rem;">
                    <i class="fa-solid fa-face-smile-wink" style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;"></i>
                    <h3 style="color: #fff; font-weight: 800;">¡Excelente Rendimiento!</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">No se encontraron juicios pendientes en la ficha seleccionada.</p>
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($rankingCompetencias as $idx => $comp)
                        @php
                            $isSelected = $competenciaSeleccionadaId == $comp->Id_Competencia;
                        @endphp
                        <div style="background: {{ $isSelected ? 'rgba(239,68,68,0.12)' : 'rgba(0,0,0,0.2)' }}; 
                                    border: 1px solid {{ $isSelected ? 'rgba(239,68,68,0.4)' : 'rgba(255,255,255,0.06)' }}; 
                                    border-radius: 16px; padding: 1.25rem; transition: all 0.2s;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.3rem;">
                                        <span style="background: {{ $idx == 0 ? '#ef4444' : ($idx == 1 ? '#f59e0b' : 'rgba(255,255,255,0.1)') }}; color: white; width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">
                                            #{{ $idx + 1 }}
                                        </span>
                                        <span style="font-weight: 800; color: var(--primary); font-size: 0.8rem;">Código: {{ $comp->Codigo }}</span>
                                    </div>
                                    <h3 style="font-size: 1.05rem; font-weight: 800; color: #fff; margin: 0;">{{ $comp->Nombre }}</h3>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.3rem; font-weight: 900; color: {{ $idx == 0 ? '#ef4444' : '#fca5a5' }};">
                                        {{ $comp->total_aprendices_afectados }}
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Aprendices con Pendientes</div>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 0.85rem; margin-top: 0.85rem;">
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    Total juicios en mora: <strong style="color: #fff;">{{ $comp->total_juicios_pendientes }}</strong>
                                </div>

                                <a href="{{ route('acciones.cuellos-botella', ['ficha_id' => $fichaId, 'competencia_id' => $comp->Id_Competencia]) }}" 
                                   class="btn" style="background: {{ $isSelected ? '#ef4444' : 'rgba(239,68,68,0.15)' }}; color: {{ $isSelected ? '#fff' : '#fca5a5' }}; font-weight: 800; font-size: 0.78rem; padding: 0.45rem 1rem; border-radius: 10px; text-decoration: none;">
                                    <i class="fa-solid fa-users-viewfinder"></i> {{ $isSelected ? 'Viendo Grupo de Refuerzo' : 'Aislar Grupo de Refuerzo' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- COLUMNA 2: Grupo de Refuerzo (Aislado al hacer clic) -->
        @if($grupoRefuerzo->isNotEmpty() && $competenciaObj)
        <div class="card" style="padding: 1.75rem; border-color: rgba(239,68,68,0.3); background: linear-gradient(180deg, rgba(30,15,25,0.7) 0%, rgba(15,23,42,0.9) 100%); position: sticky; top: 1.5rem;">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <span class="badge badge-warning" style="margin-bottom: 0.4rem; display: inline-block;">Subgrupo Aislado para Citación</span>
                    <h2 style="font-size: 1.25rem; font-weight: 800; color: #fff; margin: 0;">Grupo de Refuerzo Pedagógico</h2>
                    <p style="font-size: 0.8rem; color: #fca5a5; margin-top: 0.3rem;">
                        Competencia: <strong>{{ $competenciaObj->Nombre }}</strong>
                    </p>
                </div>
                <a href="{{ route('acciones.cuellos-botella', ['ficha_id' => $fichaId]) }}" title="Cerrar panel" style="color: var(--text-muted); text-decoration: none; font-size: 1.1rem;">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>

            <!-- Listado de aprendices para citar -->
            <div style="max-height: 480px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; padding-right: 0.5rem;">
                @foreach($grupoRefuerzo as $ap)
                    <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                        <div>
                            <div style="font-weight: 800; font-size: 0.9rem; color: #fff;">{{ $ap->Nombre }} {{ $ap->Apellido }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Doc: {{ $ap->Documento }} • Ficha: {{ $ap->Id_Ficha }}</div>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <!-- Botón Perfil -->
                            <a href="{{ route('aprendices.show', $ap->Id_Aprendiz) }}" title="Ver Expediente"
                               style="width: 34px; height: 34px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; text-decoration: none;">
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Acción masiva sobre el grupo aislado -->
            <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <a href="{{ route('acciones.matriz', ['ficha_id' => $fichaId, 'competencia_id' => $competenciaObj->Id_Competencia]) }}" 
                   class="btn btn-primary" style="width: 100%; text-align: center; font-weight: 800; padding: 0.75rem; border-radius: 12px; background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                    <i class="fa-solid fa-check-double"></i> Calificar a este Grupo en Matriz
                </a>
            </div>

        </div>
        @endif

    </div>

</div>
@endsection
