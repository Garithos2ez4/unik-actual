@extends('layouts.app')

@section('title', 'Colaboradores - Asignar Pendientes')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-8">
            <h2><i class="bi bi-people-fill"></i> Colaboradores</h2>
            <p class="text-secondary">Asigna tareas o notas a la bandeja de pendientes de otros usuarios.</p>
        </div>
    </div>
    <br>
    <div class="row">
        @if(count($usuarios) < 1)
            <div class="col-12 d-flex justify-content-center align-items-center" style="height: 70vh">
            <x-aviso_no_encontrado :mensaje="'colaboradores'" />
    </div>
    @else
    <div class="col-12">
        <ul class="list-group">
            <li class="list-group-item bg-sistema-uno text-light">
                <div class="row text-center">
                    <div class="col-3 text-start">
                        <h6 class="mt-1">ID</h6>
                    </div>
                    <div class="col-6 text-start">
                        <h6 class="mt-1">Usuario</h6>
                    </div>
                    <div class="col-3">
                        <h6 class="mt-1">Acción</h6>
                    </div>
                </div>
            </li>
            @foreach ($usuarios as $colaborador)
            <li class="list-group-item">
                <div class="row text-center align-items-center">
                    <div class="col-3 text-start">
                        <small>{{$colaborador->idUser}}</small>
                    </div>
                    <div class="col-6 text-start">
                        <small class="fw-bold"><i class="bi bi-person"></i> {{$colaborador->user}}</small>
                    </div>
                    <div class="col-3">
                        <button class="btn btn-sm btn-outline-primary w-100" onclick="asignarPendiente('{{$colaborador->idUser}}', '{{$colaborador->user}}')">
                            <i class="bi bi-journal-bookmark-fill"></i> Asignar Pendientes
                        </button>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
</div>

<script>
    function asignarPendiente(idUser, userName) {
        // Mostrar modal loading logic
        let modalEl = document.getElementById('modalBandeja');
        let loader = document.getElementById('modalBandeja-total-body');

        document.getElementById('id-modal-bandeja').value = idUser;
        document.getElementById('bandejaModalLabel').innerHTML = 'Pendientes de: ' + userName;

        loader.style.display = 'flex';

        // Inicializar el modal de bootstrap
        let modalBandeja = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalBandeja.show();

        fetch("{{ route('getbandeja') }}?id=" + idUser, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                loader.style.display = 'none';
                if (typeof quill !== 'undefined') {
                    quill.root.innerHTML = data.bandeja || '';
                } else {
                    document.getElementById('text-bandeja').innerHTML = data.bandeja || '';
                }
            })
            .catch(error => {
                loader.style.display = 'none';
                console.log('Error:', error);
            });
    }
</script>
@endsection