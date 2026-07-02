@extends('layouts.app')

@section('title', 'Casi agotados')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-12">
            <h2><a href="{{route('dashboard')}}" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> Productos por agotarse</h2>
            <label class="text-secondary"><a href="javascript:void(0)" class="invisible ms-md-4 ms-3"><i class="bi bi-arrow-left-circle"></i></a> Productos por agotarse</label>
        </div>
    </div>
    <br>
    <div id="container-list-products-dashboard">
        <x-lista_producto :productos="$productos" :tc="$tc" :container="'container-list-products-dashboard'" />
    </div>
</div>
@include('productos.partials.modal_ubicacion_base')
@include('productos.partials.modal_historial_precios_base')
@endsection