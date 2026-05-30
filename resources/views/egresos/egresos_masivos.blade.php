@extends('layouts.app')

@section('title', 'Egreso Masivo')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-12 col-md-6">
            <h2><a href="javascript:void(0)" onclick="window.history.back()" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> <i class="bi bi-lightning-charge-fill text-primary"></i> Egreso Masivo</h2>
            <p class="text-muted mb-1">Busca productos, selecciona sus series, asígnales un SKU y agrégalos a la lista para procesarlos juntos.</p>
        </div>
    </div>

    {{-- BUSCADOR DE PRODUCTOS --}}
    <div class="row mt-2">
        <div class="col-12 col-md-6" style="position:relative">
            <label class="form-label fw-bold mb-1"><i class="bi bi-search"></i> Buscar Producto</label>
            <input type="text" id="input-buscar-producto" class="form-control" placeholder="Marca, Modelo, Código o PartNumber..." autocomplete="off">
            <ul class="list-group shadow" id="suggestions-producto" style="position:absolute;z-index:1000;top:100%;left:0;width:100%;max-height:350px;overflow-y:auto"></ul>
        </div>
    </div>

    {{-- PRODUCTO SELECCIONADO --}}
    <div class="row mt-3" id="producto-seleccionado" style="display:none">
        <div class="col-12">
            <div class="card border-primary shadow-sm">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img id="producto-img" src="" alt="" style="width:60px;height:60px;object-fit:contain;border-radius:8px;background:#f8f9fa">
                        </div>
                        <div class="col">
                            <h6 class="mb-0 fw-bold" id="producto-nombre"></h6>
                            <small class="text-muted"><span id="producto-marca"></span> | Modelo: <span id="producto-modelo"></span> | Código: <span id="producto-codigo"></span></small>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-success fs-6" id="producto-stock-badge">0 series disponibles</span>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="limpiarProducto()"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SELECTOR DE CANTIDAD + SKU + TABLA DE SERIES --}}
    <div class="row mt-3" id="seccion-series" style="display:none">
        <div class="col-12 col-md-4 mb-3">
            <div class="card border-info shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-info mb-0"><i class="bi bi-gear-fill"></i> Configuración del Item</h6>
                    </div>
                    <hr class="my-2">
                    
                    {{-- Selector de cantidad --}}
                    <label class="form-label fw-bold mb-1"><small>¿Cuántas series egresar?</small></label>
                    <input type="number" id="input-cantidad-series" class="form-control form-control-lg text-center fw-bold" min="0" max="0" value="0" step="1">
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted">Máximo: <span id="max-series-text">0</span></small>
                        <div>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 me-1" style="font-size: 11px;" onclick="seleccionarTodas()">Todas</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size: 11px;" onclick="deseleccionarTodas()">Ninguna</button>
                        </div>
                    </div>
                    
                    {{-- Selector de SKU/Publicación --}}
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label fw-bold mb-0"><small>SKU / Publicación</small></label>
                            <small id="sku-validate-masivo-wrapper"><i id="sku-validate-masivo" class="bi bi-exclamation-circle text-danger"></i></small>
                        </div>
                        <div class="input-group input-group-sm mt-1" style="position:relative">
                            <input type="text" oninput="searchPublicacionMasivo(this)" class="form-control" id="input-sku-masivo" placeholder="Buscar SKU..." autocomplete="off">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="checkbox" id="check-sku-masivo" value="No aplica" style="cursor:pointer">
                                <label class="form-check-label ms-1" for="check-sku-masivo" style="font-size: 11px; cursor:pointer"><small>No aplica</small></label>
                            </div>
                            <input type="hidden" id="hidden-publicacion-id-masivo" value="">
                            <input type="hidden" id="hidden-publicacion-precio-masivo" value="">
                            <ul class="list-group shadow" id="suggestions-sku-masivo" style="position:absolute;z-index:1050;top:100%;left:0;width:100%;max-height:200px;overflow-y:auto"></ul>
                        </div>
                    </div>

                    {{-- Precio de Venta --}}
                    <div class="mt-3">
                        <label class="form-label fw-bold mb-0"><small>Precio de Venta (Unitario - S/)</small></label>
                        <input type="number" step="0.01" class="form-control form-control-sm border-success fw-bold text-end" id="input-precio-unitario-masivo" placeholder="0.00">
                    </div>
                    
                    {{-- Botón de agregar --}}
                    <div class="mt-4">
                        <button type="button" class="btn btn-primary w-100 fw-bold py-2" id="btn-agregar-item-carrito" onclick="agregarAlCarrito()"><i class="bi bi-plus-circle-fill"></i> Agregar a la Lista</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-1">
                    <strong><i class="bi bi-list-ul"></i> Seleccionar Series Específicas</strong>
                </div>
                <div class="card-body p-0" style="max-height:320px;overflow-y:auto">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width:40px"><input type="checkbox" id="check-todas-series" onclick="toggleTodasSeries(this)"></th>
                                <th>#</th>
                                <th>Número de Serie</th>
                                <th>Almacén</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-series"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- CARRITO DE PRODUCTOS AGREGADOS --}}
    <div class="row mt-4" id="seccion-carrito" style="display:none">
        <div class="col-12">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fs-6 fw-bold"><i class="bi bi-cart-check-fill"></i> Lista de Egresos Masivos (Carrito)</h5>
                    <span class="badge bg-light text-primary fw-bold" id="badge-total-items-carrito">0 items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr class="text-center text-secondary">
                                    <th style="width:70px">Imagen</th>
                                    <th>Producto</th>
                                    <th>SKU / Publicación</th>
                                    <th style="width:130px">P. Unitario</th>
                                    <th style="width:80px">Cant.</th>
                                    <th style="width:100px">Subtotal</th>
                                    <th>Series Seleccionadas</th>
                                    <th style="width:90px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-carrito">
                                {{-- Se llena desde JS --}}
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light text-end py-2">
                        <h5 class="mb-0 text-success fw-bold">Total Venta del Lote: S/ <span id="span-total-venta-lote">0.00</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FORMULARIO DE DESPACHO Y SUBMIT --}}
    <form action="{{ route('insertegreso') }}" method="POST" id="form-egreso-masivo" style="display:none">
        @csrf
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-secondary">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-info-circle-fill"></i> Datos Generales de Egreso y Despacho</h6>
                    </div>
                    <div class="card-body py-3">
                        <div class="row">
                            <div class="col-12 col-md-4 mb-3">
                                <label class="form-label fw-bold mb-1"><small>Número de Orden</small></label>
                                <div class="input-group">
                                    <input type="text" placeholder="Nro de Orden" id="input-numero-orden-masivo" name="numeroorden" class="form-control" required>
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="checkbox" id="check-orden-no-aplica" style="cursor:pointer">
                                        <label class="form-check-label ms-1" for="check-orden-no-aplica" style="font-size: 11px; cursor:pointer"><small>No aplica</small></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <label class="form-label fw-bold mb-1"><small>Fecha de pedido</small></label>
                                <input type="date" name="fechapedido" id="fechapedido-masivo" class="form-control" min="2024-01-01" max="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <label class="form-label fw-bold mb-1"><small>Fecha de despacho / venta</small></label>
                                <input type="date" name="fechadespacho" id="fechadespacho-masivo" class="form-control" required>
                            </div>
                        </div>

                        {{-- Resumen general del lote --}}
                        <div class="alert alert-info py-2 mb-2" id="resumen-egreso">
                            <strong><i class="bi bi-info-circle"></i> Resumen de Lote:</strong> <span id="resumen-texto">Añade productos al carrito para continuar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN DE PAGOS --}}
        <div class="row mt-3" id="seccion-pagos-masivo">
            <div class="col-12">
                <div class="card border-primary shadow-sm">
                    <div class="card-header bg-primary text-white py-1">
                        <h6 class="mb-0"><i class="bi bi-wallet2"></i> Registro de Pagos del Lote (Opcional)</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label class="form-label mb-0"><small>Método de Pago</small></label>
                                <select class="form-select form-select-sm" id="pago-metodo-masivo">
                                    <option value="">Seleccione...</option>
                                    @foreach($metodosPago ?? [] as $metodo)
                                        <option value="{{ $metodo->idMetodoPago }}">{{ $metodo->nombreMetodo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2" id="div-pago-empresa-masivo" style="display: none;">
                                <label class="form-label mb-0"><small>Empresa</small></label>
                                <select class="form-select form-select-sm" id="pago-empresa-masivo">
                                    <option value="">Seleccione...</option>
                                    @foreach($empresas ?? [] as $empresa)
                                        <option value="{{ $empresa->idEmpresa }}">{{ $empresa->nombreComercial ?? $empresa->razonSocial }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-2" id="div-pago-cuenta-masivo" style="display: none;">
                                <label class="form-label mb-0"><small>Cuenta Destino</small></label>
                                <select class="form-select form-select-sm" id="pago-cuenta-masivo">
                                    <option value="">Seleccione...</option>
                                    @foreach($cuentasBancarias ?? [] as $cuenta)
                                        <option value="{{ $cuenta->idCuentaBancaria }}" data-idempresa="{{ $cuenta->idEmpresa }}" data-banco="{{ strtoupper($cuenta->Banco->nombreBanco ?? '') }}">{{ $cuenta->Banco->nombreBanco ?? 'Banco' }} - Nro: {{ $cuenta->numeroCuenta }} ({{ $cuenta->tipoCuenta }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label mb-0"><small>Monto</small></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="pago-monto-masivo" placeholder="0.00">
                            </div>
                            <div class="col-md-2 mb-2 text-end">
                                <button type="button" class="btn btn-sm btn-primary w-100" id="btn-add-pago-masivo">Añadir</button>
                            </div>
                        </div>
                        
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered mb-0 text-center" id="tabla-pagos-masivo" style="display:none;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Método</th>
                                        <th>Monto</th>
                                        <th>Quitar</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-end">Total Pagado:</th>
                                        <th id="total-pagado-masivo" class="text-primary">0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="hidden-pagos-container-masivo"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden Items inputs generated by JS --}}
        <div id="hidden-items-container"></div>

        <br>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-success btn-lg px-5 shadow fw-bold" type="submit" id="btn-registrar-masivo" disabled><i class="bi bi-floppy"></i> Registrar Egreso Masivo</button>
            </div>
        </div>
    </form>
</div>
<script>
    window.assetUrl = "{{ asset('storage/') }}";
    window.tasaCambio = {{ $tasaCambio }};
    window.routes = {
        searchProducto: "{{ route('egresos.searchproducto') }}",
        seriesDisponibles: "{{ route('egresos.seriesdisponibles') }}",
        searchPublicacion: "{{ route('searchpublicacion') }}"
    };
</script>
<script src="{{asset('js/egresos_masivos.js')}}?v=1.0"></script>
@endsection
