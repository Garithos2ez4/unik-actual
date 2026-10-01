@extends('layouts.app')

@section('title', 'Catálogo Ripley')

@section('content')
<div class="container-fluid pt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-shop"></i> Catálogo Ripley</h3>
        <a href="{{ route('plataformas') }}" class="btn btn-secondary">Volver a Plataformas</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error') || $error)
        <div class="alert alert-danger">{{ session('error') ?? $error }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('plataformas.ripley.catalogo') }}" method="GET" class="d-flex align-items-end flex-wrap gap-3">
                <div style="flex: 1; min-width: 250px;">
                    <label class="form-label text-muted small mb-1">Buscar por Título o SKU</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Título o SKU..." value="{{ $search }}">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
                    </div>
                </div>
                @if($search)
                    <div>
                        <a href="{{ route('plataformas.ripley.catalogo') }}" class="btn btn-outline-secondary">Limpiar Filtros</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>SKU (SKU Seller)</th>
                            <th>Título de Publicación</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($offers as $offer)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $offer['shop_sku'] ?? 'N/A' }}</span><br>
                                    <small class="text-muted">Product SKU: {{ $offer['product_sku'] ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    {{ $offer['product_title'] ?? 'Sin Título' }}
                                </td>
                                <td>
                                    <span class="text-success fw-bold">S/ {{ number_format($offer['price'] ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    @php $qty = $offer['quantity'] ?? 0; @endphp
                                    <span class="badge {{ $qty > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $qty }} u.
                                    </span>
                                </td>
                                <td>
                                    @if(isset($offer['active']) && $offer['active'])
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Activo</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2"></i><br>
                                    No se encontraron publicaciones.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($totalCount) && $totalCount > $limit)
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <span class="text-muted small">Mostrando {{ count($offers) }} de {{ $totalCount }} publicaciones</span>
                <div class="btn-group">
                    @if($offset > 0)
                        <a href="{{ request()->fullUrlWithQuery(['offset' => max(0, $offset - $limit)]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-left"></i> Anterior</a>
                    @endif
                    @if($offset + count($offers) < $totalCount)
                        <a href="{{ request()->fullUrlWithQuery(['offset' => $offset + $limit]) }}" class="btn btn-outline-primary btn-sm">Siguiente <i class="bi bi-chevron-right"></i></a>
                    @endif
                </div>
            </div>
        @else
            <div class="card-footer bg-white">
                <span class="text-muted small">Mostrando {{ count($offers) }} publicaciones</span>
            </div>
        @endif
    </div>
</div>
@endsection
