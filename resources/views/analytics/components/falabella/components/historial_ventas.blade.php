<!-- Tabla de Ventas -->
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-table me-2 text-primary"></i>Historial de Operaciones
        </h5>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="buscarHistorialFalabella" class="form-control border-start-0 bg-light" placeholder="Buscar orden o modelo...">
            </div>
            <button class="btn btn-light rounded-circle border shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHistorial" aria-expanded="true" aria-controls="collapseHistorial" id="btnToggleHistorial" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-chevron-up"></i>
            </button>
        </div>
    </div>
    
    <div class="collapse show" id="collapseHistorial">
        <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaFalabella">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-secondary">Nro. Orden</th>
                        <th class="text-uppercase small fw-bold text-secondary">Fecha</th>
                        <th class="text-uppercase small fw-bold text-secondary">Vendedor</th>
                        <th class="text-uppercase small fw-bold text-secondary">Producto(s)</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Ingresos</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Costos Base</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Comisión F.</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Ganancia Neta</th>
                        <th class="text-uppercase small fw-bold text-secondary text-center">Margen (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventasFalabella as $venta)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $venta->numeroOrden ?: 'S/N' }}</span></td>
                        <td class="text-muted small">{{ \Carbon\Carbon::parse($venta->fechaVenta)->format('d/m/Y') }}</td>
                        <td><span class="badge bg-secondary opacity-75"><i class="bi bi-person me-1"></i>{{ $venta->nombre_usuario ?? 'N/A' }}</span></td>
                        <td>
                            <div class="text-truncate" style="max-width: 250px;" title="{{ $venta->modelo }}">
                                {{ $venta->modelo }}
                            </div>
                        </td>
                        <td class="text-end fw-semibold text-dark">S/ {{ number_format($venta->ingresos, 2) }}</td>
                        <td class="text-end text-muted small">S/ {{ number_format($venta->costos - $venta->comision_falabella, 2) }}</td>
                        <td class="text-end text-danger small">-S/ {{ number_format($venta->comision_falabella, 2) }}</td>
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
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-light"></i>
                            No hay ventas registradas en Falabella.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold text-dark">
                    <tr>
                        <td colspan="4" class="text-end text-uppercase">Totales del Período:</td>
                        <td class="text-end">S/ {{ number_format($ventasFalabella->sum('ingresos'), 2) }}</td>
                        <td class="text-end">S/ {{ number_format($ventasFalabella->sum(fn($v) => $v->costos - $v->comision_falabella), 2) }}</td>
                        <td class="text-end text-danger">-S/ {{ number_format($ventasFalabella->sum('comision_falabella'), 2) }}</td>
                        <td class="text-end text-success">S/ {{ number_format($ventasFalabella->sum('ganancia'), 2) }}</td>
                        <td class="text-center">
                            @php
                                $totIngresos = $ventasFalabella->sum('ingresos');
                                $totGanancia = $ventasFalabella->sum('ganancia');
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
</div>
