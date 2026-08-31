    <!-- Tendencia de Ventas -->
    @include('analytics.components.tienda.components.trends')

    <!-- Resumen por Métodos de Pago -->
    <div class="row mt-4">
        <div class="col-12">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 me-2 text-primary"></i>Ingresos por Método de Pago</h5>
            <div class="row g-3">
                @forelse($pagosTienda as $pago)
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="border-left: 4px solid var(--unik-primary) !important;">
                        <div class="card-body p-3">
                            <div class="text-uppercase small fw-bold text-secondary mb-1">{{ $pago->metodo_pago }}</div>
                            <h4 class="fw-bold text-dark mb-0">S/ {{ number_format($pago->total_monto, 2) }}</h4>
                            <div class="small text-muted mt-2"><i class="bi bi-receipt me-1"></i>{{ $pago->cantidad_transacciones }} transacciones</div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-light border text-muted">
                        <i class="bi bi-info-circle me-2"></i>No hay pagos registrados en este período.
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table me-2 text-primary"></i>Historial de Operaciones - Tienda
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaTienda">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-bold text-secondary">Vendedor</th>
                            <th class="text-uppercase small fw-bold text-secondary">Fecha</th>
                            <th class="text-uppercase small fw-bold text-secondary">Método de Pago</th>
                            <th class="text-uppercase small fw-bold text-secondary">Producto(s)</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Ingresos</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Costos Base</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Ganancia Neta</th>
                            <th class="text-uppercase small fw-bold text-secondary text-center">Margen (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventasTienda as $venta)
                        <tr>
                            <td><span class="badge bg-secondary opacity-75"><i class="bi bi-person me-1"></i>{{ $venta->nombre_usuario ?? 'N/A' }}</span></td>
                            <td class="text-muted small">{{ \Carbon\Carbon::parse($venta->fechaVenta)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-secondary opacity-75"><i class="bi bi-wallet2 me-1"></i>{{ $venta->metodos_pago ?? 'No Registrado' }}</span></td>
                            <td>
                                <div class="text-truncate" style="max-width: 250px;" title="{{ $venta->modelo }}">
                                    {{ $venta->modelo }}
                                </div>
                            </td>
                            <td class="text-end fw-semibold text-dark">S/ {{ number_format($venta->ingresos, 2) }}</td>
                            <td class="text-end text-muted small">S/ {{ number_format($venta->costos, 2) }}</td>
                            <td class="text-end fw-bold text-success">S/ {{ number_format($venta->ganancia, 2) }}</td>
                            <td class="text-center">
                                @if($venta->margen > 20)
                                <span class="badge bg-success rounded-pill px-3">{{ number_format($venta->margen, 1) }}%</span>
                                @elseif($venta->margen > 0)
                                <span class="badge bg-warning text-dark rounded-pill px-3">{{ number_format($venta->margen, 1) }}%</span>
                                @else
                                <span class="badge bg-danger rounded-pill px-3">{{ number_format($venta->margen, 1) }}%</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-light"></i>
                                No hay ventas directas de tienda registradas en este período.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold text-dark">
                        <tr>
                            <td colspan="4" class="text-end text-uppercase">Totales del Período:</td>
                            <td class="text-end">S/ {{ number_format($ventasTienda->sum('ingresos'), 2) }}</td>
                            <td class="text-end">S/ {{ number_format($ventasTienda->sum('costos'), 2) }}</td>
                            <td class="text-end text-success">S/ {{ number_format($ventasTienda->sum('ganancia'), 2) }}</td>
                            <td class="text-center">
                                @php
                                $totIngresos = $ventasTienda->sum('ingresos');
                                $totCostos = $ventasTienda->sum('costos');
                                $totGanancia = $ventasTienda->sum('ganancia');
                                $totMargen = $totCostos > 0 ? ($totGanancia / $totCostos) * 100 : 0;
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

    <!-- Desglose de Transferencias por Banco / Método de Pago -->
    @include('analytics.components.tienda.components.desglose_pagos')

    <!-- Rendimiento por SKU / Producto -->
    @include('analytics.components.tienda.components.tienda_sku')

    <!-- Scripts del gráfico -->
    @include('analytics.components.tienda.logic.scripts')