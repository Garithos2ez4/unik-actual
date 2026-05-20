<div class="modal fade" id="modalNewProvincia" tabindex="-1" aria-labelledby="modalNewProvinciaLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title" id="modalNewProvinciaLabel"><i class="bi bi-map"></i> Nueva Provincia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-provincia">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Provincia</label>
                        <input type="text" name="nombre" id="nombre_provincia" class="form-control" placeholder="Ej. Lima" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" onclick="saveNewProvincia()">Guardar</button>
            </div>
        </div>
    </div>
</div>
@include('envios.logic.modal_new_provincia')
