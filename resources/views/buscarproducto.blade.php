@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="container">
    @if($productos->isNotEmpty())
    <br>
    <div class="row">
        <div class="col-lg-5">
            <x-buscador_producto />
        </div>
        <div class="col-lg-12">
            <h2><a href="javascript:void(0)" onclick="window.history.back()" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> Productos encontrados:</h2>
        </div>

    </div>
    <div class="row pt-2 pb-2">
        <x-filtro_productos :marcas="$filtros['marcas']" :estados="$filtros['estados']" :almacenes="$almacenes" />
    </div>
    <br>
    <div id="container-buscar-producto">
        <x-lista_producto :productos="$productos" :container="'container-buscar-producto'" :tc="$tc" />
    </div>
    @else
    <div class="row d-flex justify-content-between align-items-center" style="height: 80vh;">
        <x-aviso_no_encontrado :mensaje="''" />
    </div>
    @endif
</div>
@include('productos.partials.modal_ubicacion_base')
@include('productos.partials.modal_historial_precios_base')
@include('productos.partials.modal_historial_precio_tienda')
@include('productos.partials.modal_editar_utilidad')
<script src="{{ asset('js/filtro_componente.js') }}"></script>
@endsection