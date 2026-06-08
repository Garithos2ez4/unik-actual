@extends('layouts.app')

@section('title', 'Detalle Ripley')

@section('content')
<div class="container pb-5">
    <!-- Encabezado -->
    <div class="row mt-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold"><i class="bi bi-shop text-warning me-2"></i>Detalle de Ventas - Ripley</h2>
            <p class="text-secondary mb-0">Análisis específico de ventas, comisiones y márgenes en la plataforma Ripley.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('dashboard.analitica') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Volver a Analítica
            </a>
        </div>
    </div>

    <!-- Controles de Filtros -->
    @include('analytics.partials.topcontrols')

    <!-- Tendencias de Ventas -->
    @include('analytics.components.ripley.components.trends')

    <!-- Tabla de Ventas -->
    @include('analytics.components.ripley.components.historial_ventas')
</div>

@include('analytics.components.ripley.logic.scripts')
@endsection
