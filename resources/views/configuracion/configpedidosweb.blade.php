@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-md-12">
            <h2><i class="bi bi-gear-fill"></i> Configuración</h2>
        </div>
    </div>
    <br>
    <div class="col-md-12">
        <x-nav_config :pag="$pagina" />
    </div>
    <br>
    
    @include('configuracion.components.pedidosweb_lista', ['pedidos' => $pedidos])

</div>

@include('configuracion.components.logic.pedidosweb_logic')

@endsection
