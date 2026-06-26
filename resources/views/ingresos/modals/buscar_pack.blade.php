<!-- Modal Buscar Pack -->
@foreach ($user->Accesos as $vista)
@if($vista->idVista == 8)
<div class="modal fade" id="buscarPackModal" tabindex="-1" aria-labelledby="buscarPackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buscarPackModalLabel"><i class="bi bi-search"></i> Buscar Pack a Dividir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                <div class="mb-3">
                    <label class="form-label">Número de Serie</label>
                    <input type="text" class="form-control" id="input-buscar-serie-pack" placeholder="Ingresa o escanea la serie..." autofocus autocomplete="off">
                    <div id="suggestions-serie-pack" class="list-group position-absolute w-100 shadow mt-1" style="z-index: 1050; max-height: 200px; overflow-y: auto; display: none; left: 0; padding: 0 1rem;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-purple" id="btn-ejecutar-busqueda-pack">Buscar y Dividir</button>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach
