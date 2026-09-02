<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="row">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0">Tendencia de Envíos</h5>
                    <small class="text-muted">{{ $filtros['fecha_inicio']->translatedFormat('d M Y') }} — {{ $filtros['fecha_fin']->translatedFormat('d M Y') }}</small>
                </div>
            </div>
            <div style="height: 380px; width: 100%; position: relative;">
                <canvas id="enviosTrendChartPage"></canvas>
            </div>
        </div>
        <div class="col-lg-3 d-flex flex-column justify-content-center border-start ps-lg-4 mt-4 mt-lg-0">
            <div class="p-3 bg-light rounded-4 mb-3 border">
                <h6 class="text-secondary small mb-1 text-uppercase fw-bold">Total Envíos Periodo</h6>
                <h5 class="fw-bold text-primary mb-0">{{ number_format(collect($enviosMes)->sum('total'), 0) }}</h5>
            </div>
            <div class="p-3 bg-light rounded-4 mb-3 border">
                <h6 class="text-secondary small mb-1 text-uppercase fw-bold">Promedio Diario</h6>
                <h5 class="fw-bold text-success mb-0">{{ number_format(collect($enviosMes)->where('total', '>', 0)->avg('total') ?? 0, 1) }}</h5>
            </div>
            <div class="p-3 bg-light rounded-4 border">
                <h6 class="text-secondary small mb-1 text-uppercase fw-bold">Día con más envíos</h6>
                @php
                    $pico = collect($enviosMes)->sortByDesc('total')->first();
                @endphp
                <h5 class="fw-bold text-warning mb-0">{{ number_format($pico['total'] ?? 0, 0) }} <small class="fs-6 fw-normal text-muted">({{ $pico['fecha'] ?? '-' }})</small></h5>
            </div>
        </div>
    </div>
</div>
