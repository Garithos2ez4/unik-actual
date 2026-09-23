@extends('layouts.app')

@section('title', 'Lista de Picking - Falabella')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Lista de Picking</h1>
            <p class="text-muted small mb-0">Consolidado de productos para alistar del día {{ $selectedDate }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('plataformas.falabella.orders', ['date' => $selectedDate, 'status' => $selectedStatus]) }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="{{ route('plataformas.falabella.picking.pdf', ['date' => $selectedDate, 'status' => $selectedStatus]) }}" target="_blank" class="btn btn-danger btn-sm px-3 shadow-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Descargar PDF
            </a>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4 no-print">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="small opacity-75">Órdenes totales</div>
                    <div class="h3 fw-bold mb-0">{{ $orders->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="small opacity-75">SKUs distintos</div>
                    <div class="h3 fw-bold mb-0">{{ count($pickingItems) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <div class="small opacity-75">Unidades totales</div>
                    <div class="h3 fw-bold mb-0">{{ collect($pickingItems)->sum('total_qty') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Picking -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark">Productos Consolidados</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">SKU</th>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="pe-4">Órdenes Relacionadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pickingItems as $item)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-primary">{{ $item['seller_sku'] }}</div>
                                <div class="small text-muted" style="font-size: 11px;">{{ $item['falabella_sku'] }}</div>
                            </td>
                            <td>
                                @if($item['image'])
                                    <img src="{{ $item['image'] }}" class="rounded shadow-sm" style="width: 50px; height: 50px; object-fit: contain;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted small" style="width: 50px; height: 50px;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark fw-medium">{{ $item['name'] }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-dark rounded-pill px-3 fs-6">
                                    {{ $item['total_qty'] }}
                                </span>
                            </td>
                            <td class="pe-4">
                                @foreach($item['orders'] as $ord)
                                    <span class="badge bg-light text-dark border me-1 mb-1">
                                        #{{ $ord['order_number'] }} ({{ $ord['quantity'] }})
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No hay productos para el picking con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .sidebar, .navbar { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        body { background: white !important; }
    }
</style>
@endsection
