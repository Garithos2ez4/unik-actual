<div class="card border-0 shadow-sm rounded-4 mb-4 h-100">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-trophy text-warning me-2"></i>Top 3 Mejores Meses</h5>
            <span class="badge bg-warning-subtle text-dark rounded-pill px-3">Histórico</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-sistema-uno text-white">
                    <tr>
                        <th class="ps-4 py-3 border-0 small uppercase fw-bold">Posición / Mes</th>
                        <th class="text-end pe-4 py-3 border-0 small uppercase fw-bold">Total Facturado (S/)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topBestMonths as $index => $mes)
                    <tr>
                        <td class="ps-4 border-0">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    @if($index == 0)
                                        <span class="badge bg-warning text-dark rounded-circle p-2" title="Oro"><i class="bi bi-award-fill fs-5"></i></span>
                                    @elseif($index == 1)
                                        <span class="badge bg-secondary text-white rounded-circle p-2" title="Plata"><i class="bi bi-award-fill fs-5"></i></span>
                                    @else
                                        <span class="badge bg-bronze text-white rounded-circle p-2" style="background-color: #cd7f32 !important;" title="Bronce"><i class="bi bi-award-fill fs-5"></i></span>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $mes->mes_nombre }}</div>
                                    <small class="text-muted">Ranking #{{ $index + 1 }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-end pe-4 border-0">
                            <h5 class="fw-bold mb-0 text-primary">S/ {{ number_format($mes->total_monto, 2) }}</h5>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
