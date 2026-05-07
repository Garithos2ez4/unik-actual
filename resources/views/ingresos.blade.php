@extends('layouts.app')

@section('title', 'Ingresos')

@section('content')
<div class="container">
    <div class="bg-secondary" id="hidden-body"
        style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
    </div>
    <br>
    <div class="row mb-2">
        <div class="col-9 col-md-7 col-lg-5 text-end" style="position:relative;z-index:999">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Serial Number..." id="search">
                <ul class="list-group w-100" style="position:absolute;top:100%;z-index:1000" id="suggestions">
                </ul>
            </div>
        </div>
        <div class="col-lg-4 d-none d-lg-block"></div>
        <div class="col-3 col-md-5 col-lg-3 text-end">
            <input type="month" class="form-control hidde-month" id="month" name="month" value="{{$fecha->format('Y-m')}}">
            <button class="btn btn-light border d-md-none" onclick="hiddeInputDate('month')">
                <i class="bi bi-calendar3"></i> <!-- Ícono de calendario -->
            </button>
        </div>
        <div class="col-10 col-lg-6">
            <h2><a href="{{route('documentos', [$fecha->format('Y-m')])}}" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> <i class="bi bi-file-earmark-plus-fill"></i> Ingresos
                <span class="text-capitalize text-secondary fw-light"><em>({{$fecha->translatedFormat('F')}})</em></span>
            </h2>
        </div>
        <div class="col-2 col-lg-6 text-end">
            @foreach ($user->Accesos as $vista)
            @if($vista->idVista == 8)
            <a class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ingresoModal"><i
                    class="bi bi-file-earmark-plus"></i><span class="d-none d-md-inline"> Nuevo Registro</span></a>
            @endif
            @endforeach
        </div>
    </div>
    <form action="{{url()->current()}}" method="get" id="form-filtro-componente">
        <div class="row mb-2">
            <div class="col-6 col-md-3 col-lg-2">
                <small>Usuario</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[usuario]">
                    <option value="">Todos</option>
                    @foreach ($filtros['users'] as $usuario)
                    <option value="{{$usuario->idUser}}">{{$usuario->Usuario->user}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Proveedores</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[proveedor]">
                    <option value="">Todos</option>
                    @foreach ($filtros['proveedores'] as $proveedor)
                    <option value="{{$proveedor->idProveedor}}">{{$proveedor->nombreProveedor}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Almac&eacute;n</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[almacen]">
                    <option value="">Todos</option>
                    @foreach ($filtros['almacenes'] as $almacen)
                    <option value="{{$almacen->idAlmacen}}">{{$almacen->descripcion}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Estado</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[estado]">
                    <option value="">Todos</option>
                    @foreach ($filtros['estados'] as $estado)
                    <option value="{{$estado->estado}}">{{$estado->estado}}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
    <div id="container-lista-ingresos">
        <x-lista_ingresos :registros="$registros" :container="'container-lista-ingresos'" />
    </div>
    <!-- Modal -->
    @foreach ($user->Accesos as $vista)
    @if($vista->idVista == 8)
    <form action="{{route('insertcomprobante')}}" method="POST">
        @csrf
        <div class="modal fade" id="ingresoModal" tabindex="-1" aria-labelledby="ingresoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ingresoModalLabel">Nuevo Registro</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6 col-md-6">
                                <label>Proveedor</label>
                                <select class="form-select" id="proveedor-select" name="proveedor">
                                    <option value="" {{old('proveedor')=='' ? 'selected' : '' }}>-Elige un proveedor-
                                    </option>
                                    @foreach($proveedores as $proveedor)
                                    <option value="{{$proveedor['idProveedor']}}"
                                        {{old('proveedor')==$proveedor['idProveedor'] ? 'selected' : '' }}>
                                        {{$proveedor['nombreProveedor']}}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-6">
                                <label>Documento</label>
                                <select class="form-select" id="documento-select" name="tipocomprobante">
                                    <option value="" {{old('tipocomprobante')=='' ? 'selected' : '' }}>-Elige un
                                        documento-</option>
                                    @foreach($documentos as $doc)
                                    <option value="{{$doc->idTipoComprobante}}" {{old('tipocomprobante')==$doc->
                                            idTipoComprobante ? 'selected' : ''}}>{{$doc->descripcion}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label>Nro Documento</label>
                                <input type="text" class="form-control" id="documento-number" name="numerocomprobante"
                                    value="{{old('numerocomprobante')}}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="btn-save"><i class="bi bi-floppy"></i>
                            Registrar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
    @endforeach

    <form action="{{route('updateregistro')}}" method="POST">
        @csrf
        <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" name="idregistro" id="idregistro-modal-detail" value="">
                            <div class="col-12">
                                <h5 id="titleproduct-modal-detail">[titulo del producto]</h5>
                            </div>
                            <div class="col-6 text-secondary">
                                <h6 id="proveedor-modal-detail">[proveedor]</h6>
                            </div>
                            <div class="col-6 text-end text-secondary">
                                <h6 id="serialnumber-modal-detail">[numero de serie]</h6>
                            </div>
                            <div class="col-6">
                                <span id="user-modal-detail">[usuario]</span>
                            </div>
                            <div class="col-6 text-end">
                                <span id="date-modal-detail">[fechademovimiento]</span>
                            </div>
                            <div class="col-6 pt-2">
                                <label class="form-label fw-bold">Ubicacion:</label>
                                <select id="almacen-modal-detail" class="form-select" disabled>
                                    @foreach ($almacenes as $almacen)
                                    <option value="{{$almacen->idAlmacen}}">{{$almacen->descripcion}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 pt-2">
                                <label class="form-label fw-bold">Estado:</label>
                                <select id="state-modal-detail" name="estado" class="form-select">
                                    @foreach ($estados as $estado)
                                    <option value="{{$estado['value']}}" {{$estado['value']=='ENTREGADO' ? 'disabled' :''}}>
                                        {{$estado['name']}}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mt-3 d-none" id="devolucion-info-block">
                                <div class="alert alert-warning mb-0 p-2">
                                    <h6 class="alert-heading mb-1"><i class="bi bi-info-circle"></i> Info. Devolucin previa</h6>
                                    <small id="devolucion-motivo" class="d-block mb-1"></small>
                                    <small class="d-block"><strong>Apto Venta:</strong> <span id="devolucion-apto"></span></small>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-fallo-entrega">
                                    <label class="form-check-label text-primary fw-bold" style="cursor: pointer" for="check-fallo-entrega">
                                        Fallo de entrega (Autocompletar)
                                    </label>
                                </div>
                                <strong>Observaciones</strong>
                                <textarea name="observacion" maxlength="500" placeholder="Sin observaciones"
                                    id="obs-modal-detail" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="{{asset('js/ingresos.js')}}"></script>
<script src="{{asset('js/filtro_componente.js')}}"></script>
@endsection