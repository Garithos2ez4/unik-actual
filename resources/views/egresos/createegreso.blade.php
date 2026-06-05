@extends('layouts.app')

@section('title', 'Nuevo Egreso')

@section('content')
<div class="container">
    <div class="bg-secondary" id="hidden-body"
        style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
    </div>
    <br>
    <div class="row align-items-center">
        <div class="col-12 col-md-7 mb-2 mb-md-0">
            <h2 class="mb-0">Nuevo egreso</h2>
        </div>
        <div class="col-12 col-md-5 mb-2" style="position:relative">
            <div class="row">
                <div class="col-12">
                    <div class="input-group mt-1">
                        <input type="text" oninput="searchRegistro(this)" name="serialnumber"
                            placeholder="Serial Number" class="form-control input-egreso"
                            id="input-serial-number">
                        <div class="input-group-text">
                            <x-scan_check :clases="'form-check-input scan-check mt-0'" :idInput="'input-serial-number'" />
                        </div>
                    </div>
                    <input type="hidden" value="" name="idregistro"
                        id="hidden-product-serial-number">
                    <ul class="list-group" id="suggestions-serial-number" name="idregistro"
                        style="position:absolute;z-index:1000;top:100%;left:0;width:100%"></ul>
                </div>

                <div class="col-12 text-end">
                    <small class="text-secondary">scanner</small>
                </div>
            </div>

        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-12 text-start">
            <div id="contador-productos" class="font-weight-bold" style="font-size:18px; color: #007bff;">Productos Agregados: 0</div>
        </div>
    </div>
    <form action="{{ route('insertegreso') }}" method="POST" id="form-egreso">
        @csrf
        <div class="row">
            <div class="col-12 col-md-3 mb-2">
                <div class="row">
                    <div class="col-6">
                        <label>SKU</label> <i id="sku-modal-egreso-validate" class="bi bi-exclamation-circle text-danger"></i>
                    </div>
                    <div class="col-6 text-end text-secondary">
                        <label>No aplica</label>
                    </div>
                </div>
                <div class="input-group" style="position:relative">
                    <input type="text" oninput="searchPublicacion(this)" name="sku"
                        class="form-control input-egreso" id="input-sku-egreso"
                        placeholder="SKU de una publicacion">
                    <div class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" id="check-sku-egreso"
                            value="No aplica">
                    </div>
                    <input type="hidden" id="hidden-publicacion-sku" class="form-control cab-form" value="">
                    <input type="hidden" id="hidden-publicacion-precio" value="">

                    <ul class="list-group" id="suggestions-sku"
                        style="position:absolute;z-index:1000;top:100%;left:0;width:100%"></ul>
                </div>
            </div>
            <div class="col-12 col-md-3 mb-2" id="div-cliente-egreso" style="display:none;">
                <label>Cliente (Opcional)</label>
                <div class="input-group" style="position:relative">
                    <input type="text" oninput="searchClienteAjax(this)" id="input-cliente-egreso" class="form-control input-egreso" placeholder="Nombre o Documento">
                    <div class="input-group-text" id="btn-clear-cliente" style="cursor:pointer; display:none;" onclick="clearCliente()">
                        <i class="bi bi-x"></i>
                    </div>
                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#nuevoClienteModal" class="input-group-text bg-primary text-white" title="Añadir Cliente" style="text-decoration: none;">
                        <i class="bi bi-person-fill-add"></i>
                    </a>
                    <input type="hidden" name="idCliente" id="hidden-id-cliente" value="">
                    <ul class="list-group" id="suggestions-cliente"
                        style="position:absolute;z-index:1000;top:100%;left:0;width:100%"></ul>
                </div>
            </div>
            <div class="col-12 col-md-2 mb-2">
                <label>Numero de Orden</label>
                <input type="text" placeholder="Nro de Orden" id="input-numero-orden"
                    name="numeroorden" class="form-control input-egreso cab-form" required>
            </div>
            <div class="col-6 col-md-2 mb-2">
                <label>Fecha de pedido</label>
                <input type="date" name="fechapedido" id="fechapedido" class="form-control input-egreso cab-form"
                    min="2024-01-01" max="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-6 col-md-2 mb-2">
                <label>Fecha de despacho</label>
                <input type="date" name="fechadespacho" id="fechadespacho" class="form-control input-egreso cab-form" required>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-12" id="div-items-create-egreso">

            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12 text-end">
                <h4 class="text-success" style="display: none;" id="contenedor-total-venta">Total Venta: <span id="span-total-venta">0.00</span></h4>
            </div>
        </div>

        <!-- SECCIÓN DE COMPONENTES / UPGRADES -->
        <div class="row mt-3" id="seccion-componentes" style="display: none;">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-light">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: #e6ffe6;">
                                <i class="bi bi-cpu text-success fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Componentes / Upgrades</h5>
                                <span class="text-muted small">Añade RAM, SSD u otros componentes instalados en este producto
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">Opcional</span>
                                </span>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-3 shadow-sm border mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-5" style="position: relative;">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Buscar Componente (RAM / SSD)</label>
                                    <input type="text" class="form-control" id="input-buscar-componente" placeholder="Ej: RAM DDR4 16GB" autocomplete="off">
                                    <ul class="list-group" id="suggestions-componente" style="position:absolute;z-index:1000;top:100%;left:0;width:100%"></ul>
                                </div>
                                <div class="col-6 col-md-3" style="position: relative;">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Serie del Componente</label>
                                    <select class="form-select" id="select-serie-componente" disabled>
                                        <option value="">Primero selecciona un componente</option>
                                    </select>
                                    <input type="hidden" id="hidden-componente-idProducto" value="">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Costo (S/)</label>
                                    <input type="number" step="0.01" class="form-control fw-bold text-danger" id="input-costo-componente" placeholder="0.00">
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" id="btn-add-componente">
                                        <i class="bi bi-plus-lg me-1"></i> Añadir
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-borderless align-middle mb-0" id="tabla-componentes" style="display:none;">
                                <thead class="table-light border-bottom">
                                    <tr>
                                        <th class="text-uppercase small fw-bold text-secondary ps-3">Componente</th>
                                        <th class="text-uppercase small fw-bold text-secondary">Serie</th>
                                        <th class="text-uppercase small fw-bold text-secondary text-end">Costo</th>
                                        <th class="text-uppercase small fw-bold text-secondary text-center pe-3">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-componentes"></tbody>
                                <tfoot class="border-top" style="border-top-width: 2px !important;">
                                    <tr>
                                        <th colspan="2" class="text-end text-uppercase text-secondary fw-bold pt-3">Total Componentes:</th>
                                        <th class="text-end pt-3">
                                            <span class="fs-5 fw-bold text-danger">S/ <span id="total-componentes-text">0.00</span></span>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div id="hidden-componentes-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE PAGOS (Oculta por defecto) -->
        <div class="row mt-4" id="seccion-pagos" style="display: none;">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 bg-light">
                    <div class="card-body p-4">

                        <div class="d-flex align-items-center mb-4">
                            <div class="p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: #e6f0ff;"> <i class="bi bi-wallet2 text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Registro de Pagos</h5>
                                <span class="text-muted small">Añade los adelantos o pagos parciales de este egreso <span class="badge bg-secondary-subtle text-secondary ms-1">Opcional</span></span>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-3 shadow-sm border mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-3">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Método de Pago</label>
                                    <select class="form-select" id="pago-metodo">
                                        <option value="">Seleccione...</option>
                                        @foreach($metodosPago ?? [] as $metodo)
                                        <option value="{{ $metodo->idMetodoPago }}">{{ $metodo->nombreMetodo }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-3" id="div-pago-empresa" style="display: none;">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Empresa</label>
                                    <select class="form-select" id="pago-empresa">
                                        <option value="">Seleccione...</option>
                                        @foreach($empresas ?? [] as $empresa)
                                        <option value="{{ $empresa->idEmpresa }}">{{ $empresa->nombreComercial ?? $empresa->razonSocial }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4" id="div-pago-cuenta" style="display: none;">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Cuenta Destino</label>
                                    <select class="form-select" id="pago-cuenta">
                                        <option value="">Seleccione...</option>
                                        @foreach($cuentasBancarias ?? [] as $cuenta)
                                        <option value="{{ $cuenta->idCuentaBancaria }}" data-idempresa="{{ $cuenta->idEmpresa }}" data-banco="{{ strtoupper($cuenta->Banco->nombreBanco ?? '') }}">
                                            {{ $cuenta->Banco->nombreBanco ?? 'Banco' }} - Nro: {{ $cuenta->numeroCuenta }} ({{ $cuenta->tipoCuenta }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Monto (S/)</label>
                                    <input type="number" step="0.01" class="form-control fw-bold text-primary" id="pago-monto" placeholder="0.00">
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label text-secondary fw-semibold small mb-1">Referencia</label>
                                    <input type="text" class="form-control" id="pago-ref" placeholder="Ej. OP-1234">
                                </div>

                                <div class="col-12 col-md-2">
                                    <button type="button" class="btn btn-primary w-100 fw-bold shadow-sm" id="btn-add-pago">
                                        <i class="bi bi-plus-lg me-1"></i> Añadir
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-borderless align-middle mb-0" id="tabla-pagos" style="display:none;">
                                <thead class="table-light border-bottom">
                                    <tr>
                                        <th class="text-uppercase small fw-bold text-secondary ps-3">Método / Cuenta</th>
                                        <th class="text-uppercase small fw-bold text-secondary">Referencia</th>
                                        <th class="text-uppercase small fw-bold text-secondary text-end">Monto</th>
                                        <th class="text-uppercase small fw-bold text-secondary text-center pe-3">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot class="border-top" style="border-top-width: 2px !important;">
                                    <tr>
                                        <th colspan="2" class="text-end text-uppercase text-secondary fw-bold pt-3">Total Pagado:</th>
                                        <th class="text-end pt-3">
                                            <span class="fs-5 fw-bold text-success">S/ <span id="total-pagado-text">0.00</span></span>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div id="hidden-pagos-container"></div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-success" type="submit" id="btn-create-egreso-submit"><i class="bi bi-floppy"></i> Registrar</button>

            </div>
        </div>
    </form>

    <div class="card bg-light mt-4">
        <div class="card-body">
            <h5 class="card-title text-success"><i class="bi bi-file-earmark-excel"></i> Carga Masiva para Atrasos</h5>
            <form action="{{ route('egresos.importar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                    <input type="file" name="archivo_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
                    <button class="btn btn-success" type="submit">Subir e Importar</button>
                    <a href="{{ route('egresos.formato') }}" class="btn btn-outline-success" title="Descargar Formato Excel">
                        <i class="bi bi-file-earmark-excel"></i> Formato
                    </a>
                </div>
                <small class="text-muted">Nota: El Excel debe tener la misma cabecera que muestras en tu imagen (Columnas: Fecha, Movimiento, SERIES, Orden, SKU). Solo se procesarán filas donde Movimiento sea 'Egreso'.</small>
            </form>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE PAGOS -->
    <div class="modal fade" id="modalConfirmacionPago" tabindex="-1" aria-labelledby="modalConfirmacionPagoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-primary">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fs-6" id="modalConfirmacionPagoLabel"><i class="bi bi-info-circle"></i> Confirmar Guardado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="modal-body-confirmacion">
                    <!-- Contenido dinámico desde JS -->
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-confirmar-guardar">Confirmar y Guardar</button>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
    window.assetUrl = "{{ asset('storage/') }}";
</script>
<script src="{{asset('js/createegreso.js')}}?v=1.16"></script>
<script src="{{asset('js/createegreso_sku.js')}}?v=1.00"></script>
<script src="{{asset('js/createegreso_pagos.js')}}?v=1.00"></script>
@include('envios.components.modal_new_cliente', ['documentos' => $tipoDocumentos])
@endsection