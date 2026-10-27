<!-- ========================================== -->
<!-- MODAL: CONSULTA EN TIEMPO REAL POR ALMACÉN -->
<!-- ========================================== -->
<div class="modal fade" id="modalAlmacenes" tabindex="-1" aria-labelledby="modalAlmacenesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalAlmacenesLabel">
                    <i class="bi bi-buildings me-2"></i> Stock en Tiempo Real por Almacén - Deltron API
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Información del Producto Encabezado -->
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1" id="almProdTitle">Cargando producto...</h5>
                        <div class="text-muted small">
                            <span class="me-3">Deltron SKU: <strong class="font-monospace text-primary" id="almProdSku">-</strong></span>
                            <span>Part Number: <strong class="font-monospace text-secondary" id="almProdPn">-</strong></span>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                        <i class="bi bi-database-check me-1"></i> Actualiza en BD
                    </span>
                </div>

                <!-- Spinner de Carga -->
                <div id="almLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Consultando...</span>
                    </div>
                    <div class="text-muted fw-semibold">Consultando y sincronizando con Inventario_Proveedor...</div>
                    <small class="text-muted">Desglosando inventario por almacenes y guardando en base de datos</small>
                </div>

                <!-- Contenido con Datos de Almacenes -->
                <div id="almContent" style="display: none;">
                    <!-- Banner de Alerta si tiene Promoción Vigente (API-0000030) -->
                    <div id="almPromoAlert" class="alert alert-warning py-2 px-3 mb-3 border-0 shadow-xs rounded-3 align-items-center justify-content-between d-none" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-fire text-danger fs-4 me-2"></i>
                            <div>
                                <strong class="text-dark d-block">¡Precio de Promoción Vigente en Deltron!</strong>
                                <span id="almPromoDetails" class="text-secondary small">Ahorro detectado frente al precio regular de lista.</span>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark font-monospace fw-bold">API-0000030</span>
                    </div>

                    <!-- Tarjetas de Resumen en Vivo -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="p-2 border rounded text-center bg-light h-100 shadow-xs">
                                <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-boxes text-success me-1"></i> Stock Total Deltron
                                </small>
                                <span class="fs-4 fw-bold text-success" id="almTotalStock">0</span> <small class="text-muted">uds.</small>
                                <small class="text-secondary d-block" id="almTotalStockSub" style="font-size: 0.7rem;">API-0000031</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 border rounded text-center bg-light h-100 shadow-xs" id="almCardRegularBox">
                                <small class="text-muted d-block fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-tag text-secondary me-1"></i> Precio Lista USD (Sin IGV)
                                </small>
                                <span class="fs-4 fw-bold text-dark" id="almPrecioUsd">$0.00</span>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">+ IGV: <span id="almPrecioIgv" class="fw-semibold text-secondary">$0.00</span></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 border rounded text-center h-100 shadow-xs" id="almCardPromoBox" style="background-color: #f8f9fa;">
                                <small class="text-muted d-block fw-semibold" id="almCardPromoTitle" style="font-size: 0.75rem;">
                                    <i class="bi bi-fire text-danger me-1"></i> Precio Promo USD (Sin IGV)
                                </small>
                                <span class="fs-4 fw-bold text-muted" id="almPrecioPromoUsd">-</span>
                                <small class="text-muted d-block" style="font-size: 0.75rem;" id="almCardPromoSub">Sin promoción vigente</small>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Almacenes -->
                    <div class="table-responsive rounded border mb-2">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr class="small text-uppercase text-secondary">
                                    <th width="15%">Cód. Sede</th>
                                    <th width="55%">Almacén / Ubicación</th>
                                    <th width="30%" class="text-center">Stock Disponible</th>
                                </tr>
                            </thead>
                            <tbody id="almTableBody">
                                <!-- Inyectado dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted fst-italic">
                        <i class="bi bi-info-circle me-1"></i> Stock sincronizado al segundo directamente desde el corte de inventario de Deltron.
                    </small>
                </div>

                <!-- Mensaje de Error si falla la consulta -->
                <div id="almError" class="alert alert-danger mb-0" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span id="almErrorMsg">No se pudo consultar el stock en tiempo real para este producto.</span>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <a href="#" id="almCrearBtn" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3 shadow-xs" style="display: none;">
                    <i class="bi bi-plus-circle me-1"></i> Crear en Unike
                </a>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="almVerFichaBtn" title="Ver especificaciones técnicas completas e imágenes (API-0000027)">
                    <i class="bi bi-file-earmark-text me-1"></i> Ver Ficha Técnica (API-0000027)
                </button>
                <a href="#" id="almCotizarBtn" target="_blank" class="btn btn-success btn-sm rounded-pill px-3">
                    <i class="bi bi-calculator me-1"></i> Cotizar en Calculadora
                </a>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
