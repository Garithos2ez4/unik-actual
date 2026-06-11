@extends('layouts.app')

@section('title', 'Detalle de Orden #' . $order->order_number)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Detalles de Orden #{{ $order->order_number }}</h1>
            <p class="text-muted small mb-0">Falabella Seller Center - Unike Store</p>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom-0">
            <div class="row align-items-center">
                <div class="col text-muted small text-uppercase fw-bold" style="max-width: 100px;">Envío</div>
                <div class="col text-muted small text-uppercase fw-bold" style="max-width: 250px;">Código de seguimiento</div>
                <div class="col text-muted small text-uppercase fw-bold text-center" style="max-width: 150px;">Falabella SKU</div>
                <div class="col text-muted small text-uppercase fw-bold">Producto</div>
                <div class="col text-muted small text-uppercase fw-bold text-end" style="max-width: 250px;">Información de despacho</div>
            </div>
        </div>
        <div class="card-body p-0">
            @foreach($order->items as $index => $item)
            <div class="border-top py-4 px-3">
                <div class="row align-items-start">
                    <!-- Columna Envío -->
                    <div class="col fw-bold text-dark" style="max-width: 100px;">
                        Envío {{ $index + 1 }}
                    </div>

                    <!-- Columna Tracking -->
                    <div class="col text-dark font-monospace" style="max-width: 250px;">
                        {{ $item->tracking_code ?: 'Pendiente' }}
                    </div>

                    <!-- Columna Falabella SKU -->
                    <div class="col text-center" style="max-width: 150px;">
                        @php
                            $pub = \App\Models\Publicacion::where('titulo', $item->name)->first();
                            $display_sku = $pub ? $pub->sku : ($item->falabella_sku ?: $item->shop_sku);
                            // Asegurar que solo sea numérico
                            if (!is_numeric($display_sku)) {
                                $display_sku = '-';
                            }
                        @endphp
                        <span class="badge bg-primary px-3 py-2 fw-bold" style="font-size: 0.9rem;">
                            {{ $display_sku ?: '-' }}
                        </span>
                    </div>

                    <!-- Columna Producto -->
                    <div class="col">
                        <div class="d-flex">
                            @php
                            $payload = is_array($item->payload) ? $item->payload : json_decode($item->payload, true);
                            $image = $payload['Image'] ?? $payload['image'] ?? null;
                            @endphp
                            <div class="me-3" style="width: 60px; height: 60px; flex-shrink: 0;">
                                @if($image)
                                <img src="{{ $image }}" class="img-fluid rounded border shadow-sm" style="object-fit: contain; width: 100%; height: 100%;">
                                @else
                                <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted h-100 w-100">
                                    <i class="bi bi-image"></i>
                                </div>
                                @endif
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-1">{{ $item->name }}</div>
                                <div class="d-flex align-items-center gap-3 mt-1">
                                    <div class="small d-flex align-items-center text-muted">
                                        <i class="bi bi-qr-code-scan me-1" style="font-size: 14px;"></i>
                                        <span class="opacity-75 me-1">SKU Seller:</span>
                                        <span class="fw-bold text-secondary">{{ $item->seller_sku }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Cantidad -->
                    <div class="col-auto text-center px-4">
                        <span class="badge bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 25px; height: 25px;">
                            {{ $item->quantity }}
                        </span>
                    </div>

                    <!-- Columna Despacho -->
                    <div class="col text-end small" style="max-width: 250px;">
                        <div class="fw-bold text-dark mb-1">Dropshipping</div>
                        <div class="text-muted italic">Operador logístico: falabella</div>
                        <div class="text-muted italic">Paquete: {{ $item->package_id ?: '-' }}</div>
                        <div class="text-muted italic mt-1">
                            Límite de despacho:
                            <span class="fw-bold {{ $order->promised_shipping_time && $order->promised_shipping_time->isPast() ? 'text-danger' : 'text-dark' }}">
                                {{ $order->promised_shipping_time ? $order->promised_shipping_time->format('M d, Y H:i') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Información del Cliente -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-person-fill me-2 text-primary"></i> Información del Cliente</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 140px;">Nombre:</td>
                            <td class="fw-bold text-dark">{{ $order->customer_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email:</td>
                            <td>{{ $order->customer_email ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ciudad:</td>
                            <td>{{ $order->shipping_city }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dirección:</td>
                            <td>{{ $order->shipping_address }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cash-stack me-2 text-success"></i> Detalles de Pago</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 140px;">Método:</td>
                            <td>{{ $order->payment_method ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Monto Total:</td>
                            <td class="h5 fw-bold text-success">S/ {{ number_format($order->price, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tipo Envío:</td>
                            <td><span class="badge bg-light text-dark border">{{ $order->shipping_type }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .italic {
        font-style: italic;
    }

    .font-monospace {
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 0.5px;
    }
</style>
@endsection