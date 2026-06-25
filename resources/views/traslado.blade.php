@extends('layouts.app')

@section('title', 'Traslados')

@section('content')

<style>
    .modal {
    display: none;
    position: fixed;
    z-index: 9999; 
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4); 
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 400px;
    text-align: center;
    z-index: 10000; 
}

button {
    padding: 10px 20px;
    margin: 5px;
    cursor: pointer;
    font-size: 16px;
}

.btn-confirm {
    background-color: green;
    color: white;
}

.btn-cancel {
    background-color: red;
    color: white;
}
</style>

<div id="hidden-body" style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none"></div>

<div id="modalConfirmacion" class="modal">
    <div class="modal-content">
        <p>¿Estás seguro de que deseas proceder con la reubicación?</p>
        <button id="btn-confirmar" class="btn-confirm">Confirmar</button>
        <button id="btn-cancelar" class="btn-cancel">Cancelar</button>
    </div>
</div>

<div class="container">
    <div class="row align-items-center">
        <div class="col-12 col-md-5">
            <h2><a href="{{route('documentos', [now()->format('Y-m')])}}" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> Traslados Masivos</h2>
        </div>
        
        <div class="col-12 col-md-7 mt-3 mt-md-0">
            <div class="row g-2">
                {{-- Búsqueda por Producto --}}
                <div class="col-8 col-lg-9" style="position:relative">
                    <div class="input-group" style="z-index:1000">
                        <span class="input-group-text bg-primary text-white"><i class="bi bi-box-seam"></i></span>
                        <input type="text" class="form-control" placeholder="Buscar Producto (Modelo, Código)..." id="search-producto" autocomplete="off">
                        <ul class="list-group shadow w-100" style="position:absolute;top:100%;z-index:1050;max-height:250px;overflow-y:auto" id="suggestions-producto"></ul>
                    </div>
                </div>
                {{-- Pegado Masivo Botón --}}
                <div class="col-4 col-lg-3">
                    <button type="button" class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#modalPegadoMasivo">
                        <i class="bi bi-clipboard-data"></i> Pegar Series
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Destino Masivo --}}
    <div class="row mt-4 mb-2 p-3 bg-light rounded border align-items-center">
        <div class="col-md-6 mb-2 mb-md-0">
            <div id="contador-productos" class="font-weight-bold" style="font-size:18px; color: #007bff;">Series Agregadas: 0</div>
            <small class="text-muted">Busca un producto para traer todas sus series o pega series específicas.</small>
        </div>
        <div class="col-md-6 text-md-end d-flex align-items-center justify-content-md-end gap-2">
            <label class="fw-bold mb-0 text-nowrap"><i class="bi bi-truck"></i> Destino Global:</label>
            <select class="form-select w-auto" id="destino-global" onchange="aplicarDestinoGlobal()">
                <option value="">- Seleccionar para todos -</option>
                @foreach($almacenes as $almacen)
                    <option value="{{ $almacen->idAlmacen }}">{{ $almacen->descripcion }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Modal Pegado Masivo --}}
    <div class="modal fade" id="modalPegadoMasivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-clipboard-data"></i> Pegado Masivo de Series</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Pega aquí tu lista de números de serie (separados por comas o saltos de línea). El sistema omitirá las series duplicadas o no disponibles.</p>
                    <textarea id="textarea-series-masivas" class="form-control" rows="8" placeholder="UNK-001&#10;UNK-002&#10;UNK-003..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btn-procesar-masivo" onclick="procesarPegadoMasivo()"><i class="bi bi-check-circle"></i> Procesar Series</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Cantidad Series --}}
    <div class="modal fade" id="modalCantidadSeries" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-primary shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title mb-0"><i class="bi bi-box-seam"></i> Trasladar Producto</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 id="modal-cantidad-producto-nombre" class="fw-bold mb-1">Producto</h6>
                    <p class="text-muted small mb-3">Hay <strong id="modal-cantidad-disponible" class="text-success">0</strong> series disponibles.</p>
                    
                    <label class="form-label fw-bold small">¿Cuántas deseas trasladar?</label>
                    <div class="input-group input-group-lg mx-auto" style="max-width: 150px;">
                        <input type="number" id="input-cantidad-trasladar" class="form-control text-center fw-bold" min="1" value="1">
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('input-cantidad-trasladar').value = document.getElementById('input-cantidad-trasladar').max">Todas</button>
                    </div>
                </div>
                <div class="modal-footer justify-content-center py-2">
                    <button type="button" class="btn btn-primary w-100" onclick="confirmarCantidadSeries()">Agregar a la lista</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <form action="{{route('updateregistroalmacen')}}" method="post">
            @csrf
            <div class="col-12">
                <ul class="list-group" id="lista-traslado" style="visibility: hidden; overflow: auto;">
                    <li class="list-group-item bg-sistema-uno text-light">
                        <div class="row pt-1 text-center">
                            <div class="col-md-4">
                                <h6>Producto</h6>
                            </div>
                            <div class="col-md-2 d-none d-md-block">
                                <h6>Serie</h6>
                            </div>
                            <div class="col-md-1 d-none d-md-block">
                                <h6>Estado</h6>
                            </div>
                            <div class="col-md-2 d-none d-md-block">
                                <h6>Origen</h6>
                            </div>
                            <div class="col-md-2 d-none d-md-block">
                                <h6>Destino</h6>
                            </div>
                        </div>
                    </li>
                    <!-- Aquí se agregarán los productos dinámicamente -->
                </ul>
            </div>
            <br>
            
            <div class="col-md-12 text-center" id="btn-reubicar" style="display: none">
                <button type="submit" class="btn btn-success" id="btn-reubicar-submit" disabled><i class="bi bi-arrow-left-right"></i> Reubicar</button>
            </div>
        </form>
    </div>
    

    <div class="row" id="aviso-vacio">
        <div class="col-12 d-flex justify-content-center align-items-center text-secondary text-decoration-underline" style="height:60vh">
            <h4>Añade una serie para su traslado</h4>
        </div>
    </div>
</div>

<script>
    var almacenes = @json($almacenes);
</script>
<script src="{{ asset('js/traslado.js') }}"></script>
@endsection
