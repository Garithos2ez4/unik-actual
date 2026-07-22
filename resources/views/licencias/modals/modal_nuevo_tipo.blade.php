{{-- Modal: Nuevo Tipo de Licencia --}}
<div class="modal fade" id="modalNuevoTipo" tabindex="-1" aria-labelledby="modalNuevoTipoLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6); border-radius:16px 16px 0 0; border:none;">
                <h5 class="modal-title text-white" id="modalNuevoTipoLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Tipo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="nuevoTipoNombre" class="form-label fw-semibold">Nombre del Tipo</label>
                    <input type="text" id="nuevoTipoNombre" class="form-control" placeholder="Ej: Windows 12 Pro" autocomplete="off">
                    <div id="nuevoTipoError" class="text-danger small mt-1 d-none"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnGuardarNuevoTipo">
                    <i class="bi bi-save me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
