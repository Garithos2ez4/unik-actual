<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        th, td {
            border: 0.5px solid #000;
            font-size: 10px;
            text-align: center;
            padding: 5px;
        }
        th {
            background-color: #ffffff;
        }
        .header{
            background-color: #dddddd;
        }
        .index{
            width: 20px;
        }
        .qty-column {
            background-color: #f5f5f5;
            width: 60px;
        }
        .fila-title {
            background-color: #333333;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 5px;
            margin-top: 20px;
        }
        .info {
            font-size: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h3>{{ $title }}</h3>
    <div class="info">
        <strong>Almacén:</strong> {{ $estante->Almacen->descripcion ?? 'N/A' }}<br>
        <strong>Fecha de Reporte:</strong> {{ $fecha }}
    </div>

    @foreach($filas as $fila)
        <div class="fila-title">
            Fila {{ $fila->fila_estante }}
        </div>
        @if(count($fila->productos_agrupados) > 0)
            <table>
                <thead>
                    <tr>
                        <th class="index header">#</th>
                        <th class="header">Código</th>
                        <th class="header">Modelo</th>
                        <th class="header">Estado</th>
                        <th class="header qty-column">Stock en Fila</th>
                    </tr>
                </thead>
                <tbody>
                    @php $count = 1; $totalFila = 0; @endphp
                    @foreach ($fila->productos_agrupados as $item)
                        <tr>
                            <td class="index">{{ $count++ }}</td>
                            <td>{{ $item['producto']->codigoProducto ?? 'ELIMINADO' }}</td>
                            <td>{{ $item['producto']->modelo ?? 'N/A' }}</td>
                            <td>{{ $item['estado'] }}</td>
                            <td class="qty-column">{{ $item['cantidad'] }}</td>
                        </tr>
                        @php $totalFila += $item['cantidad']; @endphp
                    @endforeach
                    <tr>
                        <td colspan="4" class="header" style="text-align: right; padding-right: 10px;">Total en Fila {{ $fila->fila_estante }}</td>
                        <td class="header qty-column" style="font-weight: bold;">{{ $totalFila }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="font-size: 10px; font-style: italic; color: #555;">No hay productos registrados en esta fila.</p>
        @endif
    @endforeach

</body>
</html>
