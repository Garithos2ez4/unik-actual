@extends('layouts.app')

@section('title', 'Verificador de Precios - Falabella')

@section('content')
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-12 col-md-8">
            <h2 class="fw-bold text-dark"><i class="bi bi-search text-primary"></i> Verificador de Precios Falabella</h2>
            <p class="text-muted">Busca un modelo de producto en Falabella y obtén los precios de la competencia en tiempo real para evaluar tu posicionamiento.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-4 bg-light">
            <form id="scraper-form">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label for="modelo" class="form-label fw-bold small text-secondary">Modelo / Producto a buscar</label>
                        <input type="text" class="form-control form-control-lg border-primary shadow-sm" id="modelo" placeholder="Ej. LENOVO LOQ 15IRH8..." required autocomplete="off">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="mi_tienda" class="form-label fw-bold small text-secondary">Nombre de mi tienda (Opcional)</label>
                        <input type="text" class="form-control form-control-lg shadow-sm" id="mi_tienda" value="GAMING POWER PERU" placeholder="Ej. GAMING POWER PERU" autocomplete="off">
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow fw-bold" id="btn-buscar">
                            <i class="bi bi-search"></i> Buscar Precios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="text-center py-5 d-none">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="mt-3 text-muted">Buscando en Falabella, por favor espera...</h5>
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
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #0dcaf0 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-box-seam"></i> Total Encontrados</h6>
                        <h2 class="mb-0 fw-bold text-info" id="res-total">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #198754 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-tag-fill"></i> Mejor Precio</h6>
                        <h2 class="mb-0 fw-bold text-success" id="res-precio">S/ 0.00</h2>
                        <small class="text-muted" id="res-vendedor">Vendedor</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-3" id="card-competencia" style="display:none;">
                <div class="card border-0 shadow-sm h-100 rounded-4" id="card-competencia-bg">
                    <div class="card-body">
                        <h6 class="text-uppercase fw-bold mb-1" id="res-tienda-title"><i class="bi bi-shop"></i> Posicionamiento</h6>
                        <h4 class="mb-0 fw-bold" id="res-tienda-status">...</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-columns-reverse text-primary"></i> Detalle de Publicaciones</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Título de la Publicación</th>
                                <th>Vendedor</th>
                                <th class="text-end pe-4">Precio (S/)</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-resultados">
                            <!-- JS Fills this -->
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
            const miTienda = document.getElementById('mi_tienda').value.trim();
            
            if(!modelo) return;
            
            // Mostrar loading
            btnBuscar.disabled = true;
            loadingState.classList.remove('d-none');
            errorState.classList.add('d-none');
            resultsSection.classList.add('d-none');
            tbodyResultados.innerHTML = '';
            
            const url = `/api/verificar-precio-falabella?modelo=${encodeURIComponent(modelo)}&mi_tienda=${encodeURIComponent(miTienda)}`;
            
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
                    
                    if(data.total_encontrados === 0 || !data.productos || data.productos.length === 0) {
                        errorMessage.textContent = data.message || 'No se encontraron resultados para el modelo ingresado.';
                        errorState.classList.remove('d-none');
                        return;
                    }
                    
                    // Llenar resumen
                    document.getElementById('res-total').textContent = data.total_encontrados;
                    
                    if(data.mejor_precio !== null) {
                        document.getElementById('res-precio').textContent = `S/ ${parseFloat(data.mejor_precio).toFixed(2)}`;
                        document.getElementById('res-vendedor').textContent = `Vendido por: ${data.vendedor_mas_barato}`;
                    } else {
                        document.getElementById('res-precio').textContent = 'N/A';
                        document.getElementById('res-vendedor').textContent = '-';
                    }
                    
                    // Analizar competencia
                    const cardComp = document.getElementById('card-competencia');
                    if(miTienda !== '') {
                        cardComp.style.display = 'block';
                        const bgCard = document.getElementById('card-competencia-bg');
                        const statusTitle = document.getElementById('res-tienda-status');
                        const titleH6 = document.getElementById('res-tienda-title');
                        
                        if(data.mi_tienda_es_mas_barata === true) {
                            bgCard.className = 'card border-0 shadow-sm h-100 rounded-4 bg-success text-white';
                            titleH6.className = 'text-uppercase fw-bold mb-1 text-white-50';
                            statusTitle.innerHTML = '<i class="bi bi-trophy-fill"></i> Eres el más barato';
                        } else if(data.mi_tienda_es_mas_barata === false) {
                            bgCard.className = 'card border-0 shadow-sm h-100 rounded-4 bg-danger text-white';
                            titleH6.className = 'text-uppercase fw-bold mb-1 text-white-50';
                            statusTitle.innerHTML = '<i class="bi bi-arrow-down-circle-fill"></i> Tienes competencia';
                        } else {
                            bgCard.className = 'card border-0 shadow-sm h-100 rounded-4 bg-warning text-dark';
                            titleH6.className = 'text-uppercase fw-bold mb-1 text-dark-50';
                            statusTitle.innerHTML = '<i class="bi bi-question-circle"></i> No apareces en los resultados';
                        }
                    } else {
                        cardComp.style.display = 'none';
                    }
                    
                    // Llenar tabla
                    data.productos.forEach((prod, index) => {
                        const tr = document.createElement('tr');
                        const esMiTienda = miTienda !== '' && prod.vendedor.toLowerCase().includes(miTienda.toLowerCase());
                        
                        if(index === 0) {
                            tr.classList.add('table-success'); // Resaltar el más barato
                        } else if(esMiTienda) {
                            tr.classList.add('table-info'); // Resaltar tu tienda
                        }
                        
                        tr.innerHTML = `
                            <td class="ps-4 fw-bold text-muted">${index + 1}</td>
                            <td>
                                <div>${prod.titulo}</div>
                                ${index === 0 ? '<span class="badge bg-success mt-1"><i class="bi bi-star-fill"></i> Mejor Precio</span>' : ''}
                                ${esMiTienda ? '<span class="badge bg-info text-dark mt-1"><i class="bi bi-shop"></i> Tu Tienda</span>' : ''}
                            </td>
                            <td><span class="badge bg-secondary">${prod.vendedor}</span></td>
                            <td class="text-end pe-4 fw-bold fs-5 text-primary">S/ ${parseFloat(prod.precio_mas_bajo).toFixed(2)}</td>
                        `;
                        tbodyResultados.appendChild(tr);
                    });
                    
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
