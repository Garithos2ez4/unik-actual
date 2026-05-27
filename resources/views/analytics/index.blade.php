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
            <a href="{{ route('dashboard.analitica.falabella') }}" class="btn btn-warning rounded-pill px-4 me-2">
                <i class="bi bi-shop me-1"></i> Detalle Falabella
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    @include('analytics.partials.topcontrols')

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