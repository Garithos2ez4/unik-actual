@extends('layouts.app')
@section('title', 'Mercado Libre - Envíos Flex')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                {{-- Título --}}
                <div class="col-xl-3 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-rocket-takeoff-fill me-2" style="color:#FFE600"></i>Envíos Flex (ML)
                    </h1>
                </div>

                {{-- Barra de Herramientas --}}
                <div class="col-xl-9 col-lg-12">
                    <form method="GET" class="d-flex flex-wrap flex-xl-nowrap gap-2 justify-content-xl-end align-items-center w-100">
                        {{-- Filtro Estado Envío --}}
                        <div class="input-group shadow-sm flex-nowrap" style="max-width: 240px;">
                            <span class="input-group-text bg-white fw-bold text-muted border-end-0">
                                <i class="bi bi-box-seam"></i>
                            </span>
                            <select name="shipping_status" class="form-select border-start-0 bg-white ps-0">
                                <option value="" {{ request('shipping_status') === '' ? 'selected' : '' }}>Todos los estados</option>
                                <option value="pending" {{ request('shipping_status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="ready_to_ship" {{ request('shipping_status') === 'ready_to_ship' ? 'selected' : '' }}>Listo para enviar</option>
                                <option value="shipped" {{ request('shipping_status') === 'shipped' ? 'selected' : '' }}>En tránsito (Shipped)</option>
                                <option value="delivered" {{ request('shipping_status') === 'delivered' ? 'selected' : '' }}>Entregado (Delivered)</option>
                                <option value="not_delivered" {{ request('shipping_status') === 'not_delivered' ? 'selected' : '' }}>No Entregado / Rechazado</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-dark fw-bold shadow-sm px-4">
                            Filtrar
                        </button>
                        <a href="{{ route('plataformas.ml.flex') }}" class="btn btn-outline-secondary fw-bold shadow-sm">
                            <i class="bi bi-eraser-fill"></i>
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="d-flex justify-content-end p-2">
                <button type="button" class="btn btn-warning px-3 fw-bold shadow-sm" id="btnSyncML" onclick="syncML()">
                    <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Sincronizar Órdenes</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Orden</th>
                            <th>Comprador</th>
                            <th style="min-width: 250px;">Dirección de Entrega (Flex)</th>
                            <th>Productos</th>
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
                            <td>
                                @php
                                    $address = $order->payload['shipment']['receiver_address'] ?? null;
                                    $addressLine = $address['address_line'] ?? $address['street_name'] ?? '—';
                                    $city = $address['city']['name'] ?? '';
                                    $state = $address['state']['name'] ?? '';
                                    $zip = $address['zip_code'] ?? '';
                                @endphp
                                @if($address)
                                    <div class="fw-bold text-dark" style="font-size: 14px;">{{ $addressLine }}</div>
                                    <div class="text-muted" style="font-size: 12px;">{{ $city }}{{ $state ? ', ' . $state : '' }}{{ $zip ? ' (' . $zip . ')' : '' }}</div>
                                @else
                                    <span class="text-muted fst-italic">Dirección no sincronizada <br><small>(Sincroniza nuevamente para obtenerla)</small></span>
                                @endif
                            </td>
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
                                
                                $substatusMap = [
                                    'out_for_delivery' => 'Salida a ruta',
                                    'receiver_absent' => 'Nadie en domicilio',
                                    'bad_address' => 'Domicilio incorrecto',
                                    'buyer_rescheduled' => 'Reprogramado',
                                    'delivery_blocked' => 'Entregado lejos',
                                    'waiting_for_confirmation' => 'Esperando confirmación',
                                    'refused_delivery' => 'Rechazado'
                                ];

                                $ss = $order->shipping_status ?? '';
                                $sub = $order->shipping_substatus ?? '';
                                
                                $displayStatus = $statusMap[$ss] ?? ucfirst($ss ?: '—');
                                $displaySubstatus = $substatusMap[$sub] ?? ucfirst($sub);
                                $color = $statusColor[$ss] ?? 'secondary';
                                @endphp
                                <span class="badge rounded-pill px-3 mb-1 bg-{{ $color }}">
                                    {{ $displayStatus }}
                                </span>
                                @if($sub)
                                <div class="text-muted fw-bold" style="font-size: 11px;">
                                    <i class="bi bi-info-circle me-1"></i>{{ $displaySubstatus }}
                                </div>
                                @endif
                                
                                @if($order->driver_id)
                                <div class="text-primary mt-1" style="font-size: 11px;" title="ID Transportista (Driver)">
                                    <i class="bi bi-truck me-1"></i>Driver: {{ $order->driver_id }}
                                </div>
                                @endif
                            </td>
                            <td>{{ $order->created_at_ml?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                No se encontraron envíos Flex.
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
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sincronizando...';

        fetch('{{ route("plataformas.ml.sync-orders") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(r => r.json())
            .then(data => {
                pollSyncStatus();
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sincronizar Órdenes';
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
