@extends('layouts.app')

@section('title', 'Productos Falabella')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Catálogo Falabella Seller Center</h1>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2 bg-white p-2 rounded shadow-sm border">
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por SKU o Nombre..." class="form-control form-control-sm" style="min-width: 250px;">
                
                <select name="filter" class="form-control form-control-sm">
                    <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>Todos los estados</option>
                    <option value="active" {{ $filter == 'active' ? 'selected' : '' }}>Activos</option>
                    <option value="inactive" {{ $filter == 'inactive' ? 'selected' : '' }}>Inactivos</option>
                    <option value="deleted" {{ $filter == 'deleted' ? 'selected' : '' }}>Eliminados</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-search me-1"></i>
                </button>
                
                @if($search || $filter !== 'all')
                    <a href="{{ route('plataformas.falabella.productos') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    @if($error)
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Error de conexión con la API:</strong><br>
                {{ $error }}
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">SKU / ID</th>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Estado</th>
                            <th>Stock</th>
                            <th>Precio</th>
                            <th class="pe-4 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        @php $norm = $product['_normalized'] ?? []; @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="small text-muted mb-1">SKU Seller:</div>
                                <div class="fw-bold text-dark">{{ $norm['seller_sku'] }}</div>
                                <div class="small text-muted mt-2 mb-1">SKU Falabella:</div>
                                <div class="text-secondary small">{{ $norm['falabella_sku'] }}</div>
                            </td>
                            <td>
                                @php
                                    $localProd = \App\Models\Producto::where('codigoProducto', $norm['seller_sku'])->first();
                                @endphp
                                @if($localProd && $localProd->imagenProducto1)
                                    <img src="{{ asset('storage/' . $localProd->imagenProducto1) }}" class="rounded shadow-sm" style="width: 40px; height: 40px; object-fit: contain;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted small" style="width: 40px; height: 40px;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark fw-medium">{{ $norm['name'] }}</div>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-3 bg-{{ strtolower($norm['status']) == 'active' ? 'success' : 'secondary' }}">
                                    {{ $norm['status'] }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold {{ (int)$norm['stock'] <= 0 ? 'text-danger' : 'text-dark' }}">
                                    {{ $norm['stock'] }}
                                </div>
                            </td>
                            <td>S/ {{ number_format((float)$norm['price'], 2) }}</td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-link text-primary p-0" title="Ver en Seller Center">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                                No se encontraron productos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Mostrando {{ count($products) }} productos (Offset: {{ $offset }})
                </div>
                <div class="btn-group">
                    <a href="{{ route('plataformas.falabella.productos', array_merge(request()->query(), ['offset' => max(0, $offset - $limit)])) }}" 
                       class="btn btn-outline-secondary btn-sm {{ $offset <= 0 ? 'disabled' : '' }}">
                        <i class="bi bi-chevron-left me-1"></i> Anterior
                    </a>
                    <a href="{{ route('plataformas.falabella.productos', array_merge(request()->query(), ['offset' => $offset + $limit])) }}" 
                       class="btn btn-outline-secondary btn-sm {{ count($products) < $limit ? 'disabled' : '' }}">
                        Siguiente <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
