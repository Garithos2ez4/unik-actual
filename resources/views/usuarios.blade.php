@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="container">
    <br>
    <br>
    <div class="row">
        <div class="col-6">
            <h2><i class="bi bi-person-fill"></i> Usuarios</h2>
        </div>
        <div class="col-6 text-end">
            <a href="{{route('nuevousuario')}}" class="btn btn-success"><i class="bi bi-person-plus-fill"></i> Nuevo usuario</a>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-12 col-md-12">
            @php $color ='';
            $estado = '';
            @endphp
            @foreach($usuarios as $us)
            <div class="row w-100 border shadow rounded-3 ms-0 pt-2 pb-2 mb-3">
                <div class="col-6 col-md-7">
                    <h4><i class="bi bi-person-badge"></i> {{$us->user}}</h4>
                </div>
                <div class="col-6 col-md-5 pt-1 d-flex justify-content-end text-end">
                    <h5 class="text-end me-2 {{ $us->estadoUsuario == true ? 'text-success' : 'text-danger' }}">{{ $us->estadoUsuario == true ? 'ACTIVO' : 'INACTIVO' }}</h5>
                    <button class="btn btn-info text-light" 
                    onclick='getUser({{$us->idUser}},"{{ $us->user }}",{{$us->estadoUsuario ? 1 : 0}},@json($us->Accesos))'
                    data-clicked="false" type="button" data-bs-toggle="modal" data-bs-target="#modalUpdateUser"><i class="bi bi-pencil-fill"></i></button>
                </div>
                <div class="col-md-12">
                    <h6 class="text-secondary mb-1">Accesos</h6>
                    <div class="row">
                    @foreach ($us->Accesos as $acceso)
                        <div class="col-4 col-md-3">
                            <div class="row ms-1 mb-1 border border-secondary rounded-2">
                                <small>{{$acceso->Vista->descripcion}}</small>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
                <div class="col-12 col-md-12 d-flex flex-column justify-content-end  pt-2">
                    <h6 class="text-secondary text-end"><a href="#" data-clicked="false" onclick="getIdPass({{ $us->idUser }},'id-modal-password'); return false;" type="button" data-bs-toggle="modal" data-bs-target="#modalNewPass">Reestablecer contraseña</a></h6>
                </div>
            </div>
            @endforeach
        </div>
    </div>
<form action="{{route('updateuser')}}" id="form-create"  method="POST">
 @csrf
<div class="modal fade" id="modalUpdateUser" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title " id="modalUpdateUserLabel">Modificar usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-md-6 mb-2">
                <input type="hidden" name="id" value="1" class="form-control" id="idUpdate">
                <label class="form-label">Usuario</label>
                <input type="text" name="user" class="form-control" id="userUpdate">
            </div>
            <div class="col-md-6">
                <label for="estado" class="form-label" >Estado:</label>
                <select name="estado" id="estadoUpdate" id="cargo" class="form-select">
                    <option value="true">ACTIVO</option>
                    <option value="false">INACTIVO</option>
                </select>
            </div>
            <div class="col-md-12">
                <h6>Accesos</h6>
                <div class="row">
                    @foreach ($vistas as $vista)
                        <div class="col-6 col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input access-check" type="checkbox" name="access[]" value="{{$vista->idVista}}" >
                                <label class="form-check-label" >{{$vista->descripcion}}</label>
                              </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="cancelarModal()" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" id="btnReestablecer" class="btn btn-primary"><i class="bi bi-floppy-fill"></i> Actualizar</button>
      </div>
    </div>
  </div>
</div>
</form>
</div>
<script>
    function getUser(id,user,estado,vistas){
        let inputHidden = document.getElementById('idUpdate');
        let inputUser = document.getElementById('userUpdate');
        let selectEstado = document.getElementById('estadoUpdate');
        let checksAccess = document.querySelectorAll('.access-check');
        
        inputHidden.value = id;
        inputUser.value = user;
        inputUser.disabled = true;
        
        if(estado){
            selectEstado.value = 'true';
        }else{
            selectEstado.value = 'false';
        }

        checksAccess.forEach(function(x){
            let check = false;
            vistas.forEach(function(z){
                if(x.value == z.idVista){
                    check = true;
                }
            });

            x.checked = check;
        });
    }

    function validateAccess(id){

    }
</script>
@include('partials.modal_new_pass')
@endsection