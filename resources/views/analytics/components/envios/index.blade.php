@extends('layouts.app')

@section('title', 'Analítica de Envíos')

@section('content')
<div class="container pb-5">
    
    <!-- Encabezado -->
    <div class="row mt-4 mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold"><i class="bi bi-truck me-2 text-secondary"></i>Detalle de Envíos</h2>
            <p class="text-secondary mb-0">Análisis y métricas relacionadas a los envíos, agencias y estados.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="{{ route('dashboard.analitica') }}" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver a Analítica
            </a>
        </div>
    </div>
    <!-- Filtros TopControls -->
    @include('analytics.partials.topcontrols')

    @include('analytics.components.envios.components.trendsenvios')

    <div class="row">
        <div class="col-lg-3">
            @include('analytics.components.envios.components.top_provincias')
        </div>
        <div class="col-lg-3">
            @include('analytics.components.envios.components.top_clientes')
        </div>
        <div class="col-lg-3">
            @include('analytics.components.envios.components.top_agencias')
        </div>
        <div class="col-lg-3">
            @include('analytics.components.envios.components.top_usuarios')
        </div>
</div>

@include('analytics.components.envios.logic.envios_scripts')

@endsection
