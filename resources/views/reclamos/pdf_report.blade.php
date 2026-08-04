<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe Técnico - {{ $reclamo->codigoReclamo }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            border-bottom: 2px solid #0056b3;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #0056b3;
            margin: 0;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
            color: #555;
            text-transform: uppercase;
        }

        .header-meta {
            text-align: right;
            margin-top: -45px;
            font-size: 12px;
            color: #666;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            background-color: #f4f4f4;
            padding: 8px 12px;
            border-left: 4px solid #0056b3;
            margin-bottom: 15px;
            margin-top: 30px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table th {
            text-align: left;
            padding: 8px;
            background-color: #fafafa;
            border: 1px solid #ddd;
            width: 25%;
            font-weight: bold;
            color: #555;
        }

        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
            width: 25%;
        }

        .verdict-box {
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }

        .verdict-favorable {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .verdict-desfavorable {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .verdict-pendiente {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .verdict-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .diag-table {
            width: 100%;
            border-collapse: collapse;
        }

        .diag-table th {
            background-color: #0056b3;
            color: #fff;
            padding: 10px;
            text-align: left;
            border: 1px solid #004494;
        }

        .diag-table td {
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .diag-date {
            font-size: 12px;
            color: #777;
            font-weight: bold;
        }

        /* Evita que una fila de la tabla se corte horizontalmente a la mitad del texto */
        .diag-table tr {
            page-break-inside: avoid;
        }

        /* Evita que el bloque de la firma se corte, separando la imagen del texto */
        .firma-container {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    <div class="header">
        @if(!empty($cabecera))
        <img src="{{ $cabecera }}" alt="Cabecera" style="width: 100%; max-height: 80px; object-fit: contain;">
        @else
        <h1 class="logo-text">UNIK TECHNOLOGY</h1>
        @endif
        <div class="report-title">INFORME TÉCNICO DE GARANTÍA</div>
        <div class="header-meta">
            <strong>N° Informe:</strong> {{ $reclamo->codigoReclamo }}<br>
            <strong>Fecha de Emisión:</strong> {{ date('d/m/Y') }}<br>
            <strong>Estado:</strong> {{ $reclamo->estadoGeneral }}
        </div>
    </div>

    <div class="section-title">DATOS DEL CLIENTE Y PRODUCTO</div>
    <table class="info-table">
        <tr>
            <th>Cliente:</th>
            <td colspan="3">
                @if($reclamo->Cliente)
                {{ $reclamo->Cliente->nombre }} {{ $reclamo->Cliente->apellidoPaterno }} {{ $reclamo->Cliente->apellidoMaterno }}
                @else
                {{ $reclamo->nombresCliente ?? 'N/A' }}
                @endif
            </td>
        </tr>
        <tr>
            <th>Documento (DNI/RUC):</th>
            <td>
                @if($reclamo->Cliente)
                {{ $reclamo->Cliente->numeroDocumento }}
                @else
                {{ $reclamo->numeroDocumentoCliente ?? 'N/A' }}
                @endif
            </td>
            <th>N° Orden:</th>
            <td>{{ $reclamo->ordenCompra ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Producto (Modelo):</th>
            <td colspan="3">{{ $reclamo->ProductoFisico->nombreProducto ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Número de Serie:</th>
            <td colspan="3"><strong>{{ $reclamo->ProductoFisico->numeroSerie ?? 'N/A' }}</strong></td>
        </tr>
    </table>

    <div class="section-title">VEREDICTO FINAL</div>
    @php
        $veredictoFinal = 'PENDIENTE';
        $verdictClass = 'verdict-pendiente';

        if ($reclamo->Diagnosticos && count($reclamo->Diagnosticos) > 0) {
            $ultimoDiag = $reclamo->Diagnosticos->last();
            if (in_array($ultimoDiag->respuestaSolucion, ['CAMBIO', 'REPARACION', 'NOTA DE CREDITO'])) {
                $veredictoFinal = 'FAVORABLE';
                $verdictClass = 'verdict-favorable';
            } elseif ($ultimoDiag->respuestaSolucion == 'RECHAZADO') {
                $veredictoFinal = 'DESFAVORABLE';
                $verdictClass = 'verdict-desfavorable';
            }
        }
    @endphp
    
    <div class="verdict-box {{ $verdictClass }}">
        <div class="verdict-title">DICTAMEN: {{ $veredictoFinal }}</div>
        <div style="font-size: 15px;">
            @if($veredictoFinal == 'FAVORABLE')
                El equipo ha sido evaluado y la falla reportada procede dentro de los términos y condiciones de la garantía. Se ha aplicado la solución correspondiente para asegurar su óptimo funcionamiento.
            @elseif($veredictoFinal == 'DESFAVORABLE')
                Tras la inspección técnica, se determinó que la falla o condición reportada no está cubierta por los términos de la garantía (ej. daño físico, mal uso, o desgaste normal). No procede la garantía.
            @else
                El equipo se encuentra actualmente en proceso de evaluación o a la espera de un dictamen técnico final.
            @endif
        </div>
    </div>

    <div class="section-title">DETALLE TÉCNICO Y EVALUACIONES</div>

    @if($reclamo->Diagnosticos && count($reclamo->Diagnosticos) > 0)
    <table class="diag-table">
        <thead>
            <tr>
                <th width="20%">Fecha / Técnico</th>
                <th width="40%">Diagnóstico Previo & Situación</th>
                <th width="40%">Solución Aplicada & Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reclamo->Diagnosticos as $diag)
            <tr>
                <td>
                    <div class="diag-date">{{ $diag->created_at->format('d/m/Y H:i') }}</div>
                    <div style="margin-top:5px;">{{ $diag->Tecnico->user ?? 'Técnico' }}</div>
                </td>
                <td>
                    <strong>S.A:</strong> {{ $diag->situacionActual }}<br><br>
                    <strong>Diag:</strong> {{ $diag->diagnosticoPrevio }}<br>
                    <span style="font-size:12px; color:#555;">{{ $diag->descripcionDiagnostico }}</span>
                </td>
                <td>
                    <strong>Solución:</strong> {{ $diag->respuestaSolucion }}<br><br>
                    <strong>Estado Evolución:</strong> {{ $diag->estadoEvolucion }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; color: #777; padding: 20px; border: 1px dashed #ccc;">
        No se han registrado evaluaciones técnicas formales para este caso.
    </p>
    @endif

    <!-- Reemplaza tu div actual por este -->
    <div class="firma-container" style="margin-top: 60px; text-align: center;">
        <div style="width: 250px; margin: 0 auto; border-top: 1px solid #000; padding-top: 10px;">
            @if(!empty($firma))
            <img src="{{ $firma }}" class="firma-empresa" alt="Firma" style="width: 120px; height: 35px; object-fit: contain; margin-bottom: 5px;"><br>
            @endif
            <strong>Firma Autorizada</strong><br>
            UNIK TECHNOLOGY
        </div>
    </div>

</body>

</html>