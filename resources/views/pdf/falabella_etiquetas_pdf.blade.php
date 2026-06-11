<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas Internas – {{ $selectedDate }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; background: white; }
        
        /* 2x2 Table Layout for A4 compatibility */
        .page { width: 100%; height: 100%; page-break-after: always; display: table; table-layout: fixed; }
        .row { display: table-row; height: 50%; }
        .cell { 
            display: table-cell; 
            width: 50%; 
            height: 50%; 
            border: 0.5pt dashed #ccc; 
            vertical-align: top; 
            padding: 20px; 
            overflow: hidden;
        }

        .header { margin-bottom: 15px; border-bottom: 1pt solid #eee; padding-bottom: 10px; }
        .order-num { font-size: 16px; font-weight: bold; color: #333; }
        .urgente { color: white; background: red; padding: 2px 8px; border-radius: 3px; font-weight: bold; float: right; font-size: 12px; }
        
        .section { margin-bottom: 12px; }
        .label { font-weight: bold; color: #666; margin-bottom: 2px; text-transform: uppercase; font-size: 8px; }
        .content { font-size: 11px; color: #000; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { text-align: left; border-bottom: 0.5pt solid #eee; padding: 3px 0; color: #666; }
        .items-table td { padding: 5px 0; border-bottom: 0.2pt solid #f9f9f9; vertical-align: top; }
        .qty { font-weight: bold; font-size: 14px; text-align: right; }

        .footer { position: absolute; bottom: 20px; left: 20px; right: 20px; font-size: 9px; color: #999; }
    </style>
</head>
<body>

@php $chunks = $orders->chunk(4); @endphp

@foreach($chunks as $chunk)
    <div class="page">
        <div class="row">
            @for($i = 0; $i < 2; $i++)
                <div class="cell">
                    @if(isset($chunk[$i]))
                        @php $order = $chunk[$i]; @endphp
                        <div class="header">
                            @if($order->promised_shipping_time && $order->promised_shipping_time->isPast())
                                <span class="urgente">URGENTE</span>
                            @endif
                            <div class="order-num">ORDEN #{{ $order->order_number }}</div>
                            <div style="font-size: 9px; color: #666;">FALABELLA SELLER CENTER</div>
                        </div>

                        <div class="section">
                            <div class="label">Cliente:</div>
                            <div class="content"><strong>{{ $order->customer_name }}</strong></div>
                            <div class="content">{{ $order->shipping_city }} - {{ $order->shipping_address }}</div>
                        </div>

                        <div class="section">
                            <div class="label">Envío:</div>
                            <div class="content">{{ $order->shipping_type }} | {{ $order->delivery_info }}</div>
                        </div>

                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>PRODUCTO</th>
                                    <th style="width: 30px; text-align: right;">CANT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}<br><small style="color:#666">{{ $item->seller_sku }}</small></td>
                                        <td class="qty">{{ $item->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div style="margin-top: 15px; font-size: 8px; color: #999;">
                            Promesa: {{ $order->promised_shipping_time ? $order->promised_shipping_time->format('d/m/Y H:i') : 'N/A' }}
                        </div>
                    @endif
                </div>
            @endfor
        </div>
        <div class="row">
            @for($i = 2; $i < 4; $i++)
                <div class="cell">
                    @if(isset($chunk[$i]))
                        @php $order = $chunk[$i]; @endphp
                        <div class="header">
                            @if($order->promised_shipping_time && $order->promised_shipping_time->isPast())
                                <span class="urgente">URGENTE</span>
                            @endif
                            <div class="order-num">ORDEN #{{ $order->order_number }}</div>
                            <div style="font-size: 9px; color: #666;">FALABELLA SELLER CENTER</div>
                        </div>

                        <div class="section">
                            <div class="label">Cliente:</div>
                            <div class="content"><strong>{{ $order->customer_name }}</strong></div>
                            <div class="content">{{ $order->shipping_city }} - {{ $order->shipping_address }}</div>
                        </div>

                        <div class="section">
                            <div class="label">Envío:</div>
                            <div class="content">{{ $order->shipping_type }} | {{ $order->delivery_info }}</div>
                        </div>

                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>PRODUCTO</th>
                                    <th style="width: 30px; text-align: right;">CANT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}<br><small style="color:#666">{{ $item->seller_sku }}</small></td>
                                        <td class="qty">{{ $item->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div style="margin-top: 15px; font-size: 8px; color: #999;">
                            Promesa: {{ $order->promised_shipping_time ? $order->promised_shipping_time->format('d/m/Y H:i') : 'N/A' }}
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    </div>
@endforeach

</body>
</html>
