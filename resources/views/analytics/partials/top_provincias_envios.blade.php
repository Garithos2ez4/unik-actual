<div class="row mt-2">
    <!-- Top 5 Provincias Más Solicitadas -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mb-4 h-100 animate__animated animate__fadeInUp">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-info">
                    <i class="bi bi-map-fill me-2"></i>Top 5 Provincias (Envíos)
                </h5>
                <span class="badge bg-info rounded-pill">Top 5</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="ps-4">PROVINCIA DESTINO</th>
                                <th class="text-end pe-4">TOTAL ENVÍOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProvincias as $provincia)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark d-inline-block text-truncate" style="max-width: 250px;">
                                        {{ $provincia->nombre_provincia }}
                                    </div>
                                </td>
                                <td class="text-end pe-4 py-3">
                                    <span class="badge bg-info text-white px-3 py-2 rounded-pill fs-6 fw-bold">
                                        {{ $provincia->total_envios }} envíos
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-5">
                                    <i class="bi bi-map display-4 opacity-50 mb-3 d-block"></i>
                                    No hay destinos registrados en este periodo.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 5 Envíos por Monto -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mb-4 h-100 animate__animated animate__fadeInUp">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-success">
                    <i class="bi bi-cash-stack me-2"></i>Top 5 Envíos por Monto
                </h5>
                <span class="badge bg-success rounded-pill">Top 5</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="ps-4">CLIENTE / FECHA</th>
                                <th class="text-end pe-4">MONTO TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topEnviosPorMonto as $envioMonto)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark d-inline-block text-truncate" style="max-width: 250px;">
                                        {{ $envioMonto->nombre }} {{ $envioMonto->apellidoPaterno }}
                                    </div>
                                    <div class="text-muted small">
                                        DNI/RUC: {{ $envioMonto->numeroDocumento }} • Envío: {{ \Carbon\Carbon::parse($envioMonto->fecha_envio)->format('d/m/Y') }}
                                    </div>
                                </td>
                                <td class="text-end pe-4 py-3">
                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill fs-6 fw-bold">
                                        S/ {{ number_format($envioMonto->monto_total, 2) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-5">
                                    <i class="bi bi-cash display-4 opacity-50 mb-3 d-block"></i>
                                    No hay envíos con monto registrado.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
