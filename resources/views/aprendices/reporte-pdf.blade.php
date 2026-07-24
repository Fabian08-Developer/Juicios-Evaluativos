<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Expediente Académico - {{ $aprendiz->Nombre }} {{ $aprendiz->Apellido }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            padding: 0;
        }

        /* HEADER */
        .header {
            background: #39A900;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-logo {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-sub {
            font-size: 10px;
            opacity: 0.85;
            margin-top: 2px;
        }
        .header-date {
            font-size: 10px;
            text-align: right;
            opacity: 0.85;
        }

        /* BODY */
        .body { padding: 25px 30px; }

        /* SECTION TITLE */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #39A900;
            border-bottom: 2px solid #39A900;
            padding-bottom: 5px;
            margin-bottom: 12px;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* PROFILE GRID */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        .profile-item label {
            display: block;
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .profile-item span {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        /* BADGE */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-warning { background: #fef9c3; color: #ca8a04; border: 1px solid #fde68a; }
        .badge-info    { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }

        /* COMPETENCIA TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        thead th {
            background: #0f172a;
            color: white;
            padding: 8px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            color: #334155;
        }

        /* PROGRESS BAR */
        .progress-wrap {
            background: #e2e8f0;
            border-radius: 4px;
            height: 8px;
            width: 100%;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: #39A900;
            border-radius: 4px;
        }

        /* RESULTS */
        .resultado-row td {
            background: #f8fafc;
            font-size: 9.5px;
            padding: 6px 10px 6px 25px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* FOOTER */
        .footer {
            margin-top: 30px;
            padding: 12px 30px;
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div>
        <div class="header-logo">🎓 SENA — Servicio Nacional de Aprendizaje</div>
        <div class="header-sub">Sistema de Gestión de Juicios Evaluativos</div>
    </div>
    <div class="header-date">
        Generado el: {{ now()->format('d/m/Y H:i') }}<br>
        Documento Oficial
    </div>
</div>

<div class="body">

    <!-- DATOS PERSONALES -->
    <div class="section-title">📋 Datos del Aprendiz</div>
    <div class="profile-grid">
        <div class="profile-item">
            <label>Nombre Completo</label>
            <span>{{ $aprendiz->Nombre }} {{ $aprendiz->Apellido }}</span>
        </div>
        <div class="profile-item">
            <label>Documento de Identidad</label>
            <span>{{ $aprendiz->Tipo_Documento }} {{ $aprendiz->Documento }}</span>
        </div>
        <div class="profile-item">
            <label>Estado</label>
            @php
                $badgeClass = 'badge-info';
                if($aprendiz->Estado == 'EN FORMACION') $badgeClass = 'badge-success';
                if(in_array($aprendiz->Estado, ['RETIRO VOLUNTARIO', 'TRASLADADO'])) $badgeClass = 'badge-warning';
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $aprendiz->Estado }}</span>
        </div>
        <div class="profile-item">
            <label>Número de Ficha</label>
            <span style="color: #39A900; font-size: 13px;">{{ $aprendiz->Id_Ficha }}</span>
        </div>
        <div class="profile-item" style="grid-column: span 2;">
            <label>Programa de Formación</label>
            <span>{{ $aprendiz->ficha->programa->Nombre ?? 'No asignado' }}</span>
        </div>
    </div>

    <!-- RESUMEN ESTADÍSTICO -->
    <div class="section-title">📊 Resumen de Avance</div>
    @php
        $totalJuicios = $aprendiz->juicios->count();
        $totalAprobados = $aprendiz->juicios->where('Estado', 1)->count();
        $totalPendientes = $totalJuicios - $totalAprobados;
        $porcentajeGlobal = $totalJuicios > 0 ? round(($totalAprobados / $totalJuicios) * 100) : 0;
    @endphp
    <table>
        <thead>
            <tr>
                <th>Total Juicios</th>
                <th>Aprobados</th>
                <th>Por Evaluar</th>
                <th>% Global de Avance</th>
                <th>Progreso</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: bold; font-size: 13px;">{{ $totalJuicios }}</td>
                <td style="color: #16a34a; font-weight: bold; font-size: 13px;">{{ $totalAprobados }}</td>
                <td style="color: #ca8a04; font-weight: bold; font-size: 13px;">{{ $totalPendientes }}</td>
                <td style="font-weight: bold; font-size: 13px;">{{ $porcentajeGlobal }}%</td>
                <td style="width: 150px;">
                    <div class="progress-wrap">
                        <div class="progress-bar" style="width: {{ $porcentajeGlobal }}%;"></div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- DETALLE POR COMPETENCIAS -->
    <div class="section-title">🎯 Detalle por Competencias y Resultados</div>
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">CÓDIGO</th>
                <th>COMPETENCIA / RESULTADO</th>
                <th style="width: 60px; text-align: center;">ESTADO</th>
                <th style="width: 80px; text-align: center;">AVANCE</th>
                <th style="width: 100px;">PROGRESO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($avancePorCompetencia as $data)
                <!-- Fila de Competencia -->
                <tr>
                    <td style="font-weight: bold; color: #39A900; background: #f0fdf4;">{{ $data['codigo'] }}</td>
                    <td style="font-weight: bold; background: #f0fdf4; font-size: 10px;">{{ $data['nombre'] }}</td>
                    <td style="text-align: center; background: #f0fdf4;">
                        <span class="badge {{ $data['porcentaje'] == 100 ? 'badge-success' : 'badge-warning' }}">
                            {{ $data['aprobados'] }}/{{ $data['total'] }}
                        </span>
                    </td>
                    <td style="text-align: center; font-weight: bold; background: #f0fdf4; color: #39A900;">
                        {{ round($data['porcentaje']) }}%
                    </td>
                    <td style="background: #f0fdf4;">
                        <div class="progress-wrap">
                            <div class="progress-bar" style="width: {{ $data['porcentaje'] }}%;"></div>
                        </div>
                    </td>
                </tr>
                <!-- Filas de Resultados -->
                @foreach($data['juicios'] as $juicio)
                <tr class="resultado-row">
                    <td style="color: #64748b;">↳ {{ $juicio->resultado->Codigo ?? '---' }}</td>
                    <td style="color: #475569;">{{ $juicio->resultado->Nombre ?? 'Sin nombre' }}</td>
                    <td style="text-align: center;">
                        @if($juicio->Estado == 1)
                            <span class="badge badge-success">✓ Aprobado</span>
                        @else
                            <span class="badge badge-warning">⏳ Pendiente</span>
                        @endif
                    </td>
                    <td colspan="2" style="color: #94a3b8; font-size: 9px;">
                        {{ $juicio->Fecha ? \Carbon\Carbon::parse($juicio->Fecha)->format('d/m/Y') : 'Sin fecha' }}
                    </td>
                </tr>
                @endforeach
            @endforeach

            @if($avancePorCompetencia->isEmpty())
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">
                    No se encontraron juicios registrados para este aprendiz.
                </td>
            </tr>
            @endif
        </tbody>
    </table>

</div>

<!-- FOOTER -->
<div class="footer">
    SENA — Servicio Nacional de Aprendizaje &nbsp;|&nbsp; Gestión de Juicios Evaluativos &nbsp;|&nbsp;
    Este documento es generado automáticamente por el sistema y tiene validez informativa.
    &nbsp;|&nbsp; {{ now()->format('Y') }}
</div>

</body>
</html>
