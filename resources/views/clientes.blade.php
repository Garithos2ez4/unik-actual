@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="container">
    <br>
    <div class="row align-items-center mb-3">
        <div class="col-12 col-md-4">
            <h2><i class="bi bi-person-standing"></i> Clientes</h2>
        </div>
        <div class="col-12 col-md-5 mt-2 mt-md-0">
            <form action="{{ url('/clientes') }}" method="GET" class="d-flex">
                <input type="text" name="q" class="form-control me-2" placeholder="Buscar por DNI, Nombre, Apellido, Correo o Celular..." value="{{ $searchQuery ?? '' }}">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Buscar</button>
                @if(!empty($searchQuery))
                    <a href="{{ url('/clientes') }}" class="btn btn-outline-danger ms-2" title="Limpiar Búsqueda"><i class="bi bi-x-circle"></i></a>
                @endif
            </form>
        </div>
        <div class="col-12 col-md-3 text-end mt-2 mt-md-0">
            <x-btn_modal_cliente :clases="'btn-success'" :spanClass="''"/>
        </div>
    </div>
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
                            <div class="col-2">
                                <h6 class="mt-1">Correo</h6>
                            </div>
                            <div class="col-1">
                                <h6 class="mt-1">Acción</h6>
                            </div>
                        </div>
                    </li>
                    @foreach ($clientes as $cli)
                        <li class="list-group-item">
                            <div class="row text-center align-items-center">
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
                                    <small>{{$cli->numeroDocumento}}</small>
                                </div>
                                <div class="col-2">
                                    <small>{{$cli->telefono}}</small>
                                </div>
                                <div class="col-2">
                                    <small class="text-truncate d-block">{{$cli->correo}}</small>
                                </div>
                                <div class="col-1">
                                    <button class="btn btn-sm btn-warning" onclick="openEditCliente({{ json_encode($cli) }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        
    </div>
    <div class="d-flex justify-content-end mb-5">
        <span class="me-3 mt-2 fw-bold text-muted">Total: {{$clientes->total()}}</span>
        {{$clientes->appends(request()->query())->links('pagination::bootstrap-5')}}
    </div>
    

@include('envios.components.modal_new_cliente', ['documentos' => $tipoDocumentos])
<script>
document.getElementById('btn-modal-new-cliente').addEventListener('click',function(x){
    // Este timeout recargará la página independientemente de si era un crear o actualizar
    setTimeout(function(){
        window.location.reload();
    },800);
});

// Reseteamos el formulario de modal_new_cliente cuando se hace click en el botón de agregar
const addClienteBtn = document.querySelector('[data-bs-target="#nuevoClienteModal"]');
if(addClienteBtn) {
    addClienteBtn.addEventListener('click', () => {
        document.getElementById('nuevoClienteModalLabel').textContent = 'Nuevo Cliente';
        document.getElementById('modal-form-create-cliente').reset();
        document.getElementById('modal-form-create-cliente').action = '{{ route("createcliente") }}';
        changeTipeDoc(document.querySelector('select[name="tipodoc"]'));
    });
}

function openEditCliente(cliente) {
    const modal = new bootstrap.Modal(document.getElementById('nuevoClienteModal'));
    document.getElementById('nuevoClienteModalLabel').textContent = 'Editar Cliente';
    
    // Cambiar action form
    const form = document.getElementById('modal-form-create-cliente');
    form.action = '/cliente/update/' + cliente.idCliente;

    // Llenar campos
    form.querySelector('input[name="nombre"]').value = cliente.nombre || '';
    form.querySelector('input[name="apepaterno"]').value = cliente.apellidoPaterno || '';
    form.querySelector('input[name="apematerno"]').value = cliente.apellidoMaterno || '';
    form.querySelector('select[name="tipodoc"]').value = cliente.idTipoDocumento || 1;
    form.querySelector('input[name="numerodoc"]').value = cliente.numeroDocumento || '';
    form.querySelector('input[name="numerotelf"]').value = cliente.telefono || '';
    form.querySelector('input[name="correo"]').value = cliente.correo || '';

    // Disparar el onchange manual para ajustar la vista
    changeTipeDoc(form.querySelector('select[name="tipodoc"]'));
    
    modal.show();
}
</script>
@endsection