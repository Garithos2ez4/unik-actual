<div class="card border-0 shadow-sm rounded-4 mt-4 h-100">
    <!-- Encabezado corporativo -->
    <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4">
        <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">
            <i class="bi bi-graph-up-arrow me-2 text-success"></i>Top 5: Mayor Rentabilidad
        </h5>
        <p class="text-secondary small mb-0 mt-1">Productos con mayor impacto en la ganancia neta</p>
    </div>

    <div class="card-body p-0">
        {{-- Encontramos la ganancia máxima para calcular el 100% de la barra --}}
        @php
        $maxGanancia = $skusMayorRentabilidad->max('ganancia');
        $maxGanancia = $maxGanancia > 0 ? $maxGanancia : 1; // Prevenir división por cero
        @endphp

        <div class="table-responsive">
            <table class="table table-borderless table-hover align-middle mb-0">
                <thead class="table-light border-bottom">
                    <tr>
                        <th class="text-uppercase text-muted small fw-semibold ps-4">SKU / Modelo</th>
                        <th class="text-uppercase text-muted small fw-semibold" style="width: 50%;">Volumen de Ganancia</th>
                        <th class="text-uppercase text-muted small fw-semibold text-end pe-4">Margen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skusMayorRentabilidad as $sku)
                    @php
                    // Si la ganancia es negativa, la barra se queda en 0. Si es positiva, calcula la proporción.
                    $porcentaje = $sku->ganancia > 0 ? ($sku->ganancia / $maxGanancia) * 100 : 0;
                    @endphp
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.03);">
                        <!-- SKU -->
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="{{ $sku->sku }}">
                                {{ $sku->sku ?: 'Sin modelo' }}
                            </div>
                        </td>

                        <!-- Barra de Ganancia (Data Bar) -->
                        <td class="py-3 pe-4">
                            <div class="d-flex align-items-center">
                                <!-- Monto en Soles -->
                                <span class="fw-bold {{ $sku->ganancia < 0 ? 'text-danger' : 'text-success' }} me-3 text-end" style="width: 85px; font-size: 0.95rem;">
                                    S/ {{ number_format($sku->ganancia, 2) }}
                                </span>
                                <!-- Barra proporcional -->
                                <div class="progress flex-grow-1 bg-light rounded-pill" style="height: 6px;">
                                    <div class="progress-bar {{ $sku->ganancia < 0 ? 'bg-danger' : 'bg-success' }} rounded-pill" role="progressbar"
                                        style="width: {{ $porcentaje }}%;"
                                        aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Margen (Mantenemos los badges sutiles porque se ven muy bien para porcentajes) -->
                        <td class="text-end pe-4 py-3">
                            @if($sku->margen > 20)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.75rem;">{{ number_format($sku->margen, 1) }}%</span>
                            @elseif($sku->margen > 0)
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" style="font-size: 0.75rem;">{{ number_format($sku->margen, 1) }}%</span>
                            @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.75rem;">{{ number_format($sku->margen, 1) }}%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <!-- Empty State Moderno -->
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <div class="p-3 bg-light rounded-circle d-inline-block mb-3">
                                <i class="bi bi-graph-up fs-2 text-secondary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Sin datos de rentabilidad</h6>
                            <p class="text-muted small mb-0">No hay ventas registradas para este período.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
