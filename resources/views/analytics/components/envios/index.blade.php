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

    <div class="row">
        <div class="col-lg-4">
            @include('analytics.components.envios.components.top_provincias')
        </div>
        <div class="col-lg-4">
            @include('analytics.components.envios.components.top_clientes')
        </div>
        <div class="col-lg-4">
            @include('analytics.components.envios.components.top_agencias')
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Fetch Top Provincias
        fetch('{{ route("dashboard.analitica.envios.provincias") }}')
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#tabla-top-provincias tbody');
                tbody.innerHTML = ''; // Limpiar loader
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay datos disponibles</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">${index + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.provincia}</div>
                                    <small class="text-muted">${item.destino}</small>
                                </td>
                                <td class="text-end pe-4 fw-bold text-primary">${item.total}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error fetching top provincias:', error));

        // Fetch Top Clientes
        fetch('{{ route("dashboard.analitica.envios.clientes") }}')
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#tabla-top-clientes tbody');
                tbody.innerHTML = ''; // Limpiar loader
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay datos disponibles</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">${index + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.cliente}</div>
                                    <small class="text-muted">Doc: ${item.documento}</small>
                                </td>
                                <td class="text-end pe-4 fw-bold text-info">${item.total}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error fetching top clientes:', error));
            
        // Fetch Top Agencias
        fetch('{{ route("dashboard.analitica.envios.agencias") }}')
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#tabla-top-agencias tbody');
                tbody.innerHTML = ''; // Limpiar loader
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay datos disponibles</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">${index + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.agencia}</div>
                                    <span class="badge ${item.estado === 'Activo' ? 'bg-success' : 'bg-secondary'}">${item.estado}</span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-warning">${item.total}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error fetching top agencias:', error));
            
    });
</script>
@endsection
