<div class="row mt-4">
    <div class="col-12">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-badge me-2 text-primary"></i>Ventas por Cuentas (Plataforma)</h5>
        <div class="row g-3">
            @forelse($cuentasMercadoLibre as $cuenta)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="border-left: 4px solid var(--unik-warning) !important;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-uppercase small fw-bold text-secondary">{{ $cuenta->nombreCuenta }}</div>
                                <span class="badge bg-light text-dark border"><i class="bi bi-box-seam me-1"></i>{{ $cuenta->cantidad_ventas }} ventas</span>
                            </div>
                            <h4 class="fw-bold text-dark mb-0">S/ {{ number_format($cuenta->total_ingresos, 2) }}</h4>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-muted">
                        <i class="bi bi-info-circle me-2"></i>No hay registros de ventas por cuentas de plataforma en este período.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
