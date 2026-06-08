    <!-- Desglose de Transferencias por Banco / Método de Pago -->
    <div class="mt-5">
        <h4 class="fw-bold text-dark mb-4"><i class="bi bi-bank text-primary me-2"></i>Desglose de Transferencias por Banco / M&eacute;todo de Pago</h4>
        
        @forelse($detallePagosTienda as $metodo => $pagos)
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-wallet2 me-2 text-primary"></i>{{ $metodo }}
                    </h5>
                    <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
                        S/ {{ number_format($pagos->sum('monto'), 2) }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-uppercase small fw-bold text-secondary"># Venta</th>
                                    <th class="text-uppercase small fw-bold text-secondary">Fecha</th>
                                    <th class="text-uppercase small fw-bold text-secondary">Vendedor</th>
                                    <th class="text-uppercase small fw-bold text-secondary">Nro. Operaci&oacute;n</th>
                                    <th class="text-uppercase small fw-bold text-secondary text-end">Monto Recibido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pagos as $pago)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">V-{{ str_pad($pago->idVenta, 5, '0', STR_PAD_LEFT) }}</span></td>
                                    <td class="text-muted small">{{ \Carbon\Carbon::parse($pago->fechaPago)->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge bg-secondary opacity-75"><i class="bi bi-person me-1"></i>{{ $pago->vendedor ?? 'N/A' }}</span></td>
                                    <td><span class="text-muted font-monospace">{{ $pago->nroOperacion ?: 'No Registrado' }}</span></td>
                                    <td class="text-end fw-semibold text-dark">S/ {{ number_format($pago->monto, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-light border text-muted mt-3">
                <i class="bi bi-info-circle me-2"></i>No hay transferencias ni pagos detallados en este per&iacute;odo.
            </div>
        @endforelse
    </div>
