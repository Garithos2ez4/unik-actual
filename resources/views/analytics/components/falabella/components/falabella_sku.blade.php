<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-box-seam me-2 text-warning"></i>Rendimiento por SKU
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaFalabellaSku">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small fw-bold text-secondary ps-4">SKU / Modelo</th>
                        <th class="text-uppercase small fw-bold text-secondary text-center">Unidades</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Ingresos</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end">Deducciones (Costos + Com.)</th>
                        <th class="text-uppercase small fw-bold text-secondary text-end pe-4">Rentabilidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skusFalabella as $sku)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark text-truncate" style="max-width: 250px;" title="{{ $sku->sku }}">
                                {{ $sku->sku ?: 'Sin modelo' }}
                            </div>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 fs-6">{{ $sku->total_unidades }}</span>
                        </td>

                        <td class="text-end fw-semibold text-dark">
                            S/ {{ number_format($sku->ingresos, 2) }}
                        </td>

                        <td class="text-end">
                            <div class="d-flex flex-column align-items-end justify-content-center">
                                <span class="text-danger small mb-1" title="Comisión Falabella">
                                    -S/ {{ number_format($sku->comision_falabella, 2) }} <i class="bi bi-shop text-muted ms-1" style="font-size: 0.8rem;"></i>
                                </span>
                                <span class="text-muted" style="font-size: 0.75rem;" title="Costo Base">
                                    S/ {{ number_format($sku->costos, 2) }} <i class="bi bi-box text-muted ms-1" style="font-size: 0.8rem;"></i>
                                </span>
                            </div>
                        </td>

                        <td class="text-end pe-4">
                            <div class="d-flex flex-column align-items-end justify-content-center">
                                {{-- Evaluamos si la ganancia es negativa para pintarla de rojo (text-danger), sino será verde (text-success) --}}
                                <span class="fw-bold {{ $sku->ganancia < 0 ? 'text-danger' : 'text-success' }} mb-1">
                                    S/ {{ number_format($sku->ganancia, 2) }}
                                </span>

                                @if($sku->margen > 20)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">{{ number_format($sku->margen, 1) }}% Margen</span>
                                @elseif($sku->margen > 0)
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.7rem;">{{ number_format($sku->margen, 1) }}% Margen</span>
                                @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">{{ number_format($sku->margen, 1) }}% Margen</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-light"></i>
                            No hay productos registrados en Falabella para este período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>