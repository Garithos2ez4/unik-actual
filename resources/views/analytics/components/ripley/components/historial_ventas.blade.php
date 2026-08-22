    <!-- Tabla de Ventas -->
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table me-2 text-primary"></i>Historial de Operaciones
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaRipley">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-bold text-secondary"># Venta</th>
                            <th class="text-uppercase small fw-bold text-secondary">Fecha</th>
                            <th class="text-uppercase small fw-bold text-secondary">Vendedor</th>
                            <th class="text-uppercase small fw-bold text-secondary">Producto(s)</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Ingresos</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Costos Base</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Comisión R.</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end" title="Tarifa por peso del paquete logístico (Primera milla)">Tarifa Peso</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Ganancia Neta</th>
                            <th class="text-uppercase small fw-bold text-secondary text-center">Margen (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventasRipley as $venta)
                        <tr>
                            <td><span class="badge bg-light text-dark border">V-{{ str_pad($venta->idVenta, 5, '0', STR_PAD_LEFT) }}</span></td>
                            <td class="text-muted small">{{ \Carbon\Carbon::parse($venta->fechaVenta)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-secondary opacity-75"><i class="bi bi-person me-1"></i>{{ $venta->nombre_usuario ?? 'N/A' }}</span></td>
                            <td>
                                <div class="text-truncate" style="max-width: 250px;" title="{{ $venta->modelo }}">
                                    {{ $venta->modelo }}
                                </div>
                            </td>
                            <td class="text-end fw-semibold text-dark">S/ {{ number_format($venta->ingresos, 2) }}</td>
                            <td class="text-end text-muted small">S/ {{ number_format($venta->costos, 2) }}</td>
                            <td class="text-end text-danger small">-S/ {{ number_format($venta->comision_ripley, 2) }}</td>
                            <td class="text-end text-danger small">-S/ {{ number_format($venta->tarifa_peso_ripley, 2) }}</td>
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
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-light"></i>
                                No hay ventas registradas en Ripley.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold text-dark">
                        <tr>
                            <td colspan="4" class="text-end text-uppercase">Totales del Período:</td>
                            <td class="text-end text-dark">S/ {{ number_format($ventasRipley->sum('ingresos'), 2) }}</td>
                            <td class="text-end text-dark">S/ {{ number_format($ventasRipley->sum('costos'), 2) }}</td>
                            <td class="text-end text-danger">-S/ {{ number_format($ventasRipley->sum('comision_ripley'), 2) }}</td>
                            <td class="text-end text-danger">-S/ {{ number_format($ventasRipley->sum('tarifa_peso_ripley'), 2) }}</td>
                            <td class="text-end text-success">S/ {{ number_format($ventasRipley->sum('ganancia'), 2) }}</td>
                            <td class="text-center">
                                @php
                                $totIngresos = $ventasRipley->sum('ingresos');
                                $totGanancia = $ventasRipley->sum('ganancia');
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