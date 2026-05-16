<div class="card border-0 shadow-sm rounded-4 mb-4 h-100">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-cash-stack text-success me-2"></i>Productos con Mayor Ingreso</h5>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Top Rentabilidad</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-sistema-uno text-white">
                    <tr>
                        <th class="ps-4 py-3 border-0 small uppercase fw-bold">Producto / Modelo</th>
                        <th class="text-center py-3 border-0 small uppercase fw-bold">Unidades</th>
                        <th class="text-end pe-4 py-3 border-0 small uppercase fw-bold">Total (S/)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productosMostRevenueMonth as $product)
                    <tr>
                        <td class="ps-4 border-0">
                            <div class="fw-bold text-dark text-truncate" style="max-width: 280px;" title="{{ $product->nombreProducto }}">
                                {{ $product->nombreProducto }}
                            </div>
                            <small class="text-muted">{{ $product->modelo }}</small>
                        </td>
                        <td class="text-center border-0">
                            <span class="badge bg-light text-dark border px-2 py-1">{{ $product->total_unidades }}</span>
                        </td>
                        <td class="text-end pe-4 border-0 fw-bold text-success">
                            S/ {{ number_format($product->total_ingreso, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">No hay datos de ingresos este mes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
