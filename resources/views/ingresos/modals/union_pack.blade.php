{{-- ============================================================
     MODAL: UNIÓN LIBRE — Armar Pack desde componentes individuales
     ============================================================ --}}
@foreach ($user->Accesos as $vista)
@if($vista->idVista == 8)
<div class="modal fade" id="unirPackModal" tabindex="-1" aria-labelledby="unirPackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="unirPackModalLabel">
                    <i class="bi bi-boxes"></i> Unir componentes en Pack
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                {{-- Indicador de pasos --}}
                <div class="d-flex align-items-center mb-3 gap-2" id="union-steps-indicator">
                    <span class="badge rounded-pill bg-success" id="step-badge-1">1</span>
                    <small class="text-muted">Seleccionar Pack</small>
                    <div class="flex-grow-1 border-top"></div>
                    <span class="badge rounded-pill bg-secondary" id="step-badge-2">2</span>
                    <small class="text-muted">Componentes</small>
                    <div class="flex-grow-1 border-top"></div>
                    <span class="badge rounded-pill bg-secondary" id="step-badge-3">3</span>
                    <small class="text-muted">Confirmar</small>
                </div>

                {{-- PASO 1: Elegir el pack destino --}}
                <div id="union-step-1" class="position-relative">
                    <p class="text-muted small mb-2">Busca y selecciona el producto que actuará como Pack (Padre).</p>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="input-buscar-padre-pack" placeholder="Buscar por modelo, código o nombre..." autocomplete="off">
                    </div>
                    <div id="suggestions-padre-pack" class="list-group position-absolute w-100 shadow" style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none; top: 100%;">
                    </div>
                    
                    <div id="union-padre-error" class="alert alert-danger d-none mt-2 small p-2">
                        <i class="bi bi-exclamation-triangle"></i> Este producto no tiene componentes configurados en la base de datos o no hay stock suficiente para armarlo.
                    </div>
                </div>

                {{-- PASO 2: Asignar componentes por slot --}}
                <div id="union-step-2" class="d-none">
                    <div class="d-flex align-items-center mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="btn-union-volver-paso1">
                            <i class="bi bi-arrow-left"></i> Volver
                        </button>
                        <strong id="union-nombre-pack-seleccionado"></strong>
                    </div>
                    <p class="text-muted small">Para cada componente requerido, selecciona qué unidad del stock se usará.</p>
                    <div id="union-componentes-container"></div>
                </div>

                {{-- PASO 3: Elegir almacén destino y confirmar --}}
                <div id="union-step-3" class="d-none">
                    <div class="d-flex align-items-center mb-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="btn-union-volver-paso2">
                            <i class="bi bi-arrow-left"></i> Volver
                        </button>
                        <strong>Resumen y confirmación</strong>
                    </div>
                    <div id="union-resumen" class="mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Almacén donde quedará el pack:</label>
                        <select class="form-select" id="union-almacen-destino">
                            @foreach($almacenes as $almacen)
                            <option value="{{$almacen->idAlmacen}}">{{$almacen->descripcion}}</option>
                            @endforeach
                        </select>
                    </div>
                    <form method="POST" action="{{ route('unir.componentes.pack') }}" id="form-unir-pack">
                        @csrf
                        <input type="hidden" name="idProductoPack" id="union-input-idpack">
                        <input type="hidden" name="idAlmacenDestino" id="union-input-idalmacen">
                        <div id="union-hidden-registros"></div>
                    </form>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="btn-union-siguiente-paso2">
                    Siguiente <i class="bi bi-arrow-right"></i>
                </button>
                <button type="button" class="btn btn-success d-none" id="btn-union-siguiente-paso3">
                    Siguiente <i class="bi bi-arrow-right"></i>
                </button>
                <button type="button" class="btn btn-success d-none" id="btn-union-confirmar">
                    <i class="bi bi-check-circle"></i> Confirmar Unión
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach
