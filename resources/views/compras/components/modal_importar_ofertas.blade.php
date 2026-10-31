<!-- ============================================================== -->
<!-- MODAL: IMPORTACION MASIVA DE OFERTAS DELTRON (+100 STOCK LIMA) -->
<!-- ============================================================== -->
<div class="modal fade" id="modalImportarOfertas" tabindex="-1" aria-labelledby="modalImportarOfertasLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 92%;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #b02a37 0%, #d63384 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 me-3">
                        <i class="bi bi-box-arrow-in-down fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalImportarOfertasLabel">
                            Importación Masiva de Ofertas Deltron (+100 Stock Lima)
                        </h5>
                        <small class="text-white-50">
                            Detecta automáticamente ofertas con stock masivo y asigna Grupos, Categorías y Foto 1 en WebP 800x800
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarModalImportar"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 bg-light">
                <!-- Estado 1: Cargando Candidatos -->
                <div id="importarOfertasCargando" class="text-center py-5">
                    <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5 class="fw-bold text-dark">Analizando Catálogo Deltron...</h5>
                    <p class="text-muted small mb-0">
                        Cruzando productos en oferta y stock masivo con tu catálogo para filtrar únicamente los que no existen.
                    </p>
                </div>

                <!-- Estado 2: Error al Cargar -->
                <div id="importarOfertasError" class="alert alert-danger d-none my-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-octagon-fill fs-3 me-3 text-danger"></i>
                        <div>
                            <strong id="importarOfertasErrorMsg">Ocurrió un error al consultar las ofertas.</strong>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="cargarCandidatosOfertas()">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado 3: Contenido Principal (Tabla de Candidatos + Progreso) -->
                <div id="importarOfertasContenido" class="d-none">
                    <!-- Banner Informativo de Arquitectura Liviana -->
                    <div class="alert alert-info border-0 shadow-xs py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center small">
                            <i class="bi bi-shield-check fs-5 text-primary me-2"></i>
                            <div>
                                <strong>Arquitectura de Importación Segura:</strong> Se procesa en micro-lotes de <strong>5 productos</strong>.
                                Solo descarga la <strong>Foto 1 (Principal)</strong> optimizada a WebP (800x800) y libera RAM en cada lote para no saturar el servidor.
                            </div>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-1 font-monospace" id="badgeTotalCandidatos">
                            0 productos nuevos
                        </span>
                    </div>

                    <!-- Panel de Ejecución de Progreso (Oculto al inicio) -->
                    <div id="panelProgresoImportacion" class="card border-danger border-2 shadow-sm mb-3 d-none">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-danger d-flex align-items-center">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    <span id="textoEstadoProgreso">Iniciando importación masiva...</span>
                                </span>
                                <span class="fw-bold text-dark font-monospace" id="textoPorcentajeProgreso">0%</span>
                            </div>
                            <div class="progress mb-2" style="height: 16px;">
                                <div id="barraProgresoImportacion" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <div>
                                    Lote actual: <strong id="loteActualTexto">0 / 0</strong> | 
                                    Importados con éxito: <strong class="text-success" id="totalImportadosExito">0</strong> | 
                                    Errores: <strong class="text-danger" id="totalImportadosError">0</strong>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnPausarImportacion" onclick="togglePausarImportacion()">
                                        <i class="bi bi-pause-fill"></i> Pausar
                                    </button>
                                </div>
                            </div>
                            <!-- Consola de registro en vivo -->
                            <div id="consolaImportacion" class="bg-dark text-white-50 p-2 rounded mt-2 font-monospace small" style="max-height: 110px; overflow-y: auto; font-size: 0.78rem;">
                                <div>[Sistema] Esperando inicio de procesamiento de lotes...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros Rápidos de la Tabla -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check ms-2 mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkSeleccionarTodos" checked onchange="toggleSeleccionarTodos(this.checked)">
                                    <label class="form-check-label fw-bold small text-dark" for="checkSeleccionarTodos">
                                        Seleccionar Todos (<span id="conteoSeleccionados">0</span>)
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkReciclarFantasmasOfertas">
                                    <label class="form-check-label small fw-semibold text-warning-emphasis" for="checkReciclarFantasmasOfertas" title="Reutiliza los IDPRODUCTO de productos antiguos que nunca tuvieron ingresos en almacén en lugar de crear IDs nuevos">
                                        <i class="bi bi-recycle text-warning me-1"></i>Reciclar IDs sin ingresos (<span id="conteoFantasmasOfertas">0</span> disp.)
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="max-width: 400px; width: 100%;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="filtroTablaCandidatos" class="form-control" placeholder="Buscar por título, SKU, marca o grupo..." oninput="filtrarTablaCandidatos()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('filtroTablaCandidatos').value=''; filtrarTablaCandidatos();">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Candidatos -->
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="table-responsive" style="max-height: 480px;">
                            <table class="table table-hover table-striped align-middle mb-0" id="tablaCandidatosOfertas">
                                <thead class="table-light sticky-top small text-uppercase text-secondary">
                                    <tr>
                                        <th width="4%" class="text-center">#</th>
                                        <th width="8%" class="text-center">Foto 1</th>
                                        <th width="32%">Producto Deltron</th>
                                        <th width="28%">Grupo / Categoría Sugerido</th>
                                        <th width="14%" class="text-center">Precios (US$)</th>
                                        <th width="14%" class="text-center">Stock Lima</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyCandidatosOfertas" class="small">
                                    <!-- Se inyecta dinamicamente via JS -->
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
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" id="btnCerrarFooter">
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold shadow-xs" id="btnEjecutarImportacion" onclick="iniciarImportacionMasiva()">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Iniciar Importación Masiva
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
