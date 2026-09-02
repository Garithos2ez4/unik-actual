@extends('layouts.app')
@section('title', 'Mercado Libre - Órdenes')

@section('content')
<div class="container-fluid">
    <br>
    <div class="row">
        <div class="col-md-12">
            <h2><i class="bi bi-cart-check-fill" style="color:#FFE600"></i> Mercado Libre
                <small class="text-secondary fs-6">— Órdenes y Despachos</small>
            </h2>
        </div>
    </div>
    <br>

    {{-- Filtros --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2">
            <select name="logistic_type" class="form-select form-select-sm">
                <option value="">Todos los despachos</option>
                <option value="self_service" {{ request('logistic_type') === 'self_service' ? 'selected' : '' }}>Flex</option>
                <option value="xd_drop_off" {{ request('logistic_type') === 'xd_drop_off' ? 'selected' : '' }}>Urbano (Drop-off)</option>
                <option value="fulfillment" {{ request('logistic_type') === 'fulfillment' ? 'selected' : '' }}>ME Full</option>
                <option value="custom" {{ request('logistic_type') === 'custom' ? 'selected' : '' }}>Acuerdo de entrega</option>
                <option value="drop_off" {{ request('logistic_type') === 'drop_off' ? 'selected' : '' }}>Punto de entrega</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Pagado</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}" placeholder="Desde">
        </div>
        <div class="col-md-2">
            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}" placeholder="Hasta">
        </div>
        <div class="col-md-1">
            <button class="btn btn-sm btn-dark w-100" type="submit">Filtrar</button>
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-sm btn-warning" id="btnSyncML" onclick="syncML()">
                <i class="bi bi-arrow-repeat"></i> Sincronizar
            </button>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="table-responsive shadow rounded-3">
        <table class="table table-sm table-hover mb-0">
            <thead class="bg-sistema-uno text-light">
                <tr>
                    <th>Orden ML</th>
                    <th>Comprador</th>
                    <th>Monto</th>
                    <th>Método de Despacho</th>
                    <th>Estado Envío</th>
                    <th>Tracking</th>
                    <th>Devolución</th>
                    <th>Fecha Creación</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><code>{{ $order->ml_order_id }}</code></td>
                    <td>{{ $order->buyer_name ?? $order->buyer_nickname ?? '—' }}</td>
                    <td>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td>
                    <td>
                        @php
                            $badges = [
                                'self_service' => 'primary',
                                'xd_drop_off'  => 'warning text-dark',
                                'fulfillment'  => 'success',
                                'custom'       => 'secondary',
                                'drop_off'     => 'info text-dark',
                            ];
                            $badge = $badges[$order->logistic_type] ?? 'light text-dark';
                        @endphp
                        @if($order->logistic_label)
                            <span class="badge bg-{{ $badge }}">{{ $order->logistic_label }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $order->shipping_status ?? '—' }}</td>
                    <td>{{ $order->tracking_number ?? '—' }}</td>
                    <td>
                        @if($order->return_status)
                            <span class="badge bg-danger">{{ $order->return_status }}</span>
                        @else
                            <span class="text-muted">Sin reclamo</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at_ml?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No hay órdenes sincronizadas aún. Usa el botón <strong>Sincronizar</strong>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>

    {{-- Toast de estado --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="syncToast" class="toast align-items-center text-bg-primary border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="syncToastMsg">Sincronizando...</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function syncML() {
    const btn = document.getElementById('btnSyncML');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sincronizando...';

    fetch('{{ route("plataformas.ml.sync-orders") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        pollSyncStatus();
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sincronizar';
    });
}

function pollSyncStatus() {
    const interval = setInterval(() => {
        fetch('{{ route("plataformas.ml.sync-status") }}')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'completed' || data.status === 'idle') {
                clearInterval(interval);
                document.getElementById('btnSyncML').disabled = false;
                document.getElementById('btnSyncML').innerHTML = '<i class="bi bi-check-circle"></i> Listo';
                setTimeout(() => location.reload(), 1500);
            }
        });
    }, 3000);
}
</script>
@endpush
