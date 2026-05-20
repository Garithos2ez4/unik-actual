<div class="modal fade" id="modalNewAgencia" tabindex="-1" aria-labelledby="modalNewAgenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNewAgenciaLabel"><i class="bi bi-building"></i> Nueva Agencia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-agencia">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Agencia</label>
                        <input type="text" name="nombre" id="nombre_agencia" class="form-control" placeholder="Ej. SHALOM" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveNewAgencia()">Guardar Agencia</button>
            </div>
        </div>
    </div>
</div>
@include('envios.logic.modal_new_agencia')
