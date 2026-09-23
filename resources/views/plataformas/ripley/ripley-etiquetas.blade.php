@extends('layouts.app')

@section('title', 'Etiquetas de Envio - Ripley')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-xl-3 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-tags-fill text-warning me-2"></i>Etiquetas de Envio
                    </h1>
                    <p class="text-muted small mb-0">Ripley Seller Center</p>
                </div>
                <div class="col-xl-5 col-lg-7">
                    <form method="GET" action="{{ route('plataformas.ripley.etiquetas') }}"
                          class="d-flex gap-2 align-items-center bg-white p-1 rounded border shadow-sm">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Fecha promesa:</span>
                            <input type="date" name="date" value="{{ $selectedDate }}"
                                   class="form-control border-0 bg-transparent" style="max-width: 140px;">
                        </div>
                        <div class="input-group input-group-sm border-start">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Estado:</span>
                            <select name="status" class="form-select border-0 bg-transparent" style="min-width: 120px;">
                                <option value="pending"  {{ $selectedStatus == 'pending'  ? 'selected' : '' }}>Pendientes</option>
                                <option value="shipped"  {{ $selectedStatus == 'shipped'  ? 'selected' : '' }}>Enviados</option>
                                <option value="all"      {{ $selectedStatus == 'all'      ? 'selected' : '' }}>Todos</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm px-3 rounded shadow-sm flex-shrink-0">
                            <i class="bi bi-funnel-fill"></i> Filtrar
                        </button>
                    </form>
                </div>
                <div class="col-xl-4 col-lg-5 d-flex gap-2 justify-content-end">
                    @if($orders->count() > 0)
                    <button type="button" id="btn-generar-etiquetas" onclick="generarEtiquetasRipley('{{ route('plataformas.ripley.etiquetas.descargar', ['date' => $selectedDate, 'status' => $selectedStatus]) }}')" 
                       class="btn btn-success px-3 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Descargar Albaranes</span>
                    </button>
                    <a href="https://ripleyperu-prod.mirakl.net/" target="_blank"
                       class="btn btn-warning px-3 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>Seller Center</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($orders->count() > 0)
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-warning">{{ $orders->count() }}</div>
                <div class="text-muted small">Ordenes para la fecha</div>
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
                <div class="fs-2 fw-bold text-success">S/ {{ number_format($orders->sum('price'), 2) }}</div>
                <div class="text-muted small">Monto total</div>
            </div>
        </div>
    </div>
    @endif

    <div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-3 mb-4">
        <i class="bi bi-info-circle-fill fs-5 mt-1 text-info"></i>
        <div>
            <p class="mb-0 small">
                Puedes descargar todas las etiquetas de la fecha seleccionada haciendo clic en el botón <strong>Descargar Etiquetas</strong>.<br>
                También puedes ingresar al <a href="https://ripleyperu-prod.mirakl.net/" target="_blank" class="fw-bold">Seller Center de Ripley</a> para agendar el retiro o gestionar las órdenes individualmente. Asegurate de agendar tus pedidos <strong>antes de las 9:59 pm</strong>.
            </p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">
                <i class="bi bi-list-ul me-2 text-muted"></i>Ordenes con promesa {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
            </span>
            <span class="badge bg-secondary rounded-pill">{{ $orders->count() }} ordenes</span>
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
                            <th>Promesa Envio</th>
                            <th>Tracking</th>
                            <th class="pe-4 text-end">Accion</th>
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
                                        'pending', 'waiting_debit_payment' => 'warning',
                                        'ready_to_ship'                    => 'info',
                                        'shipped'                          => 'success',
                                        default                            => 'secondary',
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
                                        <span class="text-primary">+{{ $order->items->count() - 2 }} mas</span>
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
                            <td>
                                @php $tracking = $order->items->first()?->tracking_code; @endphp
                                @if($tracking)
                                    <span class="badge bg-light text-dark border small">{{ $tracking }}</span>
                                @else
                                    <span class="text-muted small">Sin tracking</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <a href="https://ripleyperu-prod.mirakl.net/" target="_blank"
                                   class="btn btn-outline-warning btn-sm rounded-pill px-3 shadow-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Ir a SellerCenter
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                No hay ordenes con promesa de envio para esta fecha.
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
    async function generarEtiquetasRipley(url) {
        const btn = document.getElementById('btn-generar-etiquetas');
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

            const { PDFDocument } = PDFLib;
            const outPdf = await PDFDocument.create();

            for (const base64 of data.labels) {
                try {
                    const labelPdf = await PDFDocument.load(base64);
                    // Copiar todas las páginas de este PDF
                    const pages = await outPdf.copyPages(labelPdf, labelPdf.getPageIndices());
                    for (const page of pages) {
                        outPdf.addPage(page);
                    }
                } catch (err) {
                    console.error('Error al incrustar etiqueta:', err);
                }
            }

            const pdfBytes = await outPdf.save();
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
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
