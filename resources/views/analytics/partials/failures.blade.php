<div class="card border-0 shadow-sm rounded-4 mb-4 h-100">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Productos con Fallas</h5>
            <span class="badge bg-danger-subtle text-danger rounded-pill px-3">Top 10</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-sistema-uno text-white">
                    <tr>
                        <th class="ps-4 py-3 border-0 small uppercase fw-bold">Producto / Modelo</th>
                        <th class="text-center py-3 border-0 small uppercase fw-bold">Incidencias</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productosConFallas as $falla)
                    <tr>
                        <td class="ps-4 border-0">
                            <div class="fw-bold text-dark">{{ $falla->nombreProducto }}</div>
                            <small class="text-muted">{{ $falla->modelo }}</small>
                        </td>
                        <td class="text-center border-0">
                            <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold" style="min-width: 45px;">{{ $falla->total_fallas }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center py-4 text-muted">No se registraron fallas con observaciones.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
