<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manifiesto de Despacho - {{ !empty($is_multiple) ? 'Todas las Zonas' : 'Zona ' . ($zona ?? '') }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #043e69;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header table {
            width: 100%;
            border: none;
        }
        .header td {
            border: none;
            padding: 0;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #043e69;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }
        .info-box {
            font-size: 12px;
            text-align: right;
        }
        .info-box strong {
            color: #043e69;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #043e69;
            color: #fff;
            text-align: left;
            font-size: 12px;
        }
        table.data-table td {
            font-size: 11px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge-wsp {
            background-color: #25d366;
            color: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-ml {
            background-color: #ffe600;
            color: #333;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .box-firma {
            width: 100%;
            height: 40px;
            border: 1px dashed #ccc;
            margin-top: 5px;
        }
        .producto-item {
            margin-bottom: 4px;
            border-bottom: 1px dotted #eee;
            padding-bottom: 4px;
        }
        .producto-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .cantidad-badge {
            font-weight: bold;
            background-color: #eee;
            padding: 1px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>

@php
    $zonasParaIterar = !empty($is_multiple) ? $zonas_entregas : [($zona ?? '') => ($entregas ?? [])];
@endphp

@foreach($zonasParaIterar as $currentZona => $currentEntregas)

<div class="header">
    <table>
        <tr>
            <td style="width: 50%;">
                <div class="title">Manifiesto de Despacho</div>
                <div class="subtitle">Zona de Reparto: <strong>{{ strtoupper($currentZona) }}</strong></div>
            </td>
            <td style="width: 50%;" class="info-box">
                Fecha: <strong>{{ date('d/m/Y') }}</strong><br>
                Motorizado: _________________________<br>
                Total Paradas: <strong>{{ count($currentEntregas) }}</strong>
            </td>
        </tr>
    </table>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th class="text-center" style="width: 4%;">#</th>
            <th style="width: 20%;">Cliente / Orden</th>
            <th style="width: 22%;">Dirección de Destino</th>
            <th style="width: 32%;">Productos a Entregar</th>
            <th style="width: 10%;">Cobro / Monto</th>
            <th style="width: 12%;">Celular</th>
        </tr>
    </thead>
    <tbody>
        @foreach($currentEntregas as $index => $entrega)
        <tr>
            <td class="text-center">
                <strong>{{ $index + 1 }}</strong>
            </td>
            <td>
                @if(($entrega['tipo'] ?? 'ml') === 'wsp')
                    <span class="badge-wsp">WSP</span>
                @else
                    <span class="badge-ml">ML</span>
                @endif
                <br>
                <strong>{{ $entrega['buyer_name'] }}</strong><br>
                <small style="color: #666;">Ord: {{ $entrega['order_id'] }}</small>
            </td>
            <td>
                {{ $entrega['destino'] }}
                @if(!empty($entrega['delivery_note']))
                    <div style="color: #c0392b; font-size: 10px; font-weight: bold; margin-top: 3px;">
                        ⏰ Nota: {{ $entrega['delivery_note'] }}
                    </div>
                @endif
                @if(!empty($entrega['nota']))
                    <div style="color: #d35400; font-size: 10px; font-weight: bold; margin-top: 2px;">
                        🔑 Clave: {{ $entrega['nota'] }}
                    </div>
                @endif
            </td>
            <td>
                @if(is_array($entrega['items']) || $entrega['items'] instanceof \Traversable)
                    @foreach($entrega['items'] as $item)
                        <div class="producto-item">
                            <span class="cantidad-badge">x{{ is_object($item) ? $item->quantity : ($item['quantity'] ?? 1) }}</span>
                            {{ is_object($item) ? $item->title : ($item['title'] ?? '') }}
                        </div>
                    @endforeach
                @endif
            </td>
            <td class="text-center">
                @if(($entrega['tipo'] ?? 'ml') === 'wsp')
                    <strong style="color: #e74c3c;">S/ {{ number_format($entrega['costo'], 2) }}</strong>
                @else
                    <span style="color: #999; font-style: italic;">Pagado</span>
                @endif
            </td>
            <td class="text-center">
                <strong>{{ $entrega['telefono'] ?? 'Sin teléfono' }}</strong>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if(!$loop->last)
    <div style="page-break-after: always;"></div>
@endif

@endforeach

</body>
</html>
