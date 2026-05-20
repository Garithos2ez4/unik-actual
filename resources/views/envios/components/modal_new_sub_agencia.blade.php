<div class="modal fade" id="modalNewSubAgencia" tabindex="-1" aria-labelledby="modalNewSubAgenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalNewSubAgenciaLabel"><i class="bi bi-geo-alt"></i> Nueva Oficina/Sucursal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-subagencia">
                    @csrf
                    <input type="hidden" name="idAgencia" id="idAgencia_subagencia">
                    <input type="hidden" name="idDestino" id="idDestino_subagencia">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Agencia Seleccionada</label>
                        <input type="text" id="text-agencia-seleccionada" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Distrito Seleccionado</label>
                        <input type="text" id="text-destino-seleccionado" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de Oficina / Sucursal <span class="text-muted"></span></label>
                        <input type="text" name="nombre_oficina" id="nombre_subagencia" class="form-control" placeholder="Ej: Oficina Principal, Sucursal Larco" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección Exacta <span class="text-muted">(Opcional)</span></label>
                        <input type="text" name="direccion" id="direccion_subagencia" class="form-control" placeholder="Ej: Av. Larco 456">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Teléfono de Oficina <span class="text-muted">(Opcional)</span></label>
                        <input type="text" name="telefono" id="telefono_subagencia" class="form-control" placeholder="Ej: 987654321">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="saveNewSubAgencia()">Guardar Oficina</button>
            </div>
        </div>
    </div>
</div>
@include('envios.logic.modal_new_sub_agencia')
