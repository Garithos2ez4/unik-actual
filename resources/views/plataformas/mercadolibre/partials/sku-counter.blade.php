@php
$skuCounter = [];
foreach ($orders as $order) {
foreach ($order->items as $item) {
$sku = $item->seller_sku ?: 'Sin SKU';
$skuCounter[$sku] = ($skuCounter[$sku] ?? 0) + ($item->quantity ?? 1);
}
}
arsort($skuCounter);
$totalUnidades = array_sum($skuCounter);
$totalSkus = count($skuCounter);
@endphp

@if($totalSkus > 0)
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2 px-3"
        style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#skuCounterPanel" aria-expanded="false">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-fill" style="color:#FFE600"></i>
            <span class="fw-bold text-dark small">Resumen de SKUs</span>
            <span class="badge bg-primary rounded-pill">{{ $totalSkus }} SKU{{ $totalSkus != 1 ? 's' : '' }}</span>
            <span class="badge bg-secondary rounded-pill">{{ $totalUnidades }} ud{{ $totalUnidades != 1 ? 's' : '' }} totales</span>
        </div>
        <i class="bi bi-chevron-down text-muted small"></i>
    </div>
    <div class="collapse" id="skuCounterPanel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-3">SKU Seller</th>
                            <th class="text-center" style="width:120px;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($skuCounter as $sku => $qty)
                        <tr>
                            <td class="ps-3">
                                <span class="fw-bold text-dark small font-monospace">{{ $sku }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 {{ $qty > 1 ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $qty }} ud{{ $qty != 1 ? 's' : '' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td class="ps-3 text-muted text-uppercase">Total</td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-dark px-3">{{ $totalUnidades }} uds</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endif