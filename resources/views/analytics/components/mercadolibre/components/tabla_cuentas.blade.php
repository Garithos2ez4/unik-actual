<!-- Tabla de Desglose por Cuentas -->
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-diagram-2 me-2 text-primary"></i>Desglose por Cuenta de Mercado Libre
        </h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-secondary">Cuenta</th>
                        <th class="text-uppercase small fw-bold text-secondary text-center">Nro. Ventas</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Ingresos</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Costos Base</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Ganancia Neta</th>
                        <th class="text-uppercase small fw-bold text-secondary text-center">Margen (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cuentasMercadoLibre as $cuenta)
                    <tr>
                        <td><span class="badge bg-secondary opacity-75"><i class="bi bi-person-badge me-1"></i>{{ $cuenta->nombreCuenta }}</span></td>
                        <td class="text-center"><span class="badge bg-light text-dark border">{{ $cuenta->cantidad_ventas }}</span></td>
                        <td class="text-end fw-semibold text-dark">S/ {{ number_format($cuenta->total_ingresos, 2) }}</td>
                        <td class="text-end text-muted small">S/ {{ number_format($cuenta->total_costos, 2) }}</td>
                        <td class="text-end fw-bold text-success">S/ {{ number_format($cuenta->ganancia, 2) }}</td>
                        <td class="text-center">
                            @if($cuenta->margen > 20)
                                <span class="badge bg-success rounded-pill px-3">{{ number_format($cuenta->margen, 1) }}%</span>
                            @elseif($cuenta->margen > 0)
                                <span class="badge bg-warning text-dark rounded-pill px-3">{{ number_format($cuenta->margen, 1) }}%</span>
                            @else
                                <span class="badge bg-danger rounded-pill px-3">{{ number_format($cuenta->margen, 1) }}%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-light"></i>
                            No hay información de cuentas de plataforma en este período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold text-dark">
                    <tr>
                        <td class="text-end text-uppercase">Totales:</td>
                        <td class="text-center">{{ $cuentasMercadoLibre->sum('cantidad_ventas') }}</td>
                        <td class="text-end">S/ {{ number_format($cuentasMercadoLibre->sum('total_ingresos'), 2) }}</td>
                        <td class="text-end">S/ {{ number_format($cuentasMercadoLibre->sum('total_costos'), 2) }}</td>
                        <td class="text-end text-success">S/ {{ number_format($cuentasMercadoLibre->sum('ganancia'), 2) }}</td>
                        <td class="text-center">
                            @php
                                $totIngresos = $cuentasMercadoLibre->sum('total_ingresos');
                                $totGanancia = $cuentasMercadoLibre->sum('ganancia');
                                $totMargen = $totIngresos > 0 ? ($totGanancia / $totIngresos) * 100 : 0;
                            @endphp
                            @if($totMargen > 20)
                                <span class="badge bg-success rounded-pill px-3">{{ number_format($totMargen, 1) }}%</span>
                            @elseif($totMargen > 0)
                                <span class="badge bg-warning text-dark rounded-pill px-3">{{ number_format($totMargen, 1) }}%</span>
                            @else
                                <span class="badge bg-danger rounded-pill px-3">{{ number_format($totMargen, 1) }}%</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
