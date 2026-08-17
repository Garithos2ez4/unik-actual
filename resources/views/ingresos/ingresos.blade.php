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
                <i class="bi bi-calendar3"></i> <!-- Ãcono de calendario -->
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
            <a class="btn btn-outline-purple me-1" data-bs-toggle="modal" data-bs-target="#buscarPackModal" title="Dividir Pack por Serie">
                <i class="bi bi-scissors"></i><span class="d-none d-md-inline"> Dividir por Serie</span>
            </a>
            <a class="btn btn-outline-success me-1" id="btn-abrir-union-pack" title="Unir componentes en un Pack nuevo">
                <i class="bi bi-boxes"></i><span class="d-none d-md-inline"> Unir en Pack</span>
            </a>
            <a class="btn btn-outline-primary me-1" href="{{ route('compras.deltron') }}" title="Ver Ofertas en Deltron">
                <i class="bi bi-robot"></i><span class="d-none d-md-inline"> Compras Deltron</span>
            </a>
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
    @include('ingresos.modals.buscar_pack')
    @include('ingresos.modals.union_pack')
    @include('ingresos.modals.nuevo_registro')
    @include('ingresos.modals.detalle_registro')
    @include('ingresos.modals.dividir_pack')

</div>

<style>
    .bg-purple {
        background-color: #6f42c1 !important;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: #fff;
    }

    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: #fff;
    }

    .btn-outline-purple {
        color: #6f42c1;
        border-color: #6f42c1;
    }

    .btn-outline-purple:hover {
        background-color: #6f42c1;
        color: #fff;
    }

    .text-info {
        color: #0dcaf0 !important;
    }
</style>

<script src="{{asset('js/ingresos/ingresos.js')}}?v=1.1"></script>
<script src="{{asset('js/filtro_componente.js')}}"></script>
<script src="{{asset('js/ingresos/division_producto.js')}}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('proveedor-select')) {
            new TomSelect('#proveedor-select', {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        }
    });
</script>
@endsection