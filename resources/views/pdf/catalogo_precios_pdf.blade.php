<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catalogo de Precios - {{ $categoria }}</title>
    <style>
        @page {
            margin: 28px 30px 45px 30px;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1b2838;
            font-size: 10px;
            line-height: 1.3;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border-bottom: 2px solid #00b1b9;
            padding-bottom: 10px;
        }

        .brand-name {
            font-size: 16px;
            font-weight: bold;
            color: #0d1b2a;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .brand-tagline {
            font-size: 9px;
            color: #00b1b9;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #0d1b2a;
            text-align: right;
            margin: 0;
            text-transform: uppercase;
        }

        .meta-text {
            font-size: 9px;
            color: #555555;
            text-align: right;
            margin-top: 3px;
        }

        .badge-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f1f8f9;
            border-left: 4px solid #00b1b9;
        }

        .badge-info-table td {
            padding: 6px 10px;
            font-size: 9.5px;
            color: #0d1b2a;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .items-table th {
            background-color: #0d1b2a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 7px 8px;
            border: 0.5px solid #0d1b2a;
            letter-spacing: 0.3px;
        }

        .items-table td {
            padding: 6px 8px;
            font-size: 9.5px;
            border-bottom: 0.5px solid #e2e8f0;
            border-left: 0.5px solid #f1f5f9;
            border-right: 0.5px solid #f1f5f9;
            vertical-align: middle;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .col-num {
            width: 5%;
            text-align: center;
            color: #64748b;
        }

        .col-nombre {
            width: 53%;
            text-align: left;
            font-weight: 500;
        }

        .col-modelo {
            width: 18%;
            text-align: center;
            color: #334155;
            font-family: monospace;
            font-size: 9px;
        }

        .col-stock {
            width: 9%;
            text-align: center;
            font-weight: bold;
        }

        .stock-badge {
            display: inline-block;
            background-color: #e6f9f8;
            color: #007c82;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }

        .col-precio {
            width: 15%;
            text-align: right;
            font-weight: bold;
            color: #0d1b2a;
            font-size: 10px;
        }

        .disclaimer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 0.5px solid #cbd5e1;
            font-size: 7.5px;
            color: #64748b;
            text-align: center;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <h1 class="brand-name">UNIKE TECHNOLOGY</h1>
                <div class="brand-tagline">Tienda Oficial de Tecnologia</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <h2 class="doc-title">Catalogo de Precios</h2>
                <div class="meta-text">Emision: {{ $fechaEmision }}</div>
            </td>
        </tr>
    </table>

    <table class="badge-info-table">
        <tr>
            <td>
                <strong>Categoria:</strong> {{ $categoria }}
                @if(!empty($grupo))
                    &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Grupo / Linea:</strong> {{ $grupo }}
                @endif
                &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Marca:</strong> {{ $marca }}
                &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Productos Disponibles:</strong> {{ $totalProductos }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-nombre">Nombre del Producto</th>
                <th class="col-modelo">Modelo</th>
                <th class="col-stock">Stock</th>
                <th class="col-precio">Precio Web (S/.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($productos as $index => $item)
                <tr>
                    <td class="col-num">{{ $index + 1 }}</td>
                    <td class="col-nombre">{{ $item['nombre'] }}</td>
                    <td class="col-modelo">{{ $item['modelo'] }}</td>
                    <td class="col-stock">
                        <span class="stock-badge">{{ $item['stock'] }}</span>
                    </td>
                    <td class="col-precio">{{ $item['precio'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">
                        No se encontraron productos disponibles con stock en esta seleccion.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="disclaimer">
        * Precios y stock referenciales vigentes a la fecha y hora de emision, sujetos a variacion sin previo aviso.<br>
        * Precios expresados en Soles (S/.) con I.G.V. incluido segun lo publicado en Web Unik. Documento oficial protegido contra edicion.
    </div>

    <script type="php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Helvetica", "normal");
            $size = 8;
            $text = "Pagina {PAGE_NUM} de {PAGE_COUNT}";
            $pdf->page_text(520, 810, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>

</body>
</html>
