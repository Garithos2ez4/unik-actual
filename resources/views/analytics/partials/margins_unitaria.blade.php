<div class="row mt-2">
    <!-- Productos con Mayor Margen Unitario de Ganancia -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 mb-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-currency-dollar text-success me-2"></i>Mayor Margen Unitario</h5>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3">Top 5 Rentables (Unidad)</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-sistema-uno text-white">
                            <tr>
                                <th class="ps-4 py-3 border-0 small uppercase fw-bold">Producto / Modelo</th>
                                <th class="text-center py-3 border-0 small uppercase fw-bold">Margen (%)</th>
                                <th class="text-end pe-4 py-3 border-0 small uppercase fw-bold">Ganancia Neta Unitaria</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productosMasGananciaUnitaria as $product)
                            <tr>
                                <td class="ps-4 border-0">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 280px;" title="{{ $product->nombreProducto }}">
                                        {{ $product->nombreProducto }}
                                    </div>
                                    <small class="text-muted">{{ $product->modelo }}</small>
                                </td>
                                <td class="text-center border-0">
                                    <span class="badge {{ $product->margen_porcentaje_unitario > 30 ? 'bg-success' : 'bg-primary' }} px-2 py-1">
                                        {{ number_format($product->margen_porcentaje_unitario, 1) }}%
                                    </span>
                                </td>
                                <td class="text-end pe-4 border-0 fw-bold text-success">
                                    S/ {{ number_format($product->ganancia_neta_unitaria, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No hay datos de ganancias este mes.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('analytics.partials.menor_margen_unitaria')
</div>
