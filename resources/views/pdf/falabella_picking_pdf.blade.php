<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Picking – {{ $selectedDate }}</title>

<style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #000;
        margin: 0;
        padding: 0;
    }

    /* HEADER LOGO AREA */
    .logo-container {
        margin-bottom: 25px;
    }
    
    .logo-falabella {
        color: #bed62f; /* Falabella green */
        font-size: 38px;
        font-weight: bold;
        letter-spacing: -2px;
    }
    
    .logo-com {
        color: #bed62f;
        font-size: 20px;
        font-weight: bold;
        letter-spacing: -1px;
    }
    
    .logo-seller {
        color: #5c6670; /* Grey */
        font-size: 18px;
        display: block;
        margin-left: 95px; /* Position under ".com" */
        margin-top: -6px;
    }

    .print-date {
        font-size: 12px;
        margin-bottom: 25px;
        color: #000;
    }

    /* TABLE */
    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    th {
        text-align: left;
        font-weight: bold;
        font-size: 12px;
        padding-bottom: 12px;
        border-bottom: 0; 
    }

    td {
        padding: 10px 5px 10px 0;
        vertical-align: top;
        font-size: 11px;
    }

    /* Columns widths */
    .col-sku {
        width: 25%;
        word-wrap: break-word;
    }
    .col-image {
        width: 15%;
    }
    .col-product {
        width: 35%;
        word-wrap: break-word;
        padding-right: 15px;
    }
    .col-order {
        width: 15%;
        word-wrap: break-word;
    }
    .col-qty {
        width: 10%;
    }

    .product-img {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }

</style>
</head>

<body>

<div class="logo-container">
    <span class="logo-falabella">falabella</span><span class="logo-com">.com</span>
    <span class="logo-seller">Seller Center</span>
</div>

<div class="print-date">
    Checklist printed on: {{ now()->format('d M Y') }}
</div>

<table>
    <thead>
        <tr>
            <th class="col-sku">SKU</th>
            <th class="col-image">Image</th>
            <th class="col-product">Product</th>
            <th class="col-order">Order</th>
            <th class="col-qty">Qty.</th>
        </tr>
    </thead>
    <tbody>
        @if(empty($pickingItems))
            <tr>
                <td colspan="5">No hay datos.</td>
            </tr>
        @else
            @foreach($pickingItems as $item)
            <tr>
                <td class="col-sku">
                    <strong>{{ $item['seller_sku'] }}</strong><br>
                    <small style="color: #666;">{{ $item['falabella_sku'] }}</small>
                </td>
                <td class="col-image">
                    @if(!empty($item['image']))
                        <img src="{{ $item['image'] }}" class="product-img">
                    @endif
                </td>
                <td class="col-product">
                    {{ $item['name'] }}
                </td>
                <td class="col-order">
                    @foreach($item['orders'] as $ord)
                        {{ $ord['order_number'] }}<br>
                    @endforeach
                </td>
                <td class="col-qty">
                    {{ $item['total_qty'] }}
                </td>
            </tr>
            @endforeach
        @endif
    </tbody>
</table>

</body>
</html>
