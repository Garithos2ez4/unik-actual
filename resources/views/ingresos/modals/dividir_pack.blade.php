{{-- Modal de Confirmación de División de Pack --}}
<form action="{{route('dividirpack')}}" method="POST" id="form-dividir-pack">
    @csrf
    <div class="modal fade" id="dividirPackModal" tabindex="-1" aria-labelledby="dividirPackModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title" id="dividirPackModalLabel"><i class="bi bi-scissors"></i> Dividir Pack</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idRegistro" id="dividir-pack-idregistro">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Al dividir este pack, se crearán registros individuales para cada componente con la misma serie.
                    </div>
                    <p><strong>Producto:</strong> <span id="dividir-pack-producto"></span></p>
                    <p><strong>Serie:</strong> <span id="dividir-pack-serie"></span></p>
                    <div id="dividir-pack-componentes">
                        <p class="text-secondary"><i class="bi bi-hourglass-split"></i> Cargando componentes...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-purple" id="btn-confirmar-division">
                        <i class="bi bi-scissors"></i> Confirmar División
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
