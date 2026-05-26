@extends('layouts.app')

@section('title', 'Resumen Ejecutivo')

@section('content')
<div class="container pb-5">
    <!-- Encabezado -->
    <div class="row mt-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Resumen Ejecutivo</h2>
            <p class="text-secondary mb-0">Análisis detallado de ventas, tendencias y rendimiento operativo.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm rounded-4 mt-3 mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" action="{{ route('dashboard.analitica') }}" id="form-filtros-analytics">
                <div class="row align-items-end g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-event me-1"></i>Año</label>
                        <select name="anio" class="form-select form-select-sm" id="filtro-anio" onchange="actualizarLimitesDia()">
                            @for($y = now()->year; $y >= 2026; $y--)
                            <option value="{{ $y }}" {{ $filtros['anio'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-month me-1"></i>Mes</label>
                        <select name="mes" class="form-select form-select-sm" id="filtro-mes" onchange="actualizarLimitesDia()">
                            @php
                            $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                            @endphp
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $filtros['mes'] == $m ? 'selected' : '' }}>{{ $meses[$m-1] }}</option>
                                @endfor
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-plus me-1"></i>Día Inicio</label>
                        <input type="date" name="dia_inicio" class="form-control form-control-sm" id="filtro-dia-inicio"
                            value="{{ $filtros['dia_inicio'] ?? now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-check me-1"></i>Día Corte</label>
                        <input type="date" name="dia_fin" class="form-control form-control-sm" id="filtro-dia-fin"
                            value="{{ $filtros['dia_fin'] ?? now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-funnel-fill me-1"></i>Filtrar
                        </button>
                        <a href="{{ route('dashboard.analitica') }}" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Indicador de rango activo -->
    <div class="mb-3">
        <span class="badge bg-primary bg-opacity-10 text-succes
         border border-primary px-3 py-2 rounded-pill">
            <i class="bi bi-calendar-range me-1"></i>
            {{ $filtros['fecha_inicio']->translatedFormat('d M Y') }} — {{ $filtros['fecha_fin']->translatedFormat('d M Y') }}
        </span>
    </div>

    <div class="row mt-2">
        <!-- Gráfico de Tendencia -->
        <div class="col-lg-12">
            @include('analytics.partials.trends')
        </div>
    </div>

    <div class="row mt-2">
        <!-- Productos con Mayor Ingreso (Monto S/) -->
        <div class="col-lg-6">
            @include('analytics.partials.top_products_revenue')
        </div>
        <!-- Productos Más Vendidos (Cantidad Unidades) -->
        <div class="col-lg-6">
            @include('analytics.partials.top_products_qty')
        </div>
    </div>

    <!-- Márgenes de Ganancia -->
    @include('analytics.partials.margins')

    <div class="row mt-2">
        <!-- Rendimiento por Plataforma -->
        <div class="col-lg-6">
            @include('analytics.partials.platforms')
        </div>
        <!-- Top SKUs Mes -->
        <div class="col-lg-6">
            @include('analytics.partials.top_skus')
        </div>
    </div>

    <div class="row mt-2">
        <!-- Top Fallas Detallado -->
        <div class="col-lg-6">
            @include('analytics.partials.failures')
        </div>
        <!-- Hall of Fame: Mejores Meses Históricos -->
        <div class="col-lg-6">
            @include('analytics.partials.best_months')
        </div>
    </div>
</div>

<!-- Scripts y Estilos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@include('analytics.partials.scripts')

<style>
    .bg-danger-subtle {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    .bg-success-subtle {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }

    .uppercase {
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .rounded-4 {
        border-radius: 1rem !important;
    }
</style>

<script>
    function actualizarLimitesDia() {
        const anio = document.getElementById('filtro-anio').value;
        const mes = document.getElementById('filtro-mes').value;
        const diaInicio = document.getElementById('filtro-dia-inicio');
        const diaFin = document.getElementById('filtro-dia-fin');

        // Calcular primer y último día del mes seleccionado
        const primerDia = `${anio}-${String(mes).padStart(2, '0')}-01`;
        const ultimoDia = new Date(anio, mes, 0).getDate();
        const ultimaFecha = `${anio}-${String(mes).padStart(2, '0')}-${String(ultimoDia).padStart(2, '0')}`;

        diaInicio.min = primerDia;
        diaInicio.max = ultimaFecha;
        diaFin.min = primerDia;
        diaFin.max = ultimaFecha;

        // Si los valores actuales están fuera de rango, limpiarlos
        if (diaInicio.value && (diaInicio.value < primerDia || diaInicio.value > ultimaFecha)) {
            diaInicio.value = '';
        }
        if (diaFin.value && (diaFin.value < primerDia || diaFin.value > ultimaFecha)) {
            diaFin.value = '';
        }
    }

    // Inicializar límites al cargar
    document.addEventListener('DOMContentLoaded', actualizarLimitesDia);
</script>
@endsection