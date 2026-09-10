@extends('layouts.app')

@section('title', 'Verificador de Precios - Ripley')

@section('content')
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-12 col-md-8">
            <h2 class="fw-bold text-dark"><i class="bi bi-graph-up-arrow" style="color: #4f2e96;"></i> Monitor de Precios Ripley</h2>
            <p class="text-muted">Consulta las referencias de precios de la competencia para tus productos publicados en Ripley.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-4 bg-light">
            <form id="scraper-form">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-8">
                        <label for="modelo" class="form-label fw-bold small text-secondary">Buscar modelo o producto</label>
                        <input type="text" class="form-control form-control-lg shadow-sm" style="border-color: #4f2e96;" id="modelo" placeholder="Ej. TE-2417..." autocomplete="off" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-lg w-100 shadow fw-bold text-white" style="background-color: #4f2e96; border-color: #4f2e96;" id="btn-buscar">
                            <i class="bi bi-search"></i> Consultar Precios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="text-center py-5 d-none">
        <div class="spinner-border" style="color: #4f2e96; width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="mt-3 text-muted">Consultando referencias de precios en Ripley...</h5>
        <p class="text-muted small">Esto puede tardar unos segundos.</p>
    </div>

    <!-- Error State -->
    <div id="error-state" class="alert alert-danger shadow-sm d-none">
        <i class="bi bi-exclamation-triangle-fill"></i> <span id="error-message"></span>
    </div>

    <!-- Results Section -->
    <div id="results-section" class="d-none">
        <div class="row mb-4">
            <div class="col-12 col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #4f2e96 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-box-seam"></i> Total Encontrados</h6>
                        <h2 class="mb-0 fw-bold" style="color: #4f2e96;" id="res-total">0</h2>
                        <small class="text-muted">Mostrando <span id="res-mostrando">0</span> productos</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #dc3545 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-exclamation-triangle"></i> Competencia Más Barata</h6>
                        <h2 class="mb-0 fw-bold text-danger" id="res-mas-baratos">0</h2>
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
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-columns-reverse" style="color: #4f2e96;"></i> Resultados en Ripley</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Imagen</th>
                                <th>Producto / Vendedor</th>
                                <th>Estado</th>
                                <th class="text-end">Precio (Ripley)</th>
                                <th class="text-end">Precio Lista</th>
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
            if(!modelo) return;
            
            // Mostrar loading
            btnBuscar.disabled = true;
            loadingState.classList.remove('d-none');
            errorState.classList.add('d-none');
            resultsSection.classList.add('d-none');
            tbodyResultados.innerHTML = '';
            
            const url = `/api/verificar-precio-ripley?modelo=${encodeURIComponent(modelo)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    btnBuscar.disabled = false;
                    loadingState.classList.add('d-none');
                    
                    if(!data.success) {
                        errorMessage.textContent = data.message || 'Ocurrió un error desconocido.';
                        if (data.debug) {
                            errorMessage.innerHTML += `<br><small class="text-muted">${data.debug}</small>`;
                        }
                        errorState.classList.remove('d-none');
                        return;
                    }
                    
                    if(!data.productos || data.productos.length === 0) {
                        errorMessage.textContent = 'No se encontraron resultados en Ripley para este modelo.';
                        errorState.classList.remove('d-none');
                        return;
                    }
                    
                    // Llenar resumen
                    document.getElementById('res-total').textContent = data.total;
                    document.getElementById('res-mostrando').textContent = data.mostrando;
                    
                    let masBaratos = 0, competitivos = 0;
                    
                    // Llenar tabla
                    data.productos.forEach((prod, index) => {
                        const tr = document.createElement('tr');
                        
                        let badgeColor = 'secondary';
                        let badgeText = 'Competencia';
                        if (prod.is_my_store) {
                            tr.classList.add('table-warning');
                            badgeColor = 'warning';
                            badgeText = 'Mi Tienda';
                        }
                        
                        const imgTag = prod.image ? `<img src="${prod.image}" alt="Img" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">` : '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-image text-muted"></i></div>';
                        
                        tr.innerHTML = `
                            <td class="ps-4">${imgTag}</td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 350px;" title="${prod.title}">${prod.title}</div>
                                <a href="${prod.link}" target="_blank" class="text-decoration-none small text-muted">
                                    <i class="bi bi-shop"></i> ${prod.seller} <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-${badgeColor} text-dark">${badgeText}</span>
                            </td>
                            <td class="text-end fw-bold fs-6 text-danger">S/ ${parseFloat(prod.price).toFixed(2)}</td>
                            <td class="text-end text-muted text-decoration-line-through small">S/ ${parseFloat(prod.price_original).toFixed(2)}</td>
                        `;
                        tbodyResultados.appendChild(tr);
                    });
                    
                    document.getElementById('res-mas-baratos').textContent = masBaratos; // Pendiente de logica compleja
                    document.getElementById('res-competitivos').textContent = competitivos; // Pendiente
                    
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
