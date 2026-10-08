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
                                    <option value="pending_only" {{ ($selectedShippingStatus ?? request('shipping_status', 'pending_only')) === 'pending_only' ? 'selected' : '' }}>Pendientes (No entregados)</option>
                                    <option value="all" {{ ($selectedShippingStatus ?? request('shipping_status')) === 'all' ? 'selected' : '' }}>Todos los estados</option>
                                    <option value="ready_to_ship" {{ ($selectedShippingStatus ?? request('shipping_status')) === 'ready_to_ship' ? 'selected' : '' }}>Listo para enviar</option>
                                    <option value="shipped" {{ ($selectedShippingStatus ?? request('shipping_status')) === 'shipped' ? 'selected' : '' }}>En tránsito (Shipped)</option>
                                    <option value="delivered" {{ ($selectedShippingStatus ?? request('shipping_status')) === 'delivered' ? 'selected' : '' }}>Entregado (Delivered)</option>
                                    <option value="not_delivered" {{ ($selectedShippingStatus ?? request('shipping_status')) === 'not_delivered' ? 'selected' : '' }}>No Entregado (Rechazado/Devuelto)</option>
                                    <option value="cancelled" {{ ($selectedShippingStatus ?? request('shipping_status')) === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>

                            {{-- Filtro Transportista --}}
                            <div class="input-group shadow-sm flex-nowrap" style="max-width: 200px;">
                                <span class="input-group-text bg-white fw-bold text-muted border-end-0">
                                    <i class="bi bi-truck"></i>
                                </span>
                                <select name="carrier_name" class="form-select border-start-0 bg-white ps-0">
                                    <option value="" {{ request('carrier_name') === '' ? 'selected' : '' }}>Agencias (Todas)</option>
                                    <option value="urbano_all" {{ request('carrier_name') === 'urbano_all' ? 'selected' : '' }}>Urbano (Regular y Express)</option>
                                    <option value="302093" {{ request('carrier_name') === '302093' ? 'selected' : '' }}>Olva Courier</option>
                                    <option value="1390020" {{ request('carrier_name') === '1390020' ? 'selected' : '' }}>Scharff</option>
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

    @include('plataformas.mercadolibre.partials.sku-counter')

    {{-- Tabla --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 p-2 d-flex justify-content-between align-items-center">
            <button class="btn btn-sm btn-outline-secondary fw-bold shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTablaOrders" aria-expanded="true" aria-controls="collapseTablaOrders">
                <i class="bi bi-arrows-collapse"></i> Ocultar / Mostrar Tabla
            </button>
        </div>
        <div class="collapse show" id="collapseTablaOrders">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                            <th class="ps-4">Orden</th>
                            <th>SKU Seller</th>
                            <th>Cuenta</th>
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
                                <div class="d-flex flex-column gap-1">
                                    @forelse($order->items as $item)
                                        <div class="text-dark fw-bold" style="font-size: 0.85rem;">{{ $item->seller_sku ?: 'N/A' }}</div>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="align-middle text-center">
                                <span class="badge" style="background-color: #FFE600; color: #2D3277;">
                                    <i class="bi bi-bag-fill me-1"></i>{{ $order->credential->seller_nickname ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td>
                            <td>
                                <div class="d-flex flex-column gap-2">
                                    @forelse($order->items->take(3) as $item)
                                    <div class="border-start border-3 border-primary ps-2">
                                        <div class="text-dark fw-bold" style="font-size: 0.85rem; line-height: 1.2;">{{ $item->title }}</div>
                                        <div class="text-muted mt-1" style="font-size: 0.75rem;"><i class="bi bi-box-seam me-1"></i>Cant: <strong>{{ $item->quantity }}</strong></div>
                                    </div>
                                    @empty
                                    <span class="text-muted">—</span>
                                    @endforelse
                                    @if($order->items->count() > 3)
                                    <div class="text-primary fw-bold" style="font-size: 0.8rem;">
                                        +{{ $order->items->count() - 3 }} producto(s) más
                                    </div>
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
                                'drop_off' => ['label' => 'Punto de entrega', 'color' => 'success'],
                                ];
                                $info = $badges[$order->logistic_type] ?? null;
                                @endphp
                                @if($info)
                                <div class="mb-1">
                                    <span class="badge rounded-pill px-3 bg-{{ $info['color'] }}">
                                        {{ $order->logistic_label ?? $info['label'] }}
                                    </span>
                                </div>
                                @else
                                <div class="mb-1">
                                    <span class="text-muted">—</span>
                                </div>
                                @endif
                                
                                @if($order->carrier_name)
                                @php
                                $carrierNames = [
                                    '301273' => 'Urbano',
                                    '302093' => 'Olva Courier',
                                    '1390020' => 'Scharff',
                                    '952082' => 'Flex (Mensajería)',
                                    '658142' => 'Urbano Express',
                                ];
                                $displayCarrier = $carrierNames[$order->carrier_name] ?? $order->carrier_name;
                                @endphp
                                <div class="small fw-semibold text-muted">
                                    <i class="bi bi-truck me-1"></i>{{ $displayCarrier }}
                                </div>
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
                            <td colspan="8" class="text-center py-5 text-muted">
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
@include('plataformas.mercadolibre.logic.orders')
@endpush