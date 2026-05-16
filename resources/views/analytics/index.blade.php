@extends('layouts.app')

@section('title', 'Análisis Detallado')

@section('content')
<div class="container pb-5">
    <!-- Encabezado -->
    <div class="row mt-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Análisis Detallado de Ventas</h2>
            <p class="text-secondary">Visualización profunda de tendencias, plataformas y rendimiento operativo.</p>
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
            @include('analytics.partials.trends')
        </div>
    </div>

    <div class="row mt-2">
        <!-- Rendimiento por Plataforma -->
        <div class="col-lg-6">
            @include('analytics.partials.platforms')
        </div>
        <!-- Productos con Mayor Ingreso (NUEVO) -->
        <div class="col-lg-6">
            @include('analytics.partials.top_products_revenue')
        </div>
    </div>

    <div class="row">
        <!-- Top Fallas Detallado -->
        <div class="col-lg-6">
            @include('analytics.partials.failures')
        </div>

        <!-- Top SKUs Mes -->
        <div class="col-lg-6">
            @include('analytics.partials.top_skus')
        </div>
    <div class="row mt-2">
        <!-- Hall of Fame: Mejores Meses Históricos -->
        <div class="col-lg-12">
            @include('analytics.partials.best_months')
        </div>
    </div>
</div>

<!-- Scripts y Estilos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@include('analytics.partials.scripts')

<style>
    .bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1) !important; }
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
    .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
    .rounded-4 { border-radius: 1rem !important; }
</style>
@endsection
