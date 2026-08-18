<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta Temprana de Aprendices en Riesgo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            color: #1e293b;
        }
        .container {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #0b1120 0%, #1e293b 100%);
            padding: 30px 35px;
            color: #ffffff;
            border-bottom: 4px solid #39A900;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .badge-radicado {
            display: inline-block;
            background: rgba(57, 169, 0, 0.15);
            color: #4ade80;
            border: 1px solid rgba(57, 169, 0, 0.3);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 10px;
        }
        .content {
            padding: 30px 35px;
        }
        .intro-text {
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
            margin-bottom: 25px;
        }
        .table-wrap {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge-critico {
            display: inline-block;
            background: #fee2e2;
            color: #ef4444;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-moderado {
            display: inline-block;
            background: #fef3c7;
            color: #d97706;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .footer {
            background: #f8fafc;
            padding: 20px 35px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏛️ SERVICIO NACIONAL DE APRENDIZAJE — SENA</h1>
            <p>Sistema Integral de Seguimiento Académico y Juicios Evaluativos</p>
            <div class="badge-radicado">Radicado: {{ $radicado }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="intro-text">
                Estimado equipo de <strong>Bienestar al Aprendiz</strong> y <strong>Coordinación Académica</strong>,<br><br>
                A través del presente reporte se emite una <strong>Alerta Oficial de Riesgo Académico y Deserción</strong> correspondiente a la fecha <strong>{{ $fecha }}</strong>.
                Se solicita amablemente iniciar el proceso de citación, asesoría psicopedagógica y acompañamiento a los aprendices relacionados a continuación:
            </p>

            @if($fichaId)
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px;">
                <strong>Ficha:</strong> {{ $fichaId }} @if($programaNombre) — <span>{{ $programaNombre }}</span>@endif
            </div>
            @endif

            <!-- Tabla de Aprendices -->
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Documento</th>
                            <th>Ficha</th>
                            <th>Pendientes</th>
                            <th>Score Riesgo</th>
                            <th>Nivel</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($aprendices as $ap)
                        <tr>
                            <td><strong>{{ $ap['nombre'] }} {{ $ap['apellido'] }}</strong></td>
                            <td>{{ $ap['documento'] }}</td>
                            <td>{{ $ap['ficha'] ?? '---' }}</td>
                            <td><strong style="color: #ef4444;">{{ $ap['pendientes_count'] ?? 0 }}</strong></td>
                            <td><strong>{{ $ap['score_riesgo'] ?? 0 }}%</strong></td>
                            <td>
                                @if(($ap['score_riesgo'] ?? 0) >= 70)
                                    <span class="badge-critico">🔴 Crítico</span>
                                @else
                                    <span class="badge-moderado">🟡 Moderado</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">
                📌 <em>Nota: Este reporte ha sido generado automáticamente desde el Sistema de Juicios Evaluativos del Centro de Formación.</em>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 5px 0;"><strong>SENA — Centro de Formación Agroindustrial / Tecnológico</strong></p>
            <p style="margin: 0;">Reporte generado automáticamente • Juicios Evaluativos SENA</p>
        </div>
    </div>
</body>
</html>
