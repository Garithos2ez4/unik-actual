<div class="modal fade" id="modalNewDestino" tabindex="-1" aria-labelledby="modalNewDestinoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalNewDestinoLabel"><i class="bi bi-geo-alt"></i> Nuevo Destino</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-destino">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Provincia</label>
                        <div class="input-group">
                            <select name="idProvincia" id="idProvincia_destino" class="form-select" required>
                                <option value="">Seleccione provincia...</option>
                                @foreach($provincias as $provincia)
                                    <option value="{{ $provincia->idProvincia }}">{{ $provincia->nombre }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewProvincia" title="Agregar Provincia">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Destino</label>
                        <input type="text" name="nombre" id="nombre_destino" class="form-control" placeholder="Ej. Moche (Trujillo)" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="saveNewDestino()">Guardar Destino</button>
            </div>
        </div>
    </div>
</div>
@include('envios.logic.modal_new_destino')
