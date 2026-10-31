<!-- ============================================================== -->
<!-- MODAL: IMPORTACION MASIVA DEL CATALOGO DELTRON (100 EN 100)   -->
<!-- ============================================================== -->
<div class="modal fade" id="modalImportarCatalogo" tabindex="-1" aria-labelledby="modalImportarCatalogoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 94%;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0aa2c0 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 me-3">
                        <i class="bi bi-collection-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalImportarCatalogoLabel">
                            Importación Masiva del Catálogo Deltron (Lotes de 100)
                        </h5>
                        <small class="text-white-70">
                            Explora todo el catálogo de Deltron sin congelar el navegador. Filtra, asigna grupos y crea productos con Foto WebP y especificaciones técnicas.
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarModalCatalogo"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 bg-light">
                <!-- Estado 1: Cargando Candidatos -->
                <div id="importarCatalogoCargando" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5 class="fw-bold text-dark">Consultando Catálogo Deltron (Paginación Ligera)...</h5>
                    <p class="text-muted small mb-0">
                        Cargando lote de 100 productos candidatos optimizados en memoria para máxima velocidad.
                    </p>
                </div>

                <!-- Estado 2: Error al Cargar -->
                <div id="importarCatalogoError" class="alert alert-danger d-none my-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-octagon-fill fs-3 me-3 text-danger"></i>
                        <div>
                            <strong id="importarCatalogoErrorMsg">Ocurrió un error al consultar el catálogo.</strong>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="cargarCandidatosCatalogo(paginaCatalogoActual)">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado 3: Contenido Principal (Tabla de Candidatos + Progreso) -->
                <div id="importarCatalogoContenido" class="d-none">
                    <!-- Banner Informativo de Arquitectura Liviana -->
                    <div class="alert alert-primary border-0 shadow-xs py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center small">
                            <i class="bi bi-cpu fs-5 text-primary me-2"></i>
                            <div>
                                <strong>Arquitectura de Bajo Consumo:</strong> Se cargan exactamente <strong>100 productos por página</strong> para no saturar memoria RAM ni el navegador.
                                La creación masiva procesa en micro-lotes de <strong>5 productos</strong> con Foto 1 WebP 800x800 y descripción técnica completa.
                            </div>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-1 font-monospace" id="badgeTotalCatalogo">
                            0 productos disponibles
                        </span>
                    </div>

                    <!-- Panel de Ejecución de Progreso (Oculto al inicio) -->
                    <div id="panelProgresoImportacionCatalogo" class="card border-primary border-2 shadow-sm mb-3 d-none">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-primary d-flex align-items-center">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    <span id="textoEstadoProgresoCatalogo">Iniciando importación masiva...</span>
                                </span>
                                <span class="fw-bold text-dark font-monospace" id="textoPorcentajeProgresoCatalogo">0%</span>
                            </div>
                            <div class="progress mb-2" style="height: 16px;">
                                <div id="barraProgresoImportacionCatalogo" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <div>
                                    Lote actual: <strong id="loteActualTextoCatalogo">0 / 0</strong> | 
                                    Importados con éxito: <strong class="text-success" id="totalImportadosExitoCatalogo">0</strong> | 
                                    Errores: <strong class="text-danger" id="totalImportadosErrorCatalogo">0</strong>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnPausarImportacionCatalogo" onclick="togglePausarImportacionCatalogo()">
                                        <i class="bi bi-pause-fill"></i> Pausar
                                    </button>
                                </div>
                            </div>
                            <!-- Consola de registro en vivo -->
                            <div id="consolaImportacionCatalogo" class="bg-dark text-white-50 p-2 rounded mt-2 font-monospace small" style="max-height: 110px; overflow-y: auto; font-size: 0.78rem;">
                                <div>[Sistema] Esperando inicio de procesamiento de lotes...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Filtros y Paginación Superior -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <!-- Checkbox Seleccionar Todos y Solo Stock -->
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check ms-2 mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkSeleccionarTodosCatalogo" checked onchange="toggleSeleccionarTodosCatalogo(this.checked)">
                                    <label class="form-check-label fw-bold small text-dark" for="checkSeleccionarTodosCatalogo">
                                        Seleccionar Visibles (<span id="conteoSeleccionadosCatalogo">0</span>)
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkSoloConStock" onchange="cambiarFiltroStock(this.checked)">
                                    <label class="form-check-label small text-muted" for="checkSoloConStock">
                                        <i class="bi bi-box-seam text-success me-1"></i> Solo con stock disponible
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkReciclarFantasmasCatalogo">
                                    <label class="form-check-label small fw-semibold text-warning-emphasis" for="checkReciclarFantasmasCatalogo" title="Reutiliza los IDPRODUCTO de productos antiguos que nunca tuvieron ingresos en almacén en lugar de crear IDs nuevos">
                                        <i class="bi bi-recycle text-warning me-1"></i>Reciclar IDs sin ingresos (<span id="conteoFantasmasCatalogo">0</span> disp.)
                                    </label>
                                </div>
                            </div>

                            <!-- Buscador en tiempo real con debounce -->
                            <div class="d-flex align-items-center gap-2" style="max-width: 380px; width: 100%;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="filtroTextoCatalogo" class="form-control" placeholder="Buscar por modelo, SKU, marca..." oninput="onInputFiltroCatalogo(this.value)">
                                    <button class="btn btn-outline-secondary" type="button" onclick="limpiarFiltroCatalogo()">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Paginador de 100 en 100 -->
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaPrimera" onclick="irAPagina(1)" title="Primera página">
                                    <i class="bi bi-chevron-double-left"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaAnterior" onclick="irAPagina(paginaCatalogoActual - 1)" title="Página anterior">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span class="px-2 small fw-semibold text-secondary" id="textoPaginacion">
                                    Pág. 1 de 1
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaSiguiente" onclick="irAPagina(paginaCatalogoActual + 1)" title="Página siguiente">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaUltima" onclick="irAPagina(totalPaginasCatalogo)" title="Última página">
                                    <i class="bi bi-chevron-double-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Candidatos (100 filas) -->
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="table-responsive" style="max-height: 480px;">
                            <table class="table table-hover table-striped align-middle mb-0" id="tablaCandidatosCatalogo">
                                <thead class="table-light sticky-top small text-uppercase text-secondary">
                                    <tr>
                                        <th width="4%" class="text-center">#</th>
                                        <th width="8%" class="text-center">Foto 1</th>
                                        <th width="32%">Producto Deltron</th>
                                        <th width="28%">Grupo / Categoría Sugerido</th>
                                        <th width="14%" class="text-center">Precios (US$)</th>
                                        <th width="14%" class="text-center">Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyCandidatosCatalogo" class="small">
                                    <!-- Inyectado dinamicamente via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-2 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    Puedes cambiar el grupo de cualquier producto antes de ejecutar la importación.
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" id="btnCerrarFooterCatalogo">
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-xs" id="btnEjecutarImportacionCatalogo" onclick="iniciarImportacionMasivaCatalogo()">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Iniciar Importación (<span id="conteoBotonImportar">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
