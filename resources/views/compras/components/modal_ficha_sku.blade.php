<!-- ============================================================== -->
<!-- MODAL: FICHA TECNICA Y DETALLE POR SKU DELTRON (API-0000027)   -->
<!-- ============================================================== -->
<div class="modal fade" id="modalFichaSku" tabindex="-1" aria-labelledby="modalFichaSkuLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            
            <!-- Encabezado Principal del Modal -->
            <div class="modal-header bg-dark text-white border-bottom border-secondary">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-25 p-2 me-3 text-primary border border-primary border-opacity-50">
                        <i class="bi bi-file-earmark-text-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="modal-title fw-bold mb-0 text-white" id="fichaModalTitle">
                                Ficha de Catálogo por SKU Deltron
                            </h5>
                            <span class="badge bg-primary text-white font-monospace fw-semibold px-2 py-1">API-0000027</span>
                        </div>
                        <small class="text-white-50">Especificaciones técnicas completas, precios B2B, imágenes, tránsito y stock multi-almacén</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Subbarra de Estado, Origen y Cuota -->
            <div class="bg-light px-3 py-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="fichaOrigenBadge">
                        <span class="badge bg-secondary rounded-pill px-2 py-1">
                            <i class="bi bi-clock me-1"></i> Consultando...
                        </span>
                    </span>
                    <span class="badge bg-white text-muted border rounded-pill px-2 py-1 small" id="fichaTimestamp">
                        <i class="bi bi-calendar3 me-1"></i> -
                    </span>
                    <span class="badge bg-white text-secondary border rounded-pill px-2 py-1 small">
                        <i class="bi bi-speedometer2 me-1"></i> Límite: 300 llamadas/día · 60/h
                    </span>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs" id="btnForceFichaRefresh" title="Fuerza la consulta directa a la API de Deltron y actualiza la base de datos">
                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar desde Deltron API
                    </button>
                </div>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4 bg-light bg-opacity-50">
                
                <!-- Barra de Consulta Rápida por SKU / Part Number -->
                <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
                    <div class="card-body p-2">
                        <form id="formFichaSkuLookup" onsubmit="event.preventDefault(); consultarFichaDesdeInput();" class="row g-2 align-items-center">
                            <div class="col-12 col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="fichaSkuInput" class="form-control border-start-0 font-monospace text-uppercase" placeholder="Ingresar SKU Deltron o Part Number (ej: PRHP1F3Y2A)..." required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('fichaSkuInput').value='';">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill px-3 shadow-xs">
                                    <i class="bi bi-file-earmark-text me-1"></i> Consultar Ficha
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Spinner de Carga Inicial -->
                <div id="fichaLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
                        <span class="visually-hidden">Cargando ficha técnica...</span>
                    </div>
                    <h5 class="fw-bold text-dark">Consultando ficha técnica oficial de Deltron...</h5>
                    <p class="text-muted small mb-0">Desglosando especificaciones, imágenes en alta resolución, tránsito y stock de los 13 almacenes</p>
                </div>

                <!-- Mensaje de Error si falla la consulta -->
                <div id="fichaError" class="alert alert-danger shadow-xs border-0" style="display: none;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-octagon-fill fs-3 text-danger me-3"></i>
                        <div>
                            <strong class="d-block">No se pudo cargar la ficha técnica del producto</strong>
                            <span id="fichaErrorMsg" class="small">Verifica que el SKU sea válido y exista en el catálogo de Deltron.</span>
                        </div>
                    </div>
                </div>

                <!-- Contenido Dinámico de la Ficha -->
                <div id="fichaContent" style="display: none;">
                    
                    <!-- Tarjeta de Identificación del Producto -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold text-dark mb-1" id="fichaProdName">-</h5>
                                    <div class="d-flex align-items-center gap-2 flex-wrap text-muted small mt-2">
                                        <span>SKU Deltron: <strong class="badge bg-secondary font-monospace" id="fichaProdSku">-</strong></span>
                                        <span>Part Number: <strong class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" id="fichaProdPn">-</strong></span>
                                        <span id="fichaMiniCodeBox" style="display: none;">Mini Código: <strong class="badge bg-light text-dark border font-monospace" id="fichaProdMiniCode">-</strong></span>
                                        <span id="fichaBrandBox" style="display: none;">Marca: <strong class="badge bg-light text-secondary border" id="fichaProdBrand">-</strong></span>
                                        <span id="fichaCategoryBox" style="display: none;">Categoría: <strong class="badge bg-light text-secondary border" id="fichaProdCategory">-</strong></span>
                                        <span id="fichaWarrantyBox" style="display: none;">Garantía: <strong class="badge bg-info-subtle text-info-emphasis border border-info-subtle" id="fichaProdWarranty">-</strong></span>
                                    </div>
                                </div>
                                <div id="fichaBdStatusBox">
                                    <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                                        <i class="bi bi-database-check me-1"></i> Sincronizado en BD
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Métricas Principales: Precios, Stock y Tránsito -->
                    <div class="row g-3 mb-4">
                        <!-- Precio Regular -->
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded-3 bg-white shadow-xs text-center h-100" id="fichaCardRegularBox">
                                <small class="text-muted d-block fw-semibold mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-tag text-secondary me-1"></i> PRECIO LISTA B2B
                                </small>
                                <span class="fs-4 fw-bold text-dark" id="fichaPrecioUsd">$0.00</span>
                                <small class="text-muted d-block small mt-1">+ IGV: <span id="fichaPrecioIgv" class="fw-semibold text-secondary">$0.00</span></small>
                            </div>
                        </div>

                        <!-- Precio Promo -->
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded-3 bg-white shadow-xs text-center h-100" id="fichaCardPromoBox">
                                <small class="text-muted d-block fw-semibold mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-lightning-charge text-danger me-1"></i> PRECIO PROMOCION
                                </small>
                                <span class="fs-4 fw-bold text-muted" id="fichaPrecioPromoUsd">-</span>
                                <small class="text-muted d-block small mt-1" id="fichaCardPromoSub">Sin promoción vigente</small>
                            </div>
                        </div>

                        <!-- Stock Total -->
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded-3 bg-white shadow-xs text-center h-100">
                                <small class="text-muted d-block fw-semibold mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-boxes text-success me-1"></i> STOCK DISPONIBLE
                                </small>
                                <span class="fs-4 fw-bold text-success" id="fichaStockTotal">0</span> <small class="text-muted">uds.</small>
                                <small class="text-muted d-block small mt-1">Total en 13 almacenes</small>
                            </div>
                        </div>

                        <!-- Tránsito Total -->
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded-3 bg-white shadow-xs text-center h-100">
                                <small class="text-muted d-block fw-semibold mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-truck text-info me-1"></i> EN TRÁNSITO
                                </small>
                                <span class="fs-4 fw-bold text-info" id="fichaTransitoTotal">0</span> <small class="text-muted">uds.</small>
                                <small class="text-muted d-block small mt-1" id="fichaTransitoSub">Mercadería en camino</small>
                            </div>
                        </div>
                    </div>

                    <!-- Pestañas de Detalle de Ficha Técnica -->
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
                        <div class="card-header bg-white border-bottom p-0">
                            <ul class="nav nav-tabs border-0 px-3 pt-2" id="fichaTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-bold text-dark border-bottom-0" id="tab-specs-btn" data-bs-toggle="tab" data-bs-target="#tab-specs" type="button" role="tab" aria-controls="tab-specs" aria-selected="true">
                                        <i class="bi bi-cpu me-1 text-primary"></i> Especificaciones Técnicas
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-bold text-secondary border-bottom-0" id="tab-images-btn" data-bs-toggle="tab" data-bs-target="#tab-images" type="button" role="tab" aria-controls="tab-images" aria-selected="false">
                                        <i class="bi bi-images me-1 text-success"></i> Galería de Imágenes <span class="badge bg-secondary rounded-pill ms-1" id="fichaImagesCountBadge">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-bold text-secondary border-bottom-0" id="tab-warehouses-btn" data-bs-toggle="tab" data-bs-target="#tab-warehouses" type="button" role="tab" aria-controls="tab-warehouses" aria-selected="false">
                                        <i class="bi bi-buildings me-1 text-warning"></i> Stock por Almacén <span class="badge bg-light text-dark border rounded-pill ms-1">13 sedes</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-bold text-secondary border-bottom-0" id="tab-transito-btn" data-bs-toggle="tab" data-bs-target="#tab-transito" type="button" role="tab" aria-controls="tab-transito" aria-selected="false">
                                        <i class="bi bi-truck me-1 text-info"></i> Tránsito y Promos
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content p-3" id="fichaTabsContent">
                            
                            <!-- PESTAÑA 1: ESPECIFICACIONES TÉCNICAS -->
                            <div class="tab-pane fade show active" id="tab-specs" role="tabpanel" aria-labelledby="tab-specs-btn">
                                <!-- Descripción corta / larga -->
                                <div id="fichaDescContainer" class="mb-3 p-3 bg-light rounded-3 border" style="display: none;">
                                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-info-circle me-1"></i> Resumen del Producto:</h6>
                                    <p class="text-secondary small mb-0" id="fichaDescText">-</p>
                                </div>

                                <!-- Tabla HTML de Specs Oficiales Deltron -->
                                <div class="deltron-specs-container border rounded-3 p-3 bg-white" style="max-height: 500px; overflow-y: auto;">
                                    <div id="fichaSpecsHtml">
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-card-text fs-3 d-block mb-2 opacity-50"></i>
                                            Sin especificaciones técnicas detalladas proporcionadas por Deltron.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PESTAÑA 2: GALERÍA DE IMÁGENES -->
                            <div class="tab-pane fade" id="tab-images" role="tabpanel" aria-labelledby="tab-images-btn">
                                <div class="row g-3" id="fichaImagesGrid">
                                    <!-- Inyectado dinámicamente -->
                                </div>
                            </div>

                            <!-- PESTAÑA 3: STOCK POR ALMACÉN -->
                            <div class="tab-pane fade" id="tab-warehouses" role="tabpanel" aria-labelledby="tab-warehouses-btn">
                                <div class="table-responsive rounded border mb-2">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr class="small text-uppercase text-secondary">
                                                <th width="15%" class="ps-3">Cód. Sede</th>
                                                <th width="55%">Almacén / Ubicación</th>
                                                <th width="30%" class="text-center pe-3">Stock Disponible</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fichaWarehousesTableBody">
                                            <!-- Inyectado dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted fst-italic">
                                    <i class="bi bi-info-circle me-1"></i> Stock actualizado y sincronizado en tiempo real con el corte de Deltron.
                                </small>
                            </div>

                            <!-- PESTAÑA 4: TRÁNSITO Y PROMOCIONES -->
                            <div class="tab-pane fade" id="tab-transito" role="tabpanel" aria-labelledby="tab-transito-btn">
                                <!-- Sección Tránsito -->
                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-truck text-info me-1"></i> Mercadería en Tránsito / Pedidos en Camino</h6>
                                <div class="table-responsive rounded border mb-4">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr class="small text-uppercase text-secondary">
                                                <th width="20%" class="ps-3">Tipo Transporte</th>
                                                <th width="35%">Fecha Estimada</th>
                                                <th width="25%">Referencia / Guía</th>
                                                <th width="20%" class="text-center pe-3">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fichaTransitoTableBody">
                                            <!-- Inyectado dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Sección Promociones Asociadas -->
                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-lightning-charge text-warning me-1"></i> Promociones Activas para este SKU</h6>
                                <div class="table-responsive rounded border">
                                    <table class="table table-hover table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr class="small text-uppercase text-secondary">
                                                <th width="20%" class="ps-3">Cód. Promo</th>
                                                <th width="55%">Descripción de la Oferta</th>
                                                <th width="25%" class="text-end pe-3">Precio Promo B2B</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fichaPromosTableBody">
                                            <!-- Inyectado dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

            </div>

            <!-- Pie del Modal -->
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    <i class="bi bi-shield-check text-success me-1"></i> Guardado en almacenamiento local para no consumir cuota en visitas web.
                </div>
                <div class="d-flex gap-2">
                    <a href="#" id="fichaCotizarBtn" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 shadow-xs">
                        <i class="bi bi-calculator me-1"></i> Cotizar en Calculadora
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
