@extends('layouts.app')

@section('title', 'Etiquetas de Envío - Falabella')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                {{-- Título --}}
                <div class="col-xl-3 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-tags-fill text-success me-2"></i>Etiquetas de Envío
                    </h1>
                    <p class="text-muted small mb-0">Falabella Seller Center</p>
                </div>

                {{-- Filtros --}}
                <div class="col-xl-5 col-lg-7">
                    <form method="GET" action="{{ route('plataformas.falabella.etiquetas') }}"
                          class="d-flex gap-2 align-items-center bg-white p-1 rounded border shadow-sm">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Fecha:</span>
                            <input type="date" name="date" value="{{ $selectedDate }}"
                                   class="form-control border-0 bg-transparent" style="max-width: 130px;">
                        </div>

                        <div class="input-group input-group-sm border-start">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Estado:</span>
                            <select name="status" class="form-select border-0 bg-transparent" style="min-width: 110px;">
                                <option value="pending"        {{ $selectedStatus == 'pending'        ? 'selected' : '' }}>Pendientes</option>
                                <option value="ready_to_ship"  {{ $selectedStatus == 'ready_to_ship'  ? 'selected' : '' }}>Listos para enviar</option>
                                <option value="shipped"        {{ $selectedStatus == 'shipped'        ? 'selected' : '' }}>Enviados</option>
                                <option value="all"            {{ $selectedStatus == 'all'            ? 'selected' : '' }}>Todos</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm px-3 rounded shadow-sm flex-shrink-0">
                            <i class="bi bi-funnel-fill"></i> <span class="d-none d-md-inline">Filtrar</span>
                        </button>
                    </form>
                </div>

                {{-- Acciones --}}
                <div class="col-xl-4 col-lg-5 d-flex gap-2 justify-content-end">
                    @if($orders->count() > 0)
                    <button type="button"
                            onclick="generarEtiquetasFalabella('{{ route('plataformas.falabella.etiquetas-oficiales.pdf', ['date' => $selectedDate, 'status' => $selectedStatus]) }}')"
                            class="btn btn-success px-4 shadow-sm d-flex align-items-center gap-2" id="btn-generar-etiquetas">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i>
                        <span>Generar PDF ({{ $orders->count() }} órdenes)</span>
                    </button>
                    @endif

                    <a href="{{ route('plataformas.falabella.etiquetas.pdf', ['date' => $selectedDate, 'status' => $selectedStatus]) }}"
                       target="_blank"
                       class="btn btn-outline-secondary px-3 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-printer-fill"></i>
                        <span class="d-none d-md-inline">Picking PDF</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Estadísticas rápidas --}}
    @if($orders->count() > 0)
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success">{{ $orders->count() }}</div>
                <div class="text-muted small">Órdenes para la fecha</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-primary">{{ $orders->sum('items_count') }}</div>
                <div class="text-muted small">Unidades totales</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-warning">S/ {{ number_format($orders->sum('price'), 2) }}</div>
                <div class="text-muted small">Monto total</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Tabla de órdenes --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">
                <i class="bi bi-list-ul me-2 text-muted"></i>Órdenes del {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
            </span>
            <span class="badge bg-secondary rounded-pill">{{ $orders->count() }} órdenes</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Orden</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Items</th>
                            <th>Precio</th>
                            <th>Promesa de Envío</th>
                            <th class="pe-4 text-end">Etiqueta</th>
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
                                    $statusColor = match($order->status) {
                                        'pending'       => 'warning',
                                        'ready_to_ship' => 'info',
                                        'shipped'       => 'success',
                                        default         => 'secondary',
                                    };
                                @endphp
                                <span class="badge rounded-pill px-3 bg-{{ $statusColor }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $order->items_count }} uds</div>
                                <div class="small text-muted">
                                    @foreach($order->items->take(2) as $item)
                                        <div>{{ Str::limit($item->name, 30) }}</div>
                                    @endforeach
                                    @if($order->items->count() > 2)
                                        <span class="text-primary">+{{ $order->items->count() - 2 }} más</span>
                                    @endif
                                </div>
                            </td>
                            <td>S/ {{ number_format($order->price, 2) }}</td>
                            <td>
                                @if($order->promised_shipping_time)
                                <span class="{{ $order->promised_shipping_time->isPast() && !in_array($order->status, ['shipped', 'delivered']) ? 'text-danger fw-bold' : '' }}">
                                    {{ $order->promised_shipping_time->format('d/m H:i') }}
                                </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <button type="button"
                                        onclick="generarEtiquetaOrden('{{ $order->order_id }}', '{{ $selectedDate }}', '{{ $selectedStatus }}')"
                                        class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm"
                                        title="Descargar etiqueta de esta orden">
                                    <i class="bi bi-tag-fill me-1"></i> Etiqueta
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                No hay órdenes para esta fecha y estado.
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
    // Generar etiquetas de TODAS las órdenes del día
    async function generarEtiquetasFalabella(url) {
        const btn = document.getElementById('btn-generar-etiquetas');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

        try {
            await descargarEtiquetasPDF(url);
        } catch (err) {
            alert('Error al generar etiquetas: ' + err.message);
            console.error(err);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    // Generar etiqueta de una orden individual
    async function generarEtiquetaOrden(orderId, date, status) {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        const url = '{{ route("plataformas.falabella.etiquetas-oficiales.pdf") }}?date=' + date + '&status=' + status + '&order_id=' + orderId;

        try {
            await descargarEtiquetasPDF('{{ route("plataformas.falabella.etiquetas-oficiales.pdf", ["date" => $selectedDate, "status" => $selectedStatus]) }}');
        } catch (err) {
            alert('Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    async function descargarEtiquetasPDF(url) {
        const response = await fetch(url);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error desconocido al obtener etiquetas');
        }

        if (!data.labels || data.labels.length === 0) {
            alert('No hay etiquetas disponibles para las órdenes seleccionadas.');
            return;
        }

        const { PDFDocument } = PDFLib;
        const outPdf = await PDFDocument.create();
        const pageWidth = 595.28;
        const pageHeight = 841.89;

        let currentPage = null;
        let labelCount = 0;

        for (const base64 of data.labels) {
            try {
                const labelPdf = await PDFDocument.load(base64);
                const [embeddedPage] = await outPdf.embedPdf(labelPdf, [0]);

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

                currentPage.drawPage(embeddedPage, { x, y, width, height });
                labelCount++;
            } catch (err) {
                console.error('Error al incrustar etiqueta:', err);
            }
        }

        const pdfBytes = await outPdf.save();
        const blob = new Blob([pdfBytes], { type: 'application/pdf' });
        const blobUrl = URL.createObjectURL(blob);
        window.open(blobUrl, '_blank');
    }
</script>
@endsection
