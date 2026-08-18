@extends('layouts.app')

@section('title', 'Ordenes Ripley')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-xl-3 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-receipt text-warning me-2"></i>Ordenes Ripley
                    </h1>
                    <p class="text-muted small mb-0">Historial sincronizado desde Mirakl</p>
                </div>
                <div class="col-xl-5 col-lg-7">
                    <form method="GET" action="{{ route('plataformas.ripley.orders') }}"
                          class="d-flex gap-2 align-items-center bg-white p-1 rounded border shadow-sm">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Fecha orden:</span>
                            <input type="date" name="date" value="{{ $selectedDate }}"
                                   class="form-control border-0 bg-transparent" style="max-width: 140px;">
                        </div>
                        <div class="input-group input-group-sm border-start">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Estado:</span>
                            <select name="status" class="form-select border-0 bg-transparent" style="min-width: 120px;">
                                <option value=""       {{ $selectedStatus == ''      ? 'selected' : '' }}>Todos</option>
                                <option value="pending" {{ $selectedStatus == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="shipped" {{ $selectedStatus == 'shipped' ? 'selected' : '' }}>Shipped</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm px-3 rounded shadow-sm flex-shrink-0">
                            <i class="bi bi-funnel-fill"></i> Filtrar
                        </button>
                    </form>
                </div>
                <div class="col-xl-4 col-lg-5 d-flex gap-2 justify-content-end">
                    <a href="{{ route('plataformas.ripley.etiquetas') }}" class="btn btn-outline-warning shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-tags-fill"></i> Ver Etiquetas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">
                <i class="bi bi-list-ul me-2 text-muted"></i>Ordenes del {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
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
                            <th>Total</th>
                            <th>Promesa Envio</th>
                            <th>Ciudad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $order->order_number }}</div>
                                <div class="small text-muted">{{ \Carbon\Carbon::parse($order->created_at_ripley)->format('d/m H:i') }}</div>
                            </td>
                            <td>
                                <div class="text-dark">{{ $order->customer_name }}</div>
                                <div class="small text-muted">{{ $order->shipping_address }}</div>
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
                                        <div>{{ Str::limit($item->name, 35) }}</div>
                                    @endforeach
                                    @if($order->items->count() > 2)
                                        <span class="text-primary">+{{ $order->items->count() - 2 }} mas</span>
                                    @endif
                                </div>
                            </td>
                            <td class="fw-semibold">S/ {{ number_format($order->price, 2) }}</td>
                            <td>
                                @if($order->promised_shipping_time)
                                <span class="{{ $order->promised_shipping_time->isPast() && !in_array($order->status, ['shipped', 'delivered']) ? 'text-danger fw-bold' : 'text-muted small' }}">
                                    {{ $order->promised_shipping_time->format('d/m/Y') }}
                                </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $order->shipping_city ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                No hay ordenes para esta fecha.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
