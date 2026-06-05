@extends('layouts.app')

@section('title', 'Detalle Tienda')

@section('content')
<div class="container pb-5">
    <!-- Encabezado -->
    <div class="row mt-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold"><i class="bi bi-shop text-primary me-2"></i>Detalle de Ventas - Tienda</h2>
            <p class="text-secondary mb-0">Análisis específico de ventas directas realizadas en tienda física/web (sin intermediación de plataformas).</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('dashboard.analitica') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Volver a Analítica
            </a>
        </div>
    </div>

    <!-- Controles de Filtros -->
    @include('analytics.partials.topcontrols')

    <!-- Contenedor Dinámico -->
    <div id="tienda-data-container" class="mt-4">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Cargando métricas...</span>
            </div>
            <h5 class="mt-3 text-muted">Cargando métricas de tienda...</h5>
            <p class="small text-muted">Obteniendo ventas y agrupando métodos de pago</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById('tienda-data-container');
        
        // Obtenemos los filtros de la URL (si existen)
        const params = new URLSearchParams(window.location.search);
        const endpoint = `{{ route('dashboard.analitica.tienda.data') }}?${params.toString()}`;

        fetch(endpoint)
            .then(response => {
                if (!response.ok) throw new Error('Error en la petición');
                return response.text();
            })
            .then(html => {
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error cargando datos de tienda:', error);
                container.innerHTML = `
                    <div class="alert alert-danger text-center mt-4">
                        <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                        Ocurrió un error al cargar las métricas. Por favor, recarga la página.
                    </div>`;
            });
    });
</script>
@endsection
