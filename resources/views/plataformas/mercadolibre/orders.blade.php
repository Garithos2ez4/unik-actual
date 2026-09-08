@extends('layouts.app')
@section('title', 'Mercado Libre - Órdenes')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                {{-- Título --}}
                <div class="col-xl-3 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-cart-check-fill me-2" style="color:#FFE600"></i>Ordenes Mercado Libre
                    </h1>
                </div>

                {{-- Barra de Herramientas --}}
                <div class="col-xl-9 col-lg-12">
                    <div class="d-flex flex-wrap flex-xl-nowrap gap-2 justify-content-xl-end align-items-center w-100">
                        {{-- Filtro Estado Envío y Buscador --}}
                        <form method="GET" class="d-flex flex-wrap flex-xl-nowrap gap-2 m-0">
                            {{-- Filtro Estado Envío --}}
                            <div class="input-group shadow-sm flex-nowrap" style="max-width: 240px;">
                                <span class="input-group-text bg-white fw-bold text-muted border-end-0">
                                    <i class="bi bi-box-seam"></i>
                                </span>
                                <select name="shipping_status" class="form-select border-start-0 bg-white ps-0">
                                    <option value="pending_only" {{ request('shipping_status', 'pending_only') === 'pending_only' ? 'selected' : '' }}>Pendientes (No entregados)</option>
                                    <option value="" {{ request('shipping_status') === '' ? 'selected' : '' }}>Todos los estados</option>
                                    <option value="ready_to_ship" {{ request('shipping_status') === 'ready_to_ship' ? 'selected' : '' }}>Listo para enviar</option>
                                    <option value="shipped" {{ request('shipping_status') === 'shipped' ? 'selected' : '' }}>En tránsito (Shipped)</option>
                                    <option value="delivered" {{ request('shipping_status') === 'delivered' ? 'selected' : '' }}>Entregado (Delivered)</option>
                                    <option value="not_delivered" {{ request('shipping_status') === 'not_delivered' ? 'selected' : '' }}>No Entregado (Rechazado/Devuelto)</option>
                                    <option value="cancelled" {{ request('shipping_status') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>

                            {{-- Buscador --}}
                            <div class="input-group shadow-sm flex-nowrap" style="max-width: 250px;">
                                <span class="input-group-text bg-white text-muted border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-start-0 bg-white ps-0" placeholder="N° de orden o comprador..." value="{{ request('search') }}">
                                @if(request('search'))
                                <a href="{{ route('plataformas.ml.orders') }}" class="btn btn-outline-secondary border-start-0 border-end-0 bg-white text-danger d-flex align-items-center px-2" title="Limpiar">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-dark px-3 fw-bold shadow-sm">
                                <i class="bi bi-funnel"></i> <span class="d-none d-md-inline">Filtrar</span>
                            </button>
                        </form>

                        {{-- Botón Sincronizar --}}
                        <div class="input-group shadow-sm flex-nowrap m-0" style="max-width: 300px;">
                            <span class="input-group-text bg-white fw-bold text-muted border-end-0">
                                Fecha:
                            </span>
                            <input type="date" id="sync_from_date" class="form-control border-start-0 bg-white ps-0" value="{{ now()->subDays(3)->format('Y-m-d') }}" style="min-width: 130px;">
                            <button type="button" class="btn btn-warning fw-bold text-dark" id="btnSyncML" onclick="syncML()">
                                <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Sync</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Orden</th>
                            <th>Comprador</th>
                            <th>Monto</th>
                            <th>Productos</th>
                            <th>Método de Despacho</th>
                            <th>Estado Envío</th>
                            <th>Fecha Creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $order->ml_order_id }}</div>
                            </td>
                            <td>
                                <div class="text-dark">{{ $order->buyer_name ?? $order->buyer_nickname ?? '—' }}</div>
                            </td>
                            <td>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td>
                            <td>
                                <div class="small">
                                    @forelse($order->items->take(3) as $item)
                                    <div class="mb-1">
                                        <span class="d-block text-dark fw-bold" title="{{ $item->title }}">{{ $item->title }}</span>
                                        <span class="d-block text-muted" style="font-size: 10px;">x{{ $item->quantity }}</span>
                                    </div>
                                    @empty
                                    <span class="text-muted">—</span>
                                    @endforelse
                                    @if($order->items->count() > 3)
                                    <span class="text-primary" style="font-size: 10px;">+{{ $order->items->count() - 3 }} más</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                $badges = [
                                'self_service' => ['label' => 'Flex', 'color' => 'primary'],
                                'xd_drop_off' => ['label' => 'Urbano', 'color' => 'warning'],
                                'fulfillment' => ['label' => 'ME Full', 'color' => 'success'],
                                'custom' => ['label' => 'Acuerdo', 'color' => 'secondary'],
                                'drop_off' => ['label' => 'Punto de entrega', 'color' => 'info'],
                                ];
                                $info = $badges[$order->logistic_type] ?? null;
                                @endphp
                                @if($info)
                                <span class="badge rounded-pill px-3 bg-{{ $info['color'] }}">
                                    {{ $order->logistic_label ?? $info['label'] }}
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                $statusMap = [
                                'shipped' => 'Enviado',
                                'ready_to_ship' => 'Listo para Enviar',
                                'delivered' => 'Entregado',
                                'not_delivered' => 'No Entregado',
                                'handling' => 'Preparando',
                                'pending' => 'Pendiente',
                                'cancelled' => 'Cancelado',
                                ];
                                $statusColor = [
                                'shipped' => 'primary',
                                'ready_to_ship' => 'info',
                                'delivered' => 'success',
                                'not_delivered' => 'danger',
                                'handling' => 'warning',
                                'pending' => 'warning',
                                'cancelled' => 'danger',
                                ];
                                $ss = $order->shipping_status ?? '';
                                $displayStatus = $statusMap[$ss] ?? ucfirst($ss ?: '');
                                $color = $statusColor[$ss] ?? 'secondary';
                                @endphp
                                @if($displayStatus)
                                <span class="badge rounded-pill px-3 bg-{{ $color }}">
                                    {{ $displayStatus }}
                                </span>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>{{ $order->created_at_ml?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                No hay órdenes sincronizadas aún. Usa el botón <strong>Sincronizar</strong>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
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
        const dateInput = document.getElementById('sync_from_date');
        const fromDate = dateInput ? dateInput.value : '';
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sync...';

        fetch('{{ route("plataformas.ml.sync-orders") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    from_date: fromDate ? fromDate + 'T00:00:00.000-05:00' : null
                })
            })
            .then(r => r.json())
            .then(data => {
                pollSyncStatus();
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync';
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