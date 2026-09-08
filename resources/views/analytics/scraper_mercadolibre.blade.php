@extends('layouts.app')

@section('title', 'Verificador de Precios - Mercado Libre')

@section('content')
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-12 col-md-8">
            <h2 class="fw-bold text-dark"><i class="bi bi-graph-up-arrow text-warning"></i> Monitor de Precios Mercado Libre</h2>
            <p class="text-muted">Consulta las referencias de precios de la competencia para tus productos publicados en Mercado Libre usando la API oficial.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-4 bg-light">
            <form id="scraper-form">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-8">
                        <label for="modelo" class="form-label fw-bold small text-secondary">Filtrar por título (opcional)</label>
                        <input type="text" class="form-control form-control-lg border-warning shadow-sm" id="modelo" placeholder="Ej. Monitor Teros, Laptop Lenovo... (vacío = todos)" autocomplete="off">
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-warning btn-lg w-100 shadow fw-bold text-dark" id="btn-buscar">
                            <i class="bi bi-search"></i> Consultar Precios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="text-center py-5 d-none">
        <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="mt-3 text-muted">Consultando referencias de precios en Mercado Libre...</h5>
        <p class="text-muted small">Esto puede tardar unos segundos según la cantidad de productos.</p>
    </div>

    <!-- Error State -->
    <div id="error-state" class="alert alert-danger shadow-sm d-none">
        <i class="bi bi-exclamation-triangle-fill"></i> <span id="error-message"></span>
    </div>

    <!-- Results Section -->
    <div id="results-section" class="d-none">
        <div class="row mb-4">
            <div class="col-12 col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #ffc107 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-box-seam"></i> Total con Referencias</h6>
                        <h2 class="mb-0 fw-bold text-warning" id="res-total">0</h2>
                        <small class="text-muted">Mostrando <span id="res-mostrando">0</span> productos</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #dc3545 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-exclamation-triangle"></i> Precio Alto</h6>
                        <h2 class="mb-0 fw-bold text-danger" id="res-altos">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #198754 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-check-circle"></i> Precio Competitivo</h6>
                        <h2 class="mb-0 fw-bold text-success" id="res-competitivos">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-columns-reverse text-warning"></i> Referencias de Precios</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Producto</th>
                                <th>Estado</th>
                                <th class="text-end">Tu Precio</th>
                                <th class="text-end">Precio Más Bajo</th>
                                <th class="text-end">Diferencia</th>
                                <th class="text-center">Competidores</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-resultados">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('scraper-form');
        const btnBuscar = document.getElementById('btn-buscar');
        const loadingState = document.getElementById('loading-state');
        const errorState = document.getElementById('error-state');
        const errorMessage = document.getElementById('error-message');
        const resultsSection = document.getElementById('results-section');
        const tbodyResultados = document.getElementById('tbody-resultados');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const modelo = document.getElementById('modelo').value.trim();
            
            // Mostrar loading
            btnBuscar.disabled = true;
            loadingState.classList.remove('d-none');
            errorState.classList.add('d-none');
            resultsSection.classList.add('d-none');
            tbodyResultados.innerHTML = '';
            
            const url = `/api/verificar-precio-mercadolibre?modelo=${encodeURIComponent(modelo)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    btnBuscar.disabled = false;
                    loadingState.classList.add('d-none');
                    
                    if(!data.success) {
                        errorMessage.textContent = data.message || 'Ocurrió un error desconocido.';
                        errorState.classList.remove('d-none');
                        return;
                    }
                    
                    if(!data.productos || data.productos.length === 0) {
                        errorMessage.textContent = data.message || 'No se encontraron referencias de precios para tus productos.';
                        errorState.classList.remove('d-none');
                        return;
                    }
                    
                    // Llenar resumen
                    document.getElementById('res-total').textContent = data.total;
                    document.getElementById('res-mostrando').textContent = data.mostrando;
                    
                    let altos = 0, competitivos = 0;
                    
                    // Llenar tabla
                    data.productos.forEach((prod, index) => {
                        const tr = document.createElement('tr');
                        
                        if(prod.status === 'no_benchmark_ok' || prod.status === 'no_benchmark_lowest') {
                            tr.classList.add('table-success');
                            competitivos++;
                        } else if(prod.status === 'with_benchmark_highest') {
                            tr.classList.add('table-danger');
                            altos++;
                        } else if(prod.status === 'with_benchmark_high') {
                            tr.classList.add('table-warning');
                            altos++;
                        }

                        const diffColor = prod.diferencia_porcentaje > 20 ? 'text-danger' : (prod.diferencia_porcentaje > 0 ? 'text-warning' : (prod.status === 'no_data' ? 'text-muted' : 'text-success'));
                        const diffSign = prod.diferencia_porcentaje > 0 ? '+' : '';
                        const diffValue = prod.status === 'no_data' ? '-' : `${diffSign}${Math.round(prod.diferencia_porcentaje)}%`;
                        const lowestPriceDisplay = prod.precio_mas_bajo > 0 ? `S/ ${parseFloat(prod.precio_mas_bajo).toFixed(2)}` : '-';
                        
                        tr.innerHTML = `
                            <td class="ps-4 fw-bold text-muted">${index + 1}</td>
                            <td>
                                <div class="fw-semibold">${prod.titulo || prod.item_id}</div>
                                <a href="https://articulo.mercadolibre.com.pe/${prod.item_id.replace('MPE', 'MPE-')}" target="_blank" class="text-decoration-none small">
                                    <i class="bi bi-box-arrow-up-right"></i> ${prod.item_id}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-${prod.status_color}">${prod.status_label}</span>
                            </td>
                            <td class="text-end fw-bold fs-6">S/ ${parseFloat(prod.precio_actual).toFixed(2)}</td>
                            <td class="text-end fw-bold fs-6 text-primary">${lowestPriceDisplay}</td>
                            <td class="text-end fw-bold ${diffColor}">${diffValue}</td>
                            <td class="text-center"><span class="badge bg-secondary">${prod.competidores > 0 ? prod.competidores : '-'}</span></td>
                        `;
                        tbodyResultados.appendChild(tr);
                    });
                    
                    document.getElementById('res-altos').textContent = altos;
                    document.getElementById('res-competitivos').textContent = competitivos;
                    
                    resultsSection.classList.remove('d-none');
                })
                .catch(error => {
                    btnBuscar.disabled = false;
                    loadingState.classList.add('d-none');
                    errorMessage.textContent = 'Ocurrió un error al consultar el servicio: ' + error.message;
                    errorState.classList.remove('d-none');
                });
        });
    });
</script>
@endsection
