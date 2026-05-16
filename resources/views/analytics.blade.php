@extends('layouts.app')

@section('title', 'Análisis Detallado')

@section('content')
<div class="container pb-5">
    <div class="row mt-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Análisis Detallado de Ventas</h2>
            <p class="text-secondary">Visualización profunda de tendencias, fallas y rendimiento operativo.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Gráfico de Tendencia -->
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="row">
                    <div class="col-lg-9">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold mb-0">Tendencia de Ventas (Últimos 7 Días)</h5>
                                <small class="text-muted">Egresos diarios registrados en la semana actual.</small>
                            </div>
                        </div>
                        <div style="height: 380px; width: 100%; position: relative;">
                            <canvas id="salesTrendChartPage"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-3 d-flex flex-column justify-content-center border-start ps-lg-4 mt-4 mt-lg-0">
                        <div class="p-3 bg-light rounded-4 mb-3 border">
                            <h6 class="text-secondary small mb-1 uppercase fw-bold">Ingresos Semana</h6>
                            <h3 class="fw-bold text-primary mb-0">S/ {{ number_format(collect($ventas7Dias)->sum('monto'), 2) }}</h3>
                        </div>
                        <div class="p-3 bg-light rounded-4 mb-3 border">
                            <h6 class="text-secondary small mb-1 uppercase fw-bold">Promedio Diario</h6>
                            <h3 class="fw-bold text-success mb-0">S/ {{ number_format(collect($ventas7Dias)->avg('monto'), 2) }}</h3>
                        </div>
                        <div class="p-3 bg-light rounded-4 border">
                            <h6 class="text-secondary small mb-1 uppercase fw-bold">Día con mayor ingreso</h6>
                            @php
                                $pico = collect($ventas7Dias)->sortByDesc('monto')->first();
                            @endphp
                            <h3 class="fw-bold text-warning mb-0">S/ {{ number_format($pico['monto'] ?? 0, 2) }} <small class="fs-6 fw-normal text-muted">({{ $pico['fecha'] ?? '-' }})</small></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Fallas Detallado -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Productos con Fallas</h5>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">Top 10</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">Producto / Modelo</th>
                                <th class="text-center border-0">Incidencias</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productosConFallas as $falla)
                            <tr>
                                <td class="border-0">
                                    <div class="fw-bold text-dark">{{ $falla->nombreProducto }}</div>
                                    <small class="text-muted">{{ $falla->modelo }}</small>
                                </td>
                                <td class="text-center border-0">
                                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold" style="min-width: 45px;">{{ $falla->total_fallas }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center py-4 text-muted">No se registraron fallas con observaciones.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top SKUs Mes -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-star-fill text-warning me-2"></i>SKUs con Mayor Rotación</h5>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3">Mes Actual</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">SKU / Identificador</th>
                                <th class="text-center border-0">Ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($skusMostSoldMonth as $sku)
                            <tr>
                                <td class="border-0">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 250px;" title="{{ $sku->sku }}">{{ $sku->sku }}</div>
                                    <small class="text-muted text-truncate d-block" style="max-width: 250px;" title="{{ $sku->titulo }}">{{ $sku->titulo }}</small>
                                </td>
                                <td class="text-center border-0">
                                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold" style="min-width: 45px;">{{ $sku->total_ventas }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center py-4 text-muted">Aún no hay ventas registradas este mes.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('salesTrendChartPage');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const labels = @json(collect($ventas7Dias)->pluck('fecha'));
        const dataMonto = @json(collect($ventas7Dias)->pluck('monto'));
        const dataUnidades = @json(collect($ventas7Dias)->pluck('total'));

        // Gradiente para el fondo
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(0, 177, 185, 0.3)');
        gradient.addColorStop(1, 'rgba(0, 177, 185, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: dataMonto,
                    borderColor: '#00b1b9',
                    backgroundColor: gradient,
                    borderWidth: 4,
                    fill: true,
                    tension: 0.45,
                    pointRadius: 6,
                    pointHoverRadius: 9,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#00b1b9',
                    pointBorderWidth: 3,
                    cubicInterpolationMode: 'monotone'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2d3436',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const units = dataUnidades[context.dataIndex];
                                return [
                                    ' Ingreso: S/ ' + context.parsed.y.toLocaleString('es-PE', { minimumFractionDigits: 2 }),
                                    ' Unidades: ' + units
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' }, color: '#636e72' }
                    },
                    y: { 
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false },
                        ticks: { 
                            color: '#636e72',
                            callback: (value) => 'S/ ' + value.toLocaleString()
                        }
                    }
                }
            }
        });
    });
</script>

<style>
    .bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1) !important; }
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
    .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
    .rounded-4 { border-radius: 1rem !important; }
</style>
@endsection
