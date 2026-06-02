<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista Consolidada de Inventario - Envíos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background-color: #fff;
            }

            .table {
                border-color: #dee2e6 !important;
            }
        }

        .img-producto {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .row-producto {
            page-break-inside: avoid;
        }
    </style>
</head>

<body class="bg-light pt-4 pb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 no-print">
            <div>
                <h2>Lista Consolidada de Inventario (Picking General)</h2>
                <p class="text-muted mb-0">Fecha: {{ $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : 'Selección Específica' }} | Total Envíos a Despachar: {{ $envios->count() }}</p>
            </div>
            
            @php
                // Determinar parámetros para las rutas (por fecha o por IDs específicos)
                $params = $fecha ? ['fecha' => $fecha] : ['ids' => $envios->pluck('idEnvioProvincia')->join(',')];
            @endphp

            <div class="d-flex gap-2">
                <a href="{{ route('envios.pdf', $params) }}" target="_blank" class="btn btn-danger d-flex align-items-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-pdf-fill me-2" viewBox="0 0 16 16">
                        <path d="M5.523 12.424c.14-.082.293-.162.459-.238a7.878 7.878 0 0 1-.45.606c-.28.337-.498.516-.635.572a.266.266 0 0 1-.035.012.282.282 0 0 1-.026-.044c-.056-.11-.054-.216.04-.36.106-.165.319-.354.647-.548zm2.455-1.647c-.119.025-.237.05-.356.078a21.148 21.148 0 0 0 .5-1.05 12.045 12.045 0 0 0 .51.858c-.217.032-.436.07-.654.114zm2.525.939a3.881 3.881 0 0 1-.435-.41c.228.005.434.022.568.046.256.05.397.146.397.288 0 .126-.116.234-.28.288a2.31 2.31 0 0 1-.25.044zM5.432 7.714c.08-.164.21-.416.34-.648.16-.28.314-.52.41-.62.06-.06.12-.1.136-.098.02.002.04.03.04.09 0 .15-.05.41-.12.65-.08.28-.19.55-.3.8-.13.27-.27.53-.4.77-.1.18-.21.36-.31.53-.1.17-.2.35-.3.53a43.91 43.91 0 0 1-.28.46c-.03-.1-.05-.2-.07-.31a2.82 2.82 0 0 1-.04-.26c0-.26.06-.52.12-.76.06-.23.14-.45.22-.65.08-.2.15-.38.21-.51zM9.263 1.5H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.263 1.5zM8.5 2.5V5h2.5L8.5 2.5z"/>
                    </svg>
                    Lista PDF
                </a>

                <a href="{{ route('envios.etiquetas', $params) }}" target="_blank" class="btn btn-success d-flex align-items-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tags-fill me-2" viewBox="0 0 16 16">
                        <path d="M2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586V2zm3.5 4a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                        <path d="M1.293 7.793A1 1 0 0 1 1 7.086V2a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l.043-.043-7.457-7.457z"/>
                    </svg>
                    Etiquetas
                </a>

                <button class="btn btn-primary d-flex align-items-center shadow-sm" onclick="window.print()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-2" viewBox="0 0 16 16">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z" />
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z" />
                    </svg>
                    Imprimir Lista
                </button>
            </div>
        </div>

        <div class="d-none d-print-block mb-4 border-bottom pb-2">
            <h3 class="mb-1">Lista Consolidada de Inventario</h3>
            <p class="text-muted mb-0">Fecha: {{ $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : 'Selección Específica' }} | Total Envíos: {{ $envios->count() }}</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 120px;">Imagen</th>
                            <th>Modelo</th>
                            <th>Nombre del Producto</th>
                            <th class="text-center" style="width: 180px;">Cantidad a Extraer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $inventarioGeneral = [];
                        foreach($envios as $envio) {
                            foreach($envio->Productos as $item) {
                                $idProd = $item->idProducto;
                                if (!isset($inventarioGeneral[$idProd])) {
                                    $inventarioGeneral[$idProd] = [
                                        'producto' => $item->Producto,
                                        'cantidad' => 0
                                    ];
                                }
                                $inventarioGeneral[$idProd]['cantidad'] += $item->cantidad;
                            }
                        }
                        
                        // Ordenar alfabéticamente por modelo para facilitar la búsqueda en almacén
                        usort($inventarioGeneral, function($a, $b) {
                            $modeloA = $a['producto']->modelo ?? '';
                            $modeloB = $b['producto']->modelo ?? '';
                            return strcmp($modeloA, $modeloB);
                        });
                        @endphp

                        @forelse($inventarioGeneral as $data)
                        @php
                        $producto = $data['producto'];
                        $imagen = $producto && $producto->imagenProducto1 ? asset('storage/'.$producto->imagenProducto1) : asset('storage/noimagen.webp');
                        @endphp
                        <tr class="row-producto">
                            <td class="text-center py-3">
                                <img src="{{ $imagen }}" alt="Img" class="img-producto border shadow-sm">
                            </td>
                            <td>
                                <strong class="fs-4 text-primary">{{ $producto->modelo ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                <span class="fs-5 text-secondary">{{ $producto->nombreProducto ?? 'Producto Desconocido' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success" style="font-size: 1.5rem; padding: 10px 20px;">{{ $data['cantidad'] }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5 fs-5">No hay productos registrados en los envíos seleccionados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>