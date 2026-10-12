@extends('layouts.app')

@section('title', 'Historial de Ventas por Producto')

@section('content')
<div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0 text-dark">
            <i class="bi bi-clock-history text-primary me-2"></i> Historial de Ventas por Producto
        </h2>
    </div>

    <!-- Buscador -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-search me-2"></i>Buscar Producto</h5>
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label for="productoSelect" class="form-label text-muted small fw-bold">Seleccione o escriba el nombre/modelo del producto</label>
                    <select id="productoSelect" class="form-control" placeholder="Escriba para buscar..."></select>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <button type="button" id="btnBuscar" class="btn btn-primary w-100 py-2 rounded-3 fw-bold" disabled>
                        <i class="bi bi-table me-2"></i> Ver Historial
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div class="card border-0 shadow-sm rounded-4" id="resultadosCard" style="display: none;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3" id="productTitle">Historial de Ventas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="historialTable" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-bold text-secondary">Fecha</th>
                            <th class="text-uppercase small fw-bold text-secondary">Canal</th>
                            <th class="text-uppercase small fw-bold text-secondary">Nro Orden / Factura</th>
                            <th class="text-uppercase small fw-bold text-secondary text-center">Nro Serie</th>
                            <th class="text-uppercase small fw-bold text-secondary text-center">Cantidad</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Precio Unit.</th>
                            <th class="text-uppercase small fw-bold text-secondary text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Llenado vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Añadimos jQuery y DataTables que se requieren -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

@include('analytics.logic.producto_historial_script')
@endpush