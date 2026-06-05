<div class="card border-0 shadow-sm rounded-4 mt-4 h-100">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4">
        <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">
            <i class="bi bi-star-fill me-2 text-primary"></i>Top 5: Productos Estrella
        </h5>
        <p class="text-secondary small mb-0 mt-1">Mayor volumen de rotación en el período</p>
    </div>

    <div class="card-body p-0">
        {{-- Calculamos cuál es el máximo de unidades para que su barra sea el 100% y las demás sean proporcionales --}}
        @php
        $maxUnidades = $skusMayorRotacion->max('total_unidades');
        $maxUnidades = $maxUnidades > 0 ? $maxUnidades : 1; // Prevenir división por cero
        @endphp

        <div class="table-responsive">
            <table class="table table-borderless table-hover align-middle mb-0">
                <thead class="table-light border-bottom">
                    <tr>
                        <th class="text-uppercase text-muted small fw-semibold ps-4">SKU / Modelo</th>
                        <th class="text-uppercase text-muted small fw-semibold" style="width: 45%;">Volumen de Ventas</th>
                        <th class="text-uppercase text-muted small fw-semibold text-end pe-4">Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skusMayorRotacion as $sku)
                    @php
                    // Calculamos el porcentaje de la barra
                    $porcentaje = ($sku->total_unidades / $maxUnidades) * 100;
                    @endphp
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.03);">
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="{{ $sku->sku }}">
                                {{ $sku->sku ?: 'Sin modelo' }}
                            </div>
                        </td>

                        <td class="py-3 pe-4">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-dark me-3 text-end" style="width: 45px; font-size: 0.9rem;">
                                    {{ $sku->total_unidades }} <span class="fw-normal text-muted" style="font-size: 0.75rem;">u.</span>
                                </span>
                                <div class="progress flex-grow-1 bg-light rounded-pill" style="height: 6px;">
                                    <div class="progress-bar bg-primary rounded-pill" role="progressbar"
                                        style="width: {{ $porcentaje }}%;"
                                        aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="text-end pe-4 py-3">
                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                S/ {{ number_format($sku->ingresos, 2) }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <div class="p-3 bg-light rounded-circle d-inline-block mb-3">
                                <i class="bi bi-bar-chart fs-2 text-secondary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Sin datos de rotación</h6>
                            <p class="text-muted small mb-0">No hay ventas registradas para este período.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
