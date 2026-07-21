{{-- Modal: Personalizar Títulos Template Express Falabella --}}
<div class="modal fade" id="modalTitulosFbk" tabindex="-1" aria-labelledby="modalTitulosFbkLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background: linear-gradient(135deg,#1a7a3a,#28a745); color:#fff;">
                <h5 class="modal-title" id="modalTitulosFbkLabel">
                    <i class="bi bi-file-earmark-excel-fill me-2"></i> Personalizar Títulos — Template Express Falabella
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Edita los 3 títulos que aparecerán como variaciones en el Excel. Usa <strong>"Generar del sistema"</strong> para auto-completarlos con las specs más llamativas del producto.
                </p>

                <div id="fbk-titulos-loading" class="text-center py-3" style="display:none;">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2 text-muted small">Generando sugerencias...</p>
                </div>

                <div id="fbk-titulos-form">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título 1 <span class="text-muted small">(principal)</span></label>
                        <input type="text" class="form-control" id="fbk-titulo1" maxlength="255" placeholder="Nombre del producto">
                    </div>
                    <div id="fbk-titulos-extra" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título 2</label>
                            <input type="text" class="form-control" id="fbk-titulo2" maxlength="255" placeholder="Variación con specs destacadas">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título 3</label>
                            <input type="text" class="form-control" id="fbk-titulo3" maxlength="255" placeholder="Otra variación">
                        </div>
                    </div>
                </div>

                <div id="fbk-titulos-error" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="fbk-btn-sugerir" onclick="generarSugerenciasFbk()">
                    <i class="bi bi-stars me-1"></i> Generar del sistema
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="fbk-btn-descargar" onclick="descargarTemplateExpressFbk()">
                        <i class="bi bi-download me-1"></i> Descargar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
