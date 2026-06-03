<div class="card shadow-sm border-0 mb-4 h-100 animate__animated animate__fadeInUp">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-primary">
            <i class="bi bi-box-seam me-2"></i>Top 5 Productos Más Enviados
        </h5>
        <span class="badge bg-primary rounded-pill">Top 5</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="ps-4">PRODUCTO / MODELO</th>
                        <th class="text-end pe-4">CANTIDAD ENVIADA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topEnviados as $enviado)
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark d-inline-block text-truncate" style="max-width: 250px;" title="{{ $enviado->nombreProducto }}">
                                {{ $enviado->nombreProducto }}
                            </div>
                            <div class="text-muted small">
                                {{ $enviado->modelo ?: 'Sin modelo' }}
                            </div>
                        </td>
                        <td class="text-end pe-4 py-3">
                            <span class="badge bg-success bg-opacity-10 text-white px-3 py-2 rounded-pill fs-6 fw-bold">
                                {{ $enviado->total_enviado }} und.
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted py-5">
                            <i class="bi bi-truck display-4 opacity-50 mb-3 d-block"></i>
                            No hay envíos registrados en este periodo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>