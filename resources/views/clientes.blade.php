@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-8">
            <h2><i class="bi bi-person-standing"></i> Clientes</h2>
        </div>
        <div class="col-4 text-end">
            <x-btn_modal_cliente :clases="'btn-success'" :spanClass="''"/>
        </div>
    </div>
    <br>
    <div class="row">
        @if($clientes->total() < 1)
            <div class="col-12 d-flex justify-content-center align-items-center" style="height: 70vh">
                <x-aviso_no_encontrado :mensaje="'clientes'" />
            </div>
        @else
            <div class="col-12">
                <ul class="list-group">
                    <li class="list-group-item bg-sistema-uno text-light">
                        <div class="row text-center">
                            <div class="col-2 text-start">
                                <h6 class="mt-1">Nombre</h6>
                            </div>
                            <div class="col-2">
                                <h6 class="mt-1">Apellidos</h6>
                            </div>
                            <div class="col-1">
                                <h6 class="mt-1">Tipo</h6>
                            </div>
                            <div class="col-2">
                                <h6 class="mt-1">Documento</h6>
                            </div>
                            <div class="col-2">
                                <h6 class="mt-1">Tel&eacute;fono</h6>
                            </div>
                            <div class="col-3">
                                <h6 class="mt-1">Correo</h6>
                            </div>
                        </div>
                    </li>
                    @foreach ($clientes as $cli)
                        <li class="list-group-item">
                            <div class="row text-center">
                                <div class="col-2 text-start">
                                    <small>{{$cli->nombre}}</small>
                                </div>
                                <div class="col-2">
                                    <small>{{$cli->apellidoPaterno.' '.$cli->apellidoMaterno}}</small>
                                </div>
                                <div class="col-1 text-truncate">
                                    
                                    <small>{{$cli->TipoDocumento->descripcion}}</small>
                                </div>

                                <div class="col-2">
                                   <!--  <input type="number" name="documento" id="documento">-->
                                    <small>{{$cli->numeroDocumento}}</small>
                                </div>
                                <div class="col-2">
                                    <small>{{$cli->telefono}}</small>
                                </div>
                                <div class="col-3">
                                    <small>{{$cli->correo}}</small>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        
    </div>
    <div class="d-flex justify-content-end mb-5">
        <span class="me-3 mb-3">{{$clientes->total()}}</span>
        {{$clientes->links('pagination::bootstrap-5')}}
    </div>
    

@include('envios.components.modal_new_cliente', ['documentos' => $tipoDocumentos])
<script>
document.getElementById('btn-modal-new-cliente').addEventListener('click',function(x){
    setTimeout(function(){
        window.location.reload();
    },500);
});
</script>
@endsection