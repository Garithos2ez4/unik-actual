@extends('layouts.app')

@section('title', 'Resumen Ejecutivo')

@section('content')
<div class="container pb-5">
    <div class="row mt-4 mb-5 align-items-center">
        <div class="col-md-5">
            <h2 class="fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Resumen Ejecutivo</h2>
            <p class="text-secondary mb-0">Análisis detallado de ventas, tendencias y rendimiento operativo.</p>
        </div>
        <div class="col-md-7">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="{{ route('dashboard.analitica.tienda') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-shop me-1"></i> Tienda
                </a>
                <a href="{{ route('dashboard.analitica.falabella') }}" class="btn btn-warning rounded-pill px-4 shadow-sm">
                    <i class="bi bi-shop me-1"></i> Falabella
                </a>
                <a href="{{ route('dashboard.analitica.mercadolibre') }}" class="btn btn-info text-white rounded-pill px-4 shadow-sm">
                    <i class="bi bi-shop me-1"></i> Mercado Libre
                </a>
                <a href="{{ route('dashboard.analitica.ripley') }}" class="btn rounded-pill px-4 shadow-sm" style="background-color: #6a1b9a; color: white;">
                    <i class="bi bi-shop me-1"></i> Ripley
                </a>
                <a href="{{ route('dashboard.analitica.envios') }}" class="btn btn-secondary rounded-pill px-4 shadow-sm text-white">
                    <i class="bi bi-truck me-1"></i> Envíos
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    @include('analytics.partials.topcontrols')

    <div class="row mt-2">
        <div class="col-lg-12">
            @include('analytics.partials.trends')
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-6">
            @include('analytics.partials.top_products_revenue')
        </div>
        <div class="col-lg-6">
            @include('analytics.partials.top_products_qty')
        </div>
    </div>

    @include('analytics.partials.margins')

    <div class="row mt-2">
        <div class="col-lg-6">
            @include('analytics.partials.platforms')
        </div>
        <div class="col-lg-6">
            @include('analytics.partials.top_skus')
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-6">
            @include('analytics.partials.top_enviados')
        </div>
        <div class="col-lg-6">
            @include('analytics.partials.best_months')
        </div>
    </div>
</div>

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