<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="row">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Tendencia de Ventas (Mes Actual)</h5>
                    <small class="text-muted">Egresos diarios registrados en el mes de {{ now()->translatedFormat('F') }}.</small>
                </div>
            </div>
            <div style="height: 380px; width: 100%; position: relative;">
                <canvas id="salesTrendChartPage"></canvas>
            </div>
        </div>
        <div class="col-lg-3 d-flex flex-column justify-content-center border-start ps-lg-4 mt-4 mt-lg-0">
            <div class="p-3 bg-light rounded-4 mb-3 border">
                <h6 class="text-secondary small mb-1 uppercase fw-bold">Ingresos Mes</h6>
                <h5 class="fw-bold text-primary mb-0">S/ {{ number_format(collect($ventasMes)->sum('monto'), 2) }}</h5>
            </div>
            <div class="p-3 bg-light rounded-4 mb-3 border">
                <h6 class="text-secondary small mb-1 uppercase fw-bold">Promedio Diario</h6>
                <h5 class="fw-bold text-success mb-0">S/ {{ number_format(collect($ventasMes)->where('monto', '>', 0)->avg('monto') ?? 0, 2) }}</h5>
            </div>
            <div class="p-3 bg-light rounded-4 border">
                <h6 class="text-secondary small mb-1 uppercase fw-bold">Día con mayor ingreso</h6>
                @php
                    $pico = collect($ventasMes)->sortByDesc('monto')->first();
                @endphp
                <h5 class="fw-bold text-warning mb-0">S/ {{ number_format($pico['monto'] ?? 0, 2) }} <small class="fs-6 fw-normal text-muted">({{ $pico['fecha'] ?? '-' }})</small></h5>
            </div>
        </div>
    </div>
</div>
