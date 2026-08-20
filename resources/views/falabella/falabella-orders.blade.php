@extends('layouts.app')

@section('title', 'Ordenes Falabella')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <!-- Título -->
                <div class="col-xl-3 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-shop text-primary me-2"></i>Ordenes Falabella
                    </h1>
                </div>

                <!-- Barra de Herramientas (Filtros + Buscador + Etiquetas) -->
                <div class="col-xl-9 col-lg-12">
                    <div class="d-flex flex-wrap flex-xl-nowrap gap-2 justify-content-xl-center align-items-center w-100">

                        <!-- Sincronizar Form -->
                        <form method="POST" action="{{ route('plataformas.falabella.sync-orders') }}" class="m-0">
                            @csrf
                            <div class="input-group shadow-sm flex-nowrap">
                                <span class="input-group-text bg-white fw-bold text-muted border-end-0">Fecha:</span>
                                <input type="date" name="date" value="{{ $selectedDate }}" class="form-control border-start-0 bg-white ps-0">

                                <span class="input-group-text bg-white fw-bold text-muted border-start border-end-0">Estado:</span>
                                <select name="status" class="form-select border-start-0 bg-white ps-0" style="min-width: 140px;">
                                    <option value="pending" {{ $selectedStatus == 'pending' ? 'selected' : '' }}>Pendientes</option>
                                    <option value="ready_to_ship" {{ $selectedStatus == 'ready_to_ship' ? 'selected' : '' }}>Listos para enviar</option>
                                    <option value="shipped" {{ $selectedStatus == 'shipped' ? 'selected' : '' }}>Enviados</option>
                                    <option value="all" {{ $selectedStatus == 'all' ? 'selected' : '' }}>Todos</option>
                                </select>
                                <button type="submit" class="btn btn-primary px-3 fw-bold">
                                    <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Sincronizar</span>
                                </button>
                            </div>
                        </form>

                        <!-- Buscador Form -->
                        <form method="GET" action="{{ route('plataformas.falabella.orders') }}" class="input-group shadow-sm flex-nowrap" style="max-width: 300px;">
                            <span class="input-group-text bg-white text-muted border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 bg-white ps-0" placeholder="N° de orden...">
                            @if(request('search'))
                            <a href="{{ route('plataformas.falabella.orders') }}" class="btn btn-outline-secondary border-start-0 border-end-0 bg-white text-danger d-flex align-items-center px-2" title="Limpiar">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            @endif
                            <button type="submit" class="btn btn-dark px-3 fw-bold">Buscar</button>
                        </form>

                        <!-- Etiquetas -->
                        <div class="input-group shadow-sm flex-nowrap" style="width: auto;">
                            <select id="formatoEtiqueta" class="form-select border-success text-success bg-white fw-bold" style="min-width: 130px;" title="Formato de Etiqueta">
                                <option value="a4_100">A4 - 4 por hoja</option>
                                <option value="a4_1">A4 - 1 por hoja</option>
                                <option value="termica">Térmica 10x15</option>
                            </select>
                            <button type="button" onclick="generarEtiquetasFalabella('{{ route('plataformas.falabella.etiquetas-oficiales.pdf', ['date' => $selectedDate, 'status' => $selectedStatus]) }}')" class="btn btn-success px-3 fw-bold" title="Descargar etiquetas">
                                <i class="bi bi-tags-fill me-1"></i> <span class="d-none d-md-inline">Etiquetas</span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Orden</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>SKU SHOP</th>
                                <th>Items</th>
                                <th>Precio</th>
                                <th>Promesa de Envío</th>
                                <th class="pe-4 text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $order->order_number }}</div>
                                    <div class="small text-muted">ID: {{ $order->order_id }}</div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $order->customer_name }}</div>
                                    <div class="small text-muted">{{ $order->shipping_city }}</div>
                                </td>
                                <td>
                                    @php
                                    $orderStatus = $order->status;
                                    if ((empty($orderStatus) || trim($orderStatus) === '-') && $order->items && $order->items->count() > 0) {
                                    $orderStatus = $order->items->first()->status;
                                    }

                                    $statusMap = [
                                    'pending' => 'Pendiente',
                                    'ready_to_ship' => 'Listo para Enviar',
                                    'shipped' => 'Enviado',
                                    'delivered' => 'Entregado',
                                    'canceled' => 'Cancelado',
                                    'returned' => 'Devuelto',
                                    'failed' => 'Fallido'
                                    ];
                                    $statusColor = [
                                    'pending' => 'warning',
                                    'ready_to_ship' => 'info',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'canceled' => 'danger',
                                    'returned' => 'danger',
                                    'failed' => 'danger'
                                    ];
                                    $displayStatus = $statusMap[$orderStatus] ?? ucfirst($orderStatus);
                                    $color = $statusColor[$orderStatus] ?? 'secondary';
                                    @endphp
                                    <span class="badge rounded-pill px-3 bg-{{ $color }}">
                                        {{ $displayStatus }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        @forelse($order->items as $item)
                                        <div class="fw-bold text-primary">{{ $item->shop_sku ?: $item->falabella_sku }}</div>
                                        @empty
                                        @if($order->items_count > 0)
                                        <span class="text-danger" style="font-size: 10px;">Pendiente API</span>
                                        @endif
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-center">{{ $order->items_count }} uds</div>
                                    <div class="small text-muted">
                                        @forelse($order->items->take(3) as $item)
                                        <div class="mb-1">
                                            <span class="d-block text-dark fw-bold" title="{{ $item->name }}">
                                                {{ $item->name }}
                                            </span>
                                            <span class="d-block text-muted  " style="font-size: 10px; max-width: 150px;" title="SKU Seller">{{ $item->seller_sku }}</span>

                                        </div>
                                        @empty
                                        @if($order->items_count > 0)
                                        <div class="text-center mt-1">
                                            <a href="{{ route('plataformas.falabella.orders', ['search' => $order->order_number ?? $order->order_id]) }}" class="btn btn-sm btn-outline-warning py-0 px-2" style="font-size: 11px;" title="La API de Falabella falló al descargar los items. Clic para intentar forzar carga.">
                                                <i class="bi bi-cloud-download"></i> Cargar Items
                                            </a>
                                        </div>
                                        @endif
                                        @endforelse
                                        @if($order->items->count() > 3)
                                        <span class="text-primary" style="font-size: 10px;">+{{ $order->items->count() - 3 }} más</span>
                                        @endif
                                    </div>
                                </td>
                                <td>S/ {{ number_format($order->price, 2) }}</td>
                                <td>
                                    @if($order->promised_shipping_time)
                                    <div class="{{ $order->promised_shipping_time->isPast() && !in_array($order->status, ['shipped', 'delivered']) ? 'text-danger fw-bold' : '' }}">
                                        {{ $order->promised_shipping_time->format('d/m H:i') }}
                                    </div>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('plataformas.falabella.order-details', ['order_id' => $order->order_id]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-eye-fill me-1"></i> Ver detalles
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                    No se encontraron órdenes sincronizadas para esta fecha y estado.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>
    <script>
        async function generarEtiquetasFalabella(url) {
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error desconocido al obtener etiquetas');
                }

                if (!data.labels || data.labels.length === 0) {
                    alert('No hay etiquetas disponibles para las órdenes seleccionadas.');
                    return;
                }

                const {
                    PDFDocument,
                    degrees
                } = PDFLib;
                const outPdf = await PDFDocument.create();

                const formatSelect = document.getElementById('formatoEtiqueta');
                const formato = formatSelect ? formatSelect.value : 'a4_100';

                let currentPage = null;
                let labelCount = 0;

                for (const base64 of data.labels) {
                    try {
                        const labelPdf = await PDFDocument.load(base64);

                        if (formato === 'a4_1') {
                            const [copiedPage] = await outPdf.copyPages(labelPdf, [0]);
                            outPdf.addPage(copiedPage);
                            labelCount++;
                            continue;
                        }

                        const sourcePage = labelPdf.getPages()[0];

                        // La API de Falabella envía una hoja A4 completa, pero la etiqueta solo ocupa la esquina superior izquierda (10x15cm).
                        // Recortamos (Crop) la hoja original a solo el área de la etiqueta para evitar los enormes espacios en blanco.
                        const spHeight = sourcePage.getHeight();
                        const cropWidth = 283.46; // 100mm
                        const cropHeight = 425.20; // 150mm
                        // x, y (desde abajo), width, height
                        sourcePage.setCropBox(0, spHeight - cropHeight, cropWidth, cropHeight);

                        const [embeddedPage] = await outPdf.embedPdf(labelPdf, [0]);

                        if (formato === 'termica') {
                            // 10x15cm (Aprox 4x6 pulgadas = 288x432 puntos)
                            const pageWidth = 283.46;
                            const pageHeight = 425.20;

                            currentPage = outPdf.addPage([pageWidth, pageHeight]);

                            const scaleFactor = Math.min(pageWidth / embeddedPage.width, pageHeight / embeddedPage.height);

                            currentPage.drawPage(embeddedPage, {
                                x: (pageWidth - embeddedPage.width * scaleFactor) / 2,
                                y: (pageHeight - embeddedPage.height * scaleFactor) / 2,
                                width: embeddedPage.width * scaleFactor,
                                height: embeddedPage.height * scaleFactor
                            });
                        } else {
                            // Formato A4
                            const pageWidth = 595.28;
                            const pageHeight = 841.89;

                            if (labelCount % 4 === 0) {
                                currentPage = outPdf.addPage([pageWidth, pageHeight]);
                            }

                            const indexOnPage = labelCount % 4;
                            const col = indexOnPage % 2;
                            const row = Math.floor(indexOnPage / 2);

                            const quadWidth = pageWidth / 2;
                            const quadHeight = pageHeight / 2;

                            const baseScale = Math.min(quadWidth / embeddedPage.width, quadHeight / embeddedPage.height);
                            // Aplicamos un margen automático del 6% (94% scale) para que no toquen los bordes físicos de la hoja
                            const safeScale = 0.94; 
                            const finalScale = baseScale * safeScale;

                            const drawWidth = embeddedPage.width * finalScale;
                            const drawHeight = embeddedPage.height * finalScale;

                            // Esto centra la etiqueta perfectamente dentro de su cuadrante
                            const offsetX = (quadWidth - drawWidth) / 2;
                            const offsetY = (quadHeight - drawHeight) / 2;

                            const x = (col * quadWidth) + offsetX;
                            const y = (pageHeight - ((row + 1) * quadHeight)) + offsetY;

                            currentPage.drawPage(embeddedPage, {
                                x,
                                y,
                                width: drawWidth,
                                height: drawHeight
                            });
                        }

                        labelCount++;
                    } catch (err) {
                        console.error('Error al incrustar etiqueta:', err);
                    }
                }

                const pdfBytes = await outPdf.save();
                const blob = new Blob([pdfBytes], {
                    type: 'application/pdf'
                });
                const blobUrl = URL.createObjectURL(blob);
                window.open(blobUrl, '_blank');

            } catch (err) {
                alert('Error al generar etiquetas: ' + err.message);
                console.error(err);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    </script>
    @endsection