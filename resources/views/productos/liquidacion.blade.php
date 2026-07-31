@extends('layouts.app')

@section('title', 'Liquidación de Productos')

@section('content')

<div class="container-fluid">
    <br>
    <div class="row align-items-center mb-4">
        <div class="col-7">
            <h3><i class="bi bi-tags-fill text-danger"></i> Liquidación de Productos</h3>
            <p class="text-muted mb-0">Gestiona los productos con sobre-stock inmovilizado para ofrecerlos a precios especiales.</p>
        </div>
        <div class="col-5">
            <select id="buscar-producto-select" placeholder="Buscar cualquier producto para liquidar..."></select>
        </div>
    </div>

    <!-- Pestañas para Sugeridos y Liquidados -->
    <ul class="nav nav-tabs" id="liquidacionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-danger" id="sugeridos-tab" data-bs-toggle="tab" data-bs-target="#sugeridos" type="button" role="tab">
                Sugerencias (Más de 1 año)
                <span class="badge bg-danger ms-1">{{ count($sugeridos) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-success" id="activos-tab" data-bs-toggle="tab" data-bs-target="#activos" type="button" role="tab">
                Activos en Liquidación
                <span class="badge bg-success ms-1">{{ count($liquidados) }}</span>
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="liquidacionTabsContent">
        <!-- Tab: Sugeridos -->
        <div class="tab-pane fade show active p-3 bg-white border border-top-0 rounded-bottom" id="sugeridos" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Stock</th>
                            <th>F. Ingreso</th>
                            <th>Precio Costo (USD)</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sugeridos as $prod)
                        <tr>
                            <td>
                                <img src="{{ asset('storage/' . $prod->imagenProducto1) }}" alt="img" width="50" class="img-thumbnail">
                            </td>
                            <td>
                                <a href="{{ route('details', $prod->idProducto) }}" class="fw-bold text-decoration-none" target="_blank">{{ $prod->nombreProducto }}</a>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark fs-6">{{ $prod->stock_estancado }} uds</span>
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($prod->fecha_mas_antigua)->format('d/m/Y') }}
                                <br><small class="text-danger">{{ \Carbon\Carbon::parse($prod->fecha_mas_antigua)->diffForHumans() }}</small>
                            </td>
                            <td class="fw-bold text-primary">
                                ${{ number_format($prod->precioDolar, 2) }}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger btn-liquidar" 
                                    data-id="{{ $prod->idProducto }}"
                                    data-nombre="{{ $prod->nombreProducto }}"
                                    data-costo="{{ $prod->precioDolar }}"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalLiquidar">
                                    <i class="bi bi-tag-fill"></i> Poner en Liquidación
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No hay sugerencias por el momento. ¡Buen manejo de stock!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Activos -->
        <div class="tab-pane fade p-3 bg-white border border-top-0 rounded-bottom" id="activos" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Precio Normal (USD)</th>
                            <th>Precio Liquidación (S/)</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($liquidados as $prod)
                        <tr>
                            <td>
                                <img src="{{ asset('storage/' . $prod->imagenProducto1) }}" alt="img" width="50" class="img-thumbnail">
                            </td>
                            <td>
                                <a href="{{ route('details', $prod->idProducto) }}" class="fw-bold text-decoration-none" target="_blank">{{ $prod->nombreProducto }}</a>
                            </td>
                            <td class="text-muted text-decoration-line-through">
                                ${{ number_format($prod->precioDolar, 2) }}
                            </td>
                            <td class="fw-bold text-success fs-5">
                                S/ {{ number_format($prod->precio_liquidacion, 2) }}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary btn-quitar-liquidar" data-id="{{ $prod->idProducto }}">
                                    <i class="bi bi-x-circle"></i> Quitar Liquidación
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No hay productos en liquidación activos.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Poner en Liquidacion -->
<div class="modal fade" id="modalLiquidar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-tags"></i> Activar Liquidación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="liq-nombre" class="fw-bold text-secondary mb-3"></p>
                <div class="alert alert-warning py-2 mb-3">
                    <small><i class="bi bi-info-circle"></i> El precio configurado aquí será el precio final en la web (WebUnik), ignorando cualquier otro cálculo o margen, para darle salida rápida.</small>
                </div>
                
                <input type="hidden" id="liq-id">
                
                <div class="mb-3 row align-items-center">
                    <label class="col-sm-5 col-form-label text-muted">Costo Base (USD):</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control form-control-sm" id="liq-costo" readonly>
                    </div>
                </div>

                <div class="mb-3 row align-items-center">
                    <label class="col-sm-5 col-form-label text-muted">Referencia Costo (S/.):</label>
                    <div class="col-sm-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">S/</span>
                            <input type="text" class="form-control form-control-sm" id="liq-costo-soles" readonly>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">(Incluye IGV referencial y TC {{ $tcGlobal }})</small>
                    </div>
                </div>

                <hr>

                <div class="mb-2 row align-items-center">
                    <label class="col-sm-5 col-form-label fw-bold text-danger">Nuevo Precio Final Web:</label>
                    <div class="col-sm-7">
                        <div class="input-group">
                            <span class="input-group-text bg-danger text-white">S/</span>
                            <input type="number" step="0.01" class="form-control form-control-lg border-danger fw-bold" id="liq-precio-final">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger px-4" id="btn-save-liquidar">Activar Oferta</button>
            </div>
        </div>
    </div>
</div>

@include('productos.logic.liquidacion_scripts')

@endsection
