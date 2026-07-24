<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Compromiso - {{ $aprendiz->Nombre }} {{ $aprendiz->Apellido }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            padding: 0;
        }

        .header {
            background: #39A900;
            color: white;
            padding: 20px 30px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-logo {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-sub {
            font-size: 10px;
            opacity: 0.9;
            margin-top: 3px;
        }
        .header-date {
            font-size: 10px;
            text-align: right;
            opacity: 0.9;
        }

        .body { padding: 25px 30px; }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #39A900;
            border-bottom: 2px solid #39A900;
            padding-bottom: 5px;
            margin-bottom: 12px;
            margin-top: 20px;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label {
            font-weight: bold;
            color: #64748b;
            width: 30%;
        }

        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 11px;
            line-height: 1.5;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .results-table th {
            background: #39A900;
            color: white;
            padding: 8px 10px;
            font-size: 10px;
            text-align: left;
            border: 1px solid #39A900;
        }
        .results-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .results-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .signatures {
            margin-top: 50px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .sign-line {
            border-top: 1px solid #334155;
            margin-top: 40px;
            padding-top: 6px;
            font-weight: bold;
        }
        .sign-sub {
            font-size: 9px;
            color: #64748b;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 30px;
            right: 30px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-logo">SENA SOFIA PLUS</div>
                    <div class="header-sub">Acta Oficial de Plan de Mejoramiento Académico</div>
                </td>
                <td class="header-date">
                    Fecha de emisión:<br>
                    <strong>{{ now()->format('d/m/Y - H:i') }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="body">
        
        <div class="alert-box">
            <strong>⚠️ COMPROMISO ACADÉMICO DE SALVACIÓN FORMATIVA</strong><br>
            El presente documento certifica que el aprendiz se encuentra en proceso de nivelación pedagógica y suscribe un compromiso formal para la presentación y aprobación de los Juicios Evaluativos pendientes descritos a continuación, con el fin de superar el estado de riesgo académico y cumplir con el 100% del programa de formación.
        </div>

        <div class="section-title">Datos Generales del Aprendiz</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Aprendiz:</td>
                <td><strong>{{ strtoupper($aprendiz->Nombre . ' ' . $aprendiz->Apellido) }}</strong></td>
                <td class="info-label">Documento:</td>
                <td>{{ $aprendiz->Tipo_Documento ?? 'CC' }} {{ $aprendiz->Documento }}</td>
            </tr>
            <tr>
                <td class="info-label">Ficha de Caracterización:</td>
                <td><strong>{{ $aprendiz->Id_Ficha }}</strong></td>
                <td class="info-label">Programa:</td>
                <td>{{ $aprendiz->ficha->programa->Nombre ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Estado Actual en Sistema:</td>
                <td><span style="color: #ef4444; font-weight: bold;">{{ $aprendiz->Estado }}</span></td>
                <td class="info-label">Avance Global del Expediente:</td>
                <td><strong>{{ $tasaActual }}%</strong> ({{ $aprobados }} de {{ $totalJuicios }} juicios aprobados)</td>
            </tr>
        </table>

        <div class="section-title">Resultados de Aprendizaje Pendientes por Aprobar ({{ $pendientes->count() }})</div>
        @if($pendientes->isEmpty())
            <p style="padding: 15px; color: #10b981; font-weight: bold;">No se registran juicios evaluativos pendientes.</p>
        @else
            <table class="results-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Código RA</th>
                        <th style="width: 50%;">Denominación del Resultado de Aprendizaje</th>
                        <th style="width: 35%;">Competencia Asociada</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendientes as $resultado)
                    <tr>
                        <td style="font-weight: bold; color: #39A900;">{{ $resultado->Codigo }}</td>
                        <td>{{ $resultado->Nombre }}</td>
                        <td style="color: #475569;">{{ $resultado->competencia->Nombre ?? 'Competencia General' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table class="signatures">
            <tr>
                <td>
                    <div class="sign-line">{{ strtoupper($aprendiz->Nombre . ' ' . $aprendiz->Apellido) }}</div>
                    <div class="sign-sub">Firma del Aprendiz<br>Doc: {{ $aprendiz->Documento }}</div>
                </td>
                <td>
                    <div class="sign-line">INSTRUCTOR / COORDINADOR ACADÉMICO</div>
                    <div class="sign-sub">Servicio Nacional de Aprendizaje — SENA<br>Centro de Formación</div>
                </td>
            </tr>
        </table>

    </div>

    <div class="footer">
        Sistema Inteligente de Juicios Evaluativos SENA • Documento generado automáticamente por la Plataforma del Instructor • Verificación Electrónica
    </div>

</body>
</html>
