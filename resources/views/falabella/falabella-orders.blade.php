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

                <!-- Filtros de Sincronización -->
                <div class="col-xl-5 col-lg-7">
                    <form method="POST" action="{{ route('plataformas.falabella.sync-orders') }}" class="d-flex gap-2 align-items-center bg-white p-1 rounded border shadow-sm">
                        @csrf
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Fecha:</span>
                            <input type="date" name="date" value="{{ $selectedDate }}" class="form-control border-0 bg-transparent" style="max-width: 130px;">
                        </div>

                        <div class="input-group input-group-sm border-start">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Estado:</span>
                            <select name="status" class="form-select border-0 bg-transparent" style="min-width: 110px;">
                                <option value="pending" {{ $selectedStatus == 'pending' ? 'selected' : '' }}>Pendientes</option>
                                <option value="ready_to_ship" {{ $selectedStatus == 'ready_to_ship' ? 'selected' : '' }}>Listos para enviar</option>
                                <option value="shipped" {{ $selectedStatus == 'shipped' ? 'selected' : '' }}>Enviados</option>
                                <option value="all" {{ $selectedStatus == 'all' ? 'selected' : '' }}>Todos</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm px-3 rounded shadow-sm flex-shrink-0">
                            <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Sincronizar</span>
                        </button>
                    </form>
                </div>

                <!-- Buscador de Órdenes -->
                <div class="col-xl-4 col-lg-5">
                    <div class="d-flex gap-2">
                        <form method="GET" action="{{ route('plataformas.falabella.orders') }}" class="input-group input-group-sm shadow-sm border rounded bg-white">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control border-0"
                                placeholder="N° de orden...">
                            <button type="submit" class="btn btn-dark btn-sm px-3">Buscar</button>
                            @if(request('search'))
                            <a href="{{ route('plataformas.falabella.orders') }}" class="btn btn-outline-secondary btn-sm border-0 d-flex align-items-center px-2" title="Limpiar">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            @endif
                        </form>

                        <button type="button"
                            onclick="generarEtiquetasFalabella('{{ route('plataformas.falabella.etiquetas-oficiales.pdf', ['date' => $selectedDate, 'status' => $selectedStatus]) }}')"
                            class="btn btn-success btn-sm px-3 shadow-sm flex-shrink-0 d-flex align-items-center" title="Descargar etiquetas A4">
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
                                <span class="badge rounded-pill px-3 bg-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'ready_to_ship' ? 'info' : 'success') }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    @foreach($order->items as $item)
                                    <div class="fw-bold text-primary">{{ $item->shop_sku ?: $item->falabella_sku }}</div>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-center">{{ $order->items_count }} uds</div>
                                <div class="small text-muted">
                                    @foreach($order->items->take(3) as $item)
                                    <div class="mb-1">
                                        <span class="d-block text-dark fw-bold" title="{{ $item->name }}">
                                            {{ $item->name }}
                                        </span>
                                        <span class="d-block text-muted  " style="font-size: 10px; max-width: 150px;" title="SKU Seller">{{ $item->seller_sku }}</span>

                                    </div>
                                    @endforeach
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

            // A4 dimensions in points
            const pageWidth = 595.28;
            const pageHeight = 841.89;

            let currentPage = null;
            let labelCount = 0;

            for (const base64 of data.labels) {
                try {
                    const labelPdf = await PDFDocument.load(base64);
                    const [embeddedPage] = await outPdf.embedPdf(labelPdf, [0]);

                    // Position logic for 2x2 layout
                    if (labelCount % 4 === 0) {
                        currentPage = outPdf.addPage([pageWidth, pageHeight]);
                    }

                    const indexOnPage = labelCount % 4;
                    const col = indexOnPage % 2;
                    const row = Math.floor(indexOnPage / 2);

                    const width = pageWidth / 2;
                    const height = pageHeight / 2;

                    const x = col * width;
                    const y = pageHeight - ((row + 1) * height);

                    currentPage.drawPage(embeddedPage, {
                        x,
                        y,
                        width,
                        height
                    });

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