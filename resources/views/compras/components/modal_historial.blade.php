<!-- ========================================== -->
<!-- MODAL: HISTORIAL DE ACTUALIZACIONES        -->
<!-- ========================================== -->
<div class="modal fade" id="historialModal" tabindex="-1" aria-labelledby="historialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="historialModalLabel">
                    <i class="bi bi-clock-history me-2"></i> Historial de Actualizaciones (Stock Proveedor)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-light sticky-top">
                        <tr class="small text-uppercase text-secondary">
                            <th>Fecha y Hora</th>
                            <th>Modo / Categoría</th>
                            <th class="text-center">Encontrados</th>
                            <th class="text-center">Actualizados en BD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $log)
                        <tr>
                            <td class="text-muted small">
                                <i class="bi bi-calendar3 me-1"></i> {{ $log->fecha_ejecucion->format('d/m/Y h:i A') }}
                            </td>
                            <td>
                                @if(str_contains(strtoupper($log->keyword), 'API'))
                                <span class="badge bg-success-subtle text-success border border-success font-monospace">
                                    <i class="bi bi-cloud-check-fill me-1"></i> {{ $log->keyword }}
                                </span>
                                @else
                                <span class="badge bg-secondary font-monospace">{{ $log->keyword }}</span>
                                @endif
                            </td>
                            <td class="text-center fw-semibold">{{ number_format($log->productos_encontrados) }}</td>
                            <td class="text-center text-success fw-bold">
                                +{{ number_format($log->productos_actualizados) }}
                                @if($log->productos_actualizados > 0 && !empty($log->detalles_json))
                                <button class="btn btn-sm btn-link text-decoration-none py-0 ms-1 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#detalles-{{ $log->idDetalle }}">
                                    (Ver)
                                </button>
                                @endif
                            </td>
                        </tr>
                        @if($log->productos_actualizados > 0 && !empty($log->detalles_json))
                        <tr class="collapse bg-light" id="detalles-{{ $log->idDetalle }}">
                            <td colspan="4" class="p-3">
                                <h6 class="mb-2 text-primary small fw-bold">Productos Actualizados en BD:</h6>
                                <div class="d-flex flex-wrap gap-1" style="max-height: 180px; overflow-y: auto;">
                                    @foreach($log->detalles_json as $detalle)
                                    <span class="badge bg-white text-dark border font-monospace small py-1 px-2">
                                        <i class="bi bi-check2 text-success me-1"></i> {{ $detalle['part_number'] ?? ($detalle['sku'] ?? 'N/A') }}
                                        @if(isset($detalle['stock']))
                                        <span class="badge bg-success ms-1">{{ $detalle['stock'] }}</span>
                                        @endif
                                    </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No hay registros de actualizaciones aún.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
