<div class="card border-0 shadow-sm rounded-4 mb-4 h-100">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-box-seam text-warning me-2"></i>Productos Más Vendidos (Unidades)</h5>
            <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Top Cantidad</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-sistema-uno text-white">
                    <tr>
                        <th class="ps-4 py-3 border-0 small uppercase fw-bold">Producto / Modelo</th>
                        <th class="text-center pe-4 py-3 border-0 small uppercase fw-bold" style="width: 140px;">Unidades Vendidas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productosMostSoldMonth as $product)
                    <tr>
                        <td class="ps-4 border-0">
                            <div class="fw-bold text-dark text-truncate" style="max-width: 320px;" title="{{ $product->nombreProducto }}">
                                {{ $product->nombreProducto }}
                            </div>
                            <small class="text-muted">{{ $product->modelo }}</small>
                        </td>
                        <td class="text-center pe-4 border-0">
                            <span class="badge bg-warning text-dark border-0 px-3 py-1 fs-6 fw-bold rounded-pill">
                                {{ $product->total_unidades }} u.
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center py-4 text-muted">No hay datos de ventas este mes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
