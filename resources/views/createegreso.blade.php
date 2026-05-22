@extends('layouts.app')

@section('title', 'Nuevo Egreso')

@section('content')
<div class="container">
    <div class="bg-secondary" id="hidden-body"
        style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
    </div>
    <br>
    <div class="row">
        <div class="col-5 col-md-7">
            <h2>Nuevo egreso</h2>
        </div>
        <div class="col-7 col-md-5 mb-2" style="position:relative">
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
    <form action="{{ route('insertegreso') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-3 mb-2">
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
            <div class="col-3 mb-2">
                <label>Cliente (Opcional)</label>
                <div class="input-group" style="position:relative">
                    <input type="text" oninput="searchClienteAjax(this)" id="input-cliente-egreso" class="form-control input-egreso" placeholder="Nombre o Documento">
                    <div class="input-group-text" id="btn-clear-cliente" style="cursor:pointer; display:none;" onclick="clearCliente()">
                        <i class="bi bi-x"></i>
                    </div>
                    <input type="hidden" name="idCliente" id="hidden-id-cliente" value="">
                    <ul class="list-group" id="suggestions-cliente"
                        style="position:absolute;z-index:1000;top:100%;left:0;width:100%"></ul>
                </div>
            </div>
            <div class="col-2 mb-2">
                <label>Numero de Orden</label>
                <input type="text" placeholder="Nro de Orden" id="input-numero-orden"
                    name="numeroorden" class="form-control input-egreso cab-form" required>
            </div>
            <div class="col-2 mb-2">
                <label>Fecha de pedido</label>
                <input type="date" name="fechapedido" id="fechapedido" class="form-control input-egreso cab-form"
                    min="2024-01-01" max="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-2 mb-2">
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
</div>
<script>
    window.assetUrl = "{{ asset('storage/') }}";
</script>
<script src="{{asset('js/createegreso.js')}}?v=1.11"></script>
@endsection