@extends('layouts.app')

@section('title', 'Monitor de Precios V2 - Mercado Libre')

@section('content')
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-12 col-md-8">
            <h2 class="fw-bold text-dark">
                <i class="bi bi-graph-up-arrow text-primary"></i> Monitor de Precios V2
                <span class="badge bg-primary fs-6 align-middle">Búsqueda Real</span>
            </h2>
            <p class="text-muted">Busca cualquier producto en todo Mercado Libre Perú y compara los precios de la competencia directamente contra los tuyos.</p>
        </div>
        <div class="col-12 col-md-4 text-end">
            <a href="{{ route('dashboard.herramientas.scraper_mercadolibre') }}" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-arrow-left"></i> Ir a V1 (Benchmark)
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-4 bg-light">
            <form id="scraper-form-v2">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-8">
                        <label for="modelo-v2" class="form-label fw-bold small text-secondary">Buscar producto en Mercado Libre</label>
                        <input type="text" class="form-control form-control-lg border-primary shadow-sm" id="modelo-v2" placeholder="Ej. Monitor Xiaomi G27qi, Laptop Lenovo..." autocomplete="off" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow fw-bold" id="btn-buscar-v2">
                            <i class="bi bi-search"></i> Buscar en ML
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state-v2" class="text-center py-5 d-none">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="mt-3 text-muted">Buscando precios reales en Mercado Libre...</h5>
        <p class="text-muted small">Consultando la API de búsqueda pública de ML.</p>
    </div>

    <!-- Error State -->
    <div id="error-state-v2" class="alert alert-danger shadow-sm d-none">
        <i class="bi bi-exclamation-triangle-fill"></i> <span id="error-message-v2"></span>
    </div>

    <!-- Results Section -->
    <div id="results-section-v2" class="d-none">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-12 col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #0d6efd !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-globe"></i> Resultados en ML</h6>
                        <h2 class="mb-0 fw-bold text-primary" id="res-total-v2">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #ffc107 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-shop"></i> Tus Publicaciones</h6>
                        <h2 class="mb-0 fw-bold text-warning" id="res-mis-v2">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #dc3545 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-arrow-down-circle"></i> Precio Más Bajo</h6>
                        <h2 class="mb-0 fw-bold text-danger" id="res-precio-bajo-v2">-</h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-white h-100 rounded-4" style="border-left: 5px solid #198754 !important;">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-1"><i class="bi bi-people"></i> Competidores</h6>
                        <h2 class="mb-0 fw-bold text-success" id="res-competidores-v2">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mis Publicaciones Table -->
        <div id="mis-productos-section-v2" class="d-none">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-shop text-primary"></i> Tus Publicaciones Encontradas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Tu Producto</th>
                                    <th>Estado</th>
                                    <th class="text-end">Tu Precio</th>
                                    <th class="text-end">Competidor Más Bajo</th>
                                    <th class="text-end">Diferencia</th>
                                    <th class="text-center">Competidores</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-mis-productos-v2">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Competencia Table -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-people text-danger"></i> Lista de Competidores (Priorizando búsquedas exactas)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Producto</th>
                                <th>Vendedor</th>
                                <th class="text-end">Precio</th>
                                <th class="text-center">Envío Gratis</th>
                                <th class="text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-competencia-v2">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-between align-items-center" id="pagination-container-v2">
                <!-- Pagination will be rendered here via JS -->
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('scraper-form-v2');
        const btnBuscar = document.getElementById('btn-buscar-v2');
        const loadingState = document.getElementById('loading-state-v2');
        const errorState = document.getElementById('error-state-v2');
        const errorMessage = document.getElementById('error-message-v2');
        const resultsSection = document.getElementById('results-section-v2');
        const misProductosSection = document.getElementById('mis-productos-section-v2');
        const tbodyMis = document.getElementById('tbody-mis-productos-v2');
        const tbodyComp = document.getElementById('tbody-competencia-v2');
        const paginationContainer = document.getElementById('pagination-container-v2');

        let allCompetitors = [];
        let currentPage = 1;
        const itemsPerPage = 10;

        function renderCompetitorsPage(page) {
            tbodyComp.innerHTML = '';
            
            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const currentItems = allCompetitors.slice(startIndex, endIndex);

            currentItems.forEach((comp, i) => {
                const globalIndex = startIndex + i;
                const tr = document.createElement('tr');
                if (globalIndex === 0) tr.classList.add('table-info');

                tr.innerHTML = `
                    <td class="ps-4 fw-bold text-muted">${globalIndex + 1}</td>
                    <td>
                        <div class="fw-semibold">${comp.titulo}</div>
                    </td>
                    <td><span class="badge bg-dark">${comp.seller_nickname}</span></td>
                    <td class="text-end fw-bold fs-6">S/ ${parseFloat(comp.precio).toFixed(2)}</td>
                    <td class="text-center">${comp.free_shipping ? '<i class="bi bi-truck text-success"></i> Sí' : '<span class="text-muted">No</span>'}</td>
                    <td class="text-center">
                        <a href="${comp.permalink}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                `;
                tbodyComp.appendChild(tr);
            });

            renderPaginationControls();
        }

        function renderPaginationControls() {
            paginationContainer.innerHTML = '';
            const totalPages = Math.ceil(allCompetitors.length / itemsPerPage);
            
            if (totalPages <= 1) return;

            const startText = document.createElement('span');
            startText.className = 'text-muted small';
            startText.textContent = `Mostrando ${(currentPage - 1) * itemsPerPage + 1} a ${Math.min(currentPage * itemsPerPage, allCompetitors.length)} de ${allCompetitors.length} competidores`;
            
            const nav = document.createElement('nav');
            const ul = document.createElement('ul');
            ul.className = 'pagination pagination-sm mb-0';

            // Prev btn
            const liPrev = document.createElement('li');
            liPrev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            liPrev.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
            if (currentPage > 1) {
                liPrev.addEventListener('click', (e) => { e.preventDefault(); currentPage--; renderCompetitorsPage(currentPage); });
            }
            ul.appendChild(liPrev);

            // Page numbers
            for (let p = 1; p <= totalPages; p++) {
                const li = document.createElement('li');
                li.className = `page-item ${p === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
                li.addEventListener('click', (e) => { e.preventDefault(); currentPage = p; renderCompetitorsPage(currentPage); });
                ul.appendChild(li);
            }

            // Next btn
            const liNext = document.createElement('li');
            liNext.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            liNext.innerHTML = `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
            if (currentPage < totalPages) {
                liNext.addEventListener('click', (e) => { e.preventDefault(); currentPage++; renderCompetitorsPage(currentPage); });
            }
            ul.appendChild(liNext);

            nav.appendChild(ul);
            paginationContainer.appendChild(startText);
            paginationContainer.appendChild(nav);
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const modelo = document.getElementById('modelo-v2').value.trim();

            if (!modelo) {
                errorMessage.textContent = 'Debes ingresar un término de búsqueda.';
                errorState.classList.remove('d-none');
                return;
            }

            // Show loading
            btnBuscar.disabled = true;
            loadingState.classList.remove('d-none');
            errorState.classList.add('d-none');
            resultsSection.classList.add('d-none');
            misProductosSection.classList.add('d-none');
            tbodyMis.innerHTML = '';
            tbodyComp.innerHTML = '';
            paginationContainer.innerHTML = '';

            const url = `/api/verificar-precio-mercadolibre-v2?modelo=${encodeURIComponent(modelo)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    btnBuscar.disabled = false;
                    loadingState.classList.add('d-none');

                    if (!data.success) {
                        errorMessage.textContent = data.message || 'Ocurrió un error desconocido.';
                        errorState.classList.remove('d-none');
                        return;
                    }

                    // Summary
                    document.getElementById('res-total-v2').textContent = data.total || 0;
                    document.getElementById('res-mis-v2').textContent = (data.mis_productos || []).length;
                    document.getElementById('res-competidores-v2').textContent = (data.top_competidores || []).length;

                    const precioBajo = data.precio_mas_bajo_mercado || 0;
                    document.getElementById('res-precio-bajo-v2').textContent = precioBajo > 0 ? `S/ ${parseFloat(precioBajo).toFixed(2)}` : '-';

                    // Mis productos
                    if (data.mis_productos && data.mis_productos.length > 0) {
                        misProductosSection.classList.remove('d-none');

                        data.mis_productos.forEach((prod, index) => {
                            const tr = document.createElement('tr');

                            if (prod.status === 'eres_el_mas_barato' || prod.status === 'precio_competitivo') {
                                tr.classList.add('table-success');
                            } else if (prod.status === 'precio_muy_alto') {
                                tr.classList.add('table-danger');
                            } else if (prod.status === 'precio_alto') {
                                tr.classList.add('table-warning');
                            }

                            const diffColor = prod.diferencia_porcentaje > 15 ? 'text-danger' : (prod.diferencia_porcentaje > 5 ? 'text-warning' : 'text-success');
                            const diffSign = prod.diferencia_porcentaje > 0 ? '+' : '';
                            const diffValue = prod.status === 'sin_competencia' ? '-' : `${diffSign}${prod.diferencia_porcentaje}%`;
                            const lowestDisplay = prod.precio_mas_bajo > 0 ? `S/ ${parseFloat(prod.precio_mas_bajo).toFixed(2)}` : '-';

                            tr.innerHTML = `
                                <td class="ps-4 fw-bold text-muted">${index + 1}</td>
                                <td>
                                    <div class="fw-semibold">${prod.titulo}</div>
                                    <a href="${prod.permalink}" target="_blank" class="text-decoration-none small">
                                        <i class="bi bi-box-arrow-up-right"></i> ${prod.item_id}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-${prod.status_color}">${prod.status_label}</span>
                                </td>
                                <td class="text-end fw-bold fs-6">S/ ${parseFloat(prod.precio_actual).toFixed(2)}</td>
                                <td class="text-end fw-bold fs-6 text-primary">${lowestDisplay}</td>
                                <td class="text-end fw-bold ${diffColor}">${diffValue}</td>
                                <td class="text-center"><span class="badge bg-secondary">${prod.competidores}</span></td>
                            `;
                            tbodyMis.appendChild(tr);
                        });
                    }

                    // Competencia Paginada
                    allCompetitors = data.top_competidores || [];
                    currentPage = 1;
                    if (allCompetitors.length > 0) {
                        renderCompetitorsPage(currentPage);
                    }

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
