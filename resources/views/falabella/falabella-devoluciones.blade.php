@extends('layouts.app')

@section('title', 'Devoluciones Falabella')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    @php
    $statMap = [
        'canceled' => ['label' => 'Cancelada / Devolución', 'color' => 'danger', 'icon' => 'bi-x-circle-fill'],
        'returned' => ['label' => 'Devuelta', 'color' => 'success', 'icon' => 'bi-check-circle-fill'],
        'return_waiting_for_approval' => ['label' => 'Esperando aprobación', 'color' => 'warning', 'icon' => 'bi-hourglass-split'],
        'return_shipped_by_customer' => ['label' => 'En camino', 'color' => 'info', 'icon' => 'bi-truck'],
        'return_delivered_to_seller' => ['label' => 'Recibida por vendedor', 'color' => 'primary', 'icon' => 'bi-box-arrow-in-down'],
    ];

    $reasonMap = [
        'NS_SHOW_AUTOMATED' => [
            'title' => 'Punto de entrega',
            'detail' => 'Cliente no retira el paquete, devolución activada por sistema'
        ],
        'CUSTOMER_REGRET' => [
            'title' => 'Arrepentimiento de compra',
            'detail' => 'El cliente decidió cancelar o devolver el producto'
        ],
        'FULFILLMENT_ISSUE' => [
            'title' => 'Problema de Fulfillment / Inventario',
            'detail' => 'Inconveniente logístico interno o falta de stock'
        ],
        'PRICE_ISSUE' => [
            'title' => 'Problema de precio',
            'detail' => 'Inconsistencia en el precio publicado de la orden'
        ],
        'ORDER_INFORMATION_CHANGE' => [
            'title' => 'Cambio de información',
            'detail' => 'El cliente modificó datos vitales del pedido'
        ],
        'NOT_DELIVERED_AFTER_RETRIES' => [
            'title' => 'Entrega fallida',
            'detail' => 'No se pudo entregar el paquete al cliente tras varios intentos'
        ],
        'CANCELLED_BY_CUSTOMER' => [
            'title' => 'Cancelado por el cliente',
            'detail' => 'El comprador canceló la orden antes o durante el proceso'
        ]
    ];
    @endphp
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #fff5f5, #fff);">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">

                {{-- Título --}}
                <div class="col-xl-3 col-lg-12">
                    <h1 class="h4 mb-0 fw-bold text-danger">
                        <i class="bi bi-arrow-return-left me-2"></i>Devoluciones
                    </h1>
                    <p class="text-muted small mb-0">Falabella Seller Center</p>
                </div>

                {{-- Filtros --}}
                <div class="col-xl-6 col-lg-8">
                    <form method="GET" action="{{ route('plataformas.falabella.devoluciones') }}"
                        class="d-flex gap-2 align-items-center bg-white p-1 rounded border shadow-sm flex-wrap">

                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Desde:</span>
                            <input type="date" name="date_from" value="{{ $dateFrom }}"
                                class="form-control border-0 bg-transparent" style="max-width: 130px;">
                        </div>

                        <div class="input-group input-group-sm border-start">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Hasta:</span>
                            <input type="date" name="date_to" value="{{ $dateTo }}"
                                class="form-control border-0 bg-transparent" style="max-width: 130px;">
                        </div>

                        <div class="input-group input-group-sm border-start">
                            <span class="input-group-text bg-transparent border-0 small fw-bold text-muted">Estado:</span>
                            <select name="status" class="form-select border-0 bg-transparent" style="min-width: 130px;">
                                <option value="" {{ $selectedStatus == ''         ? 'selected' : '' }}>Todos</option>
                                <option value="canceled" {{ $selectedStatus == 'canceled'                     ? 'selected' : '' }}>Canceladas / En devolución</option>
                                <option value="returned" {{ $selectedStatus == 'returned'                     ? 'selected' : '' }}>Devueltas (completadas)</option>
                                <option value="return_waiting_for_approval" {{ $selectedStatus == 'return_waiting_for_approval'  ? 'selected' : '' }}>Esperando aprobación</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-danger btn-sm px-3 shadow-sm flex-shrink-0">
                            <i class="bi bi-funnel-fill"></i> <span class="d-none d-md-inline">Filtrar</span>
                        </button>
                    </form>
                </div>

                {{-- Sincronizar --}}
                <div class="col-xl-3 col-lg-4 d-flex justify-content-end">
                    <form method="POST" action="{{ route('plataformas.falabella.sync-devoluciones') }}">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        <button type="submit" class="btn btn-outline-danger px-4 shadow-sm d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Sincronizar devoluciones</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats rápidas --}}
    @if($returns->count() > 0)
    <div class="row g-3 mb-4">
        @php
        $byStatus = $returns->groupBy(function($ret) {
            $st = $ret->status;
            if (empty($st) || $st === '-') {
                $st = $ret->items->first()->status ?? '-';
            }
            return $st;
        });
        @endphp
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-danger">{{ $returns->count() }}</div>
                <div class="text-muted small">Total devoluciones</div>
            </div>
        </div>
        @foreach($statMap as $statusKey => $info)
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center py-3 border-{{ $info['color'] }}" style="border-left: 4px solid !important;">
                <div class="fs-3 fw-bold text-{{ $info['color'] }}">{{ $byStatus->get($statusKey, collect())->count() }}</div>
                <div class="text-muted small">{{ $info['label'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tabla --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">
                <i class="bi bi-table me-2 text-muted"></i>
                Devoluciones del {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            </span>
            <span class="badge bg-danger rounded-pill">{{ $returns->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Producto</th>
                            <th>N° Orden</th>
                            <th>Modo logístico</th>
                            <th>Razón de devolución</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="pe-4 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                        @php
                        $realStatus = $return->status;
                        if (empty($realStatus) || $realStatus === '-') {
                            $firstItem = collect($return->items)->first();
                            $realStatus = $firstItem ? $firstItem->status : '-';
                        }
                        $statusInfo = $statMap[$realStatus] ?? ['label' => $realStatus, 'color' => 'secondary', 'icon' => 'bi-question-circle'];
                        
                        // Extraer razón de devolución desde el primer ítem
                        $firstItemInfo = collect($return->items)->first();
                        $apiReason = $firstItemInfo ? ($firstItemInfo->payload['Reason'] ?? '') : '';
                        
                        $mappedReason = $reasonMap[$apiReason] ?? [
                            'title' => $apiReason ?: 'Devolución general',
                            'detail' => $apiReason ? 'Motivo reportado por API' : 'Motivo no especificado por Falabella'
                        ];
                        @endphp
                        <tr>
                            <td class="ps-4" style="max-width: 280px;">
                                @foreach($return->items as $item)
                                <div class="mb-1">
                                    <div class="fw-bold text-dark small">{{ $item->name }}</div>
                                    <div class="text-muted" style="font-size: 11px;">SKU: {{ $item->seller_sku }}</div>
                                </div>
                                @endforeach
                            </td>
                            <td>
                                <div class="fw-bold">{{ $return->order_number }}</div>
                                <div class="text-muted small">ID: {{ $return->order_id }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $return->shipping_type ?: 'Fulfillment by Seller' }}
                                </span>
                            </td>
                            <td style="max-width: 200px;">
                                <div class="small text-muted text-center">
                                    <div class="text-dark fw-medium">{{ $mappedReason['title'] }}</div>
                                    <div style="font-size: 11px;">{{ $mappedReason['detail'] }}</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-{{ $statusInfo['color'] }} px-3">
                                    <i class="bi {{ $statusInfo['icon'] }} me-1"></i>
                                    {{ $statusInfo['label'] }}
                                </span>
                            </td>
                            <td>
                                <div class="small">{{ $return->updated_at_falabella ? $return->updated_at_falabella->format('d/m/Y H:i') : ($return->synced_at ? $return->synced_at->format('d/m/Y H:i') : '-') }}</div>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('plataformas.falabella.order-details', ['order_id' => $return->order_id]) }}"
                                    class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                    <i class="bi bi-eye-fill me-1"></i> Ver detalle
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-25"></i>
                                No hay devoluciones para el rango seleccionado.<br>
                                <span class="small">Sincroniza para traer los datos desde Falabella.</span>
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