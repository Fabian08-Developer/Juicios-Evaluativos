<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Oficio de Remisión - {{ $radicado }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            background: #fff;
            padding: 0;
        }

        .header {
            background: #39A900;
            color: white;
            padding: 18px 25px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-logo {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header-sub {
            font-size: 9.5px;
            opacity: 0.95;
            margin-top: 3px;
        }
        .header-date {
            font-size: 9.5px;
            text-align: right;
            opacity: 0.95;
        }

        .body { padding: 22px 25px; }

        .radicado-bar {
            background: #f1f5f9;
            border-left: 4px solid #39A900;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 11px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #39A900;
            border-bottom: 1.5px solid #39A900;
            padding-bottom: 4px;
            margin-bottom: 10px;
            margin-top: 15px;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .info-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9.5px;
        }
        .info-label {
            font-weight: bold;
            color: #64748b;
            width: 25%;
        }

        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 9.5px;
            line-height: 1.45;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .results-table th {
            background: #39A900;
            color: white;
            padding: 7px 8px;
            font-size: 9px;
            text-align: left;
            border: 1px solid #39A900;
        }
        .results-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
        }
        .results-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 15px;
        }
        .sign-line {
            border-top: 1px solid #334155;
            margin-top: 35px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 9.5px;
        }
        .sign-sub {
            font-size: 8.5px;
            color: #64748b;
        }

        .footer {
            position: fixed;
            bottom: 15px;
            left: 25px;
            right: 25px;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-logo">SERVICIO NACIONAL DE APRENDIZAJE — SENA</div>
                    <div class="header-sub">Oficio de Remisión Oficial a Bienestar al Aprendiz y Coordinación</div>
                </td>
                <td class="header-date">
                    Fecha de emisión:<br>
                    <strong>{{ $fecha ?? now()->format('d/m/Y - H:i') }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="body">
        
        <div class="radicado-bar">
            <strong>NÚMERO DE RADICADO:</strong> <span style="color: #39A900; font-weight: bold;">{{ $radicado }}</span>
            &nbsp; | &nbsp; <strong>DESTINATARIO:</strong> Equipo de Bienestar al Aprendiz / Coordinación Académica
        </div>

        <div class="alert-box">
            <strong>REMISIÓN POR ALERTA TEMPRANA DE DESERCIÓN Y RIESGO ACADÉMICO</strong><br>
            Por medio del presente oficio se remite formalmente el listado de aprendices identificados en zona de riesgo académico crítico o moderado según el Sistema de Juicios Evaluativos. Se solicita convocar de manera prioritaria a los aprendices para la suscripción de planes de mejoramiento y asesoría integral.
        </div>

        @if($ficha)
        <div class="section-title">Datos del Grupo / Ficha de Formación</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Ficha de Formación:</td>
                <td><strong>{{ $ficha->Id_Ficha }}</strong></td>
                <td class="info-label">Programa:</td>
                <td>{{ $ficha->programa->Nombre ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Jornada:</td>
                <td>{{ $ficha->Jornada ?? 'DIURNA' }}</td>
                <td class="info-label">Modalidad:</td>
                <td>{{ $ficha->programa->Modalidad ?? 'PRESENCIAL' }}</td>
            </tr>
        </table>
        @endif

        <div class="section-title">Relación de Aprendices Remitidos ({{ count($aprendices) }})</div>
        <table class="results-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 32%;">Nombre y Apellido</th>
                    <th style="width: 18%;">Documento</th>
                    <th style="width: 12%;">Ficha</th>
                    <th style="width: 15%;">Juicios Pendientes</th>
                    <th style="width: 18%;">Nivel de Riesgo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aprendices as $index => $ap)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $ap->Nombre }} {{ $ap->Apellido }}</strong></td>
                    <td>{{ $ap->Tipo_Documento ?? 'CC' }} {{ $ap->Documento }}</td>
                    <td>{{ $ap->Id_Ficha }}</td>
                    <td style="color: #ef4444; font-weight: bold; text-align: center;">{{ $ap->pendientes_count ?? ($ap->remision->total_pendientes ?? 'N/A') }}</td>
                    <td>
                        @php
                            $score = $ap->score_riesgo ?? ($ap->remision->score_riesgo ?? 0);
                        @endphp
                        @if($score >= 70)
                            <span style="color: #ef4444; font-weight: bold;">🔴 Crítico ({{ $score }}%)</span>
                        @else
                            <span style="color: #d97706; font-weight: bold;">🟡 Moderado ({{ $score }}%)</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="signatures">
            <tr>
                <td>
                    <div class="sign-line">INSTRUCTOR / EQUIPO EJECUTOR</div>
                    <div class="sign-sub">Servicio Nacional de Aprendizaje — SENA<br>Centro de Formación</div>
                </td>
                <td>
                    <div class="sign-line">RECIBIDO BIENESTAR AL APRENDIZ</div>
                    <div class="sign-sub">Coordinación de Formación Profesional<br>Firma y Fecha de Radicación</div>
                </td>
            </tr>
        </table>

    </div>

    <div class="footer">
        Sistema de Juicios Evaluativos SENA • Generado automáticamente para trazabilidad institucional • Radicado: {{ $radicado }}
    </div>

</body>
</html>
