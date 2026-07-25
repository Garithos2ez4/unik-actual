<!-- Modal Editar Utilidad -->
<div class="modal fade" id="modalEditarUtilidad" tabindex="-1" aria-labelledby="modalEditarUtilidadLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title" id="modalEditarUtilidadLabel">
                    <i class="bi bi-pencil-square"></i> Editar Utilidad
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editUtilidadIdProducto">

                <!-- Nombre del producto -->
                <p class="fw-bold text-truncate mb-3" id="editUtilidadNombreProducto" title="" style="font-size: 0.9em;"></p>

                <div class="row g-2">
                    <!-- Columna izquierda: Precio base (solo lectura) -->
                    <div class="col-6">
                        <label class="form-label text-muted small mb-1">Precio (Sin IGV)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="editUtilidadPrecioBase" disabled>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small mb-1">Precio (Con IGV)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="editUtilidadPrecioIgv" disabled>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <label class="form-label text-muted small mb-1">Precio Soles (Sin IGV)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">S/</span>
                            <input type="text" class="form-control" id="editUtilidadPrecioBaseSoles" disabled>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small mb-1">Precio Soles (Con IGV)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">S/</span>
                            <input type="text" class="form-control" id="editUtilidadPrecioIgvSoles" disabled>
                        </div>
                    </div>
                </div>

                <hr class="my-2">

                <!-- Campo editable: Ganancia -->
                <div class="mb-2">
                    <label for="editUtilidadGanancia" class="form-label fw-bold mb-1">
                        <i class="bi bi-currency-dollar text-success"></i> Ganancia (Utilidad):
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="editUtilidadGanancia" step="0.01" oninput="recalcularPreviewUtilidad()">
                    </div>
                </div>

                <hr class="my-2">

                <!-- Preview de precios resultantes -->
                <div class="bg-light rounded p-2" style="font-size: 0.88em;">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-muted">Precio Venta (USD):</span>
                            <div class="fw-bold" id="editUtilidadPrecioVentaUsd">$0.00</div>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">TC usado:</span>
                            <div class="fw-bold" id="editUtilidadTC">0.00</div>
                        </div>
                    </div>
                    <hr class="my-1">
                    <div class="text-center">
                        <span class="text-muted">Precio en Web (S/.):</span>
                        <div class="fw-bold text-success fs-5" id="editUtilidadPrecioWeb">S/ 0.00</div>
                    </div>
                </div>

                <button type="button" class="btn btn-success w-100 mt-3" id="btnGuardarUtilidad" onclick="guardarUtilidad()">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

@include('productos.logic.editar_utilidad_scripts')

