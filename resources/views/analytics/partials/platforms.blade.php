<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex align-items-center mb-3">
            <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                <i class="bi bi-shop text-primary fs-5"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">Rendimiento por Plataforma</h5>
                <small class="text-muted">{{ $filtros['fecha_inicio']->translatedFormat('d M Y') }} — {{ $filtros['fecha_fin']->translatedFormat('d M Y') }}</small>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-sistema-uno text-white">
                    <tr>
                        <th class="ps-4 py-3 border-0 small uppercase fw-bold">Plataforma</th>
                        <th class="py-3 border-0 small uppercase fw-bold text-center">Pedidos</th>
                        <th class="py-3 border-0 small uppercase fw-bold text-end">Ingresos (S/)</th>
                        <th class="pe-4 py-3 border-0 small uppercase fw-bold text-end">Cuota</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $granTotal = $metricasPlataformas->sum('total_monto');
                    @endphp
                    @foreach($metricasPlataformas as $meta)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-light rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    @if(str_contains(strtoupper($meta->plataforma), 'FALABELLA'))
                                        <i class="bi bi-handbag-fill text-success"></i>
                                    @elseif(str_contains(strtoupper($meta->plataforma), 'MERCADO'))
                                        <i class="bi bi-handbag-fill text-warning"></i>
                                    @else
                                        <i class="bi bi-shop-window text-primary"></i>
                                    @endif
                                </div>
                                <span class="fw-bold text-dark">{{ $meta->plataforma }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-normal px-3">{{ $meta->total_pedidos }}</span>
                        </td>
                        <td class="text-end fw-bold">
                            S/ {{ number_format($meta->total_monto, 2) }}
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                <span class="small text-muted me-2">{{ $granTotal > 0 ? number_format(($meta->total_monto / $granTotal) * 100, 1) : 0 }}%</span>
                                <div class="progress w-50" style="height: 6px;">
                                    <div class="progress-bar bg-primary rounded-pill" role="progressbar" 
                                         style="width: {{ $granTotal > 0 ? ($meta->total_monto / $granTotal) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if($metricasPlataformas->count() > 1)
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td class="ps-4 py-3 border-0">TOTAL</td>
                        <td class="py-3 border-0 text-center">{{ $metricasPlataformas->sum('total_pedidos') }}</td>
                        <td class="py-3 border-0 text-end">S/ {{ number_format($granTotal, 2) }}</td>
                        <td class="pe-4 py-3 border-0 text-end">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
