@extends('layouts.app')

@section('title', 'Plataformas')

@section('content')
<div class="container">
    <br>
    <br>
    <div class="row">
        <div class="col-6 col-md-9 mb-2">
            <h2><i class="bi bi-shop"></i> Plataformas</h2>
        </div>
        <div class="col-6 col-md-3 mb-2 text-end">
            <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#plataformaModal"><i class="bi bi-clipboard-plus-fill"></i> Nueva Plataforma</button>
        </div>
    </div>
    <br>
    <div class="row">
        @foreach($plataformas as $plata)
        <div class="col-md-12 border shadow mb-3 rounded-3">
            <div class="row border-bottom shadow pb-1 pt-1">
              <div class="col-3 col-md-1 pe-0">
                <img src="{{asset('storage/'.$plata->imagenPlataforma)}}" alt="{{$plata->nombrePlataforma}}" class="rounded-3 w-100 mt-1" >
            </div>
                <div class="col-6 col-md-9 d-flex align-items-center justify-content-start pt-2">
                    <h3>{{$plata->nombrePlataforma}}</h3>
                </div>
                <div class="col-3 col-md-2 text-end">
                  @if($plata->nombrePlataforma == 'Falabella')
                    <a href="{{ route('plataformas.falabella.seller') }}" class="btn btn-primary mt-1 me-1">
                      <i class="bi bi-box-arrow-in-right"></i> Seller Center
                    </a>
                  @endif
                  <button class="btn btn-warning mt-1" type="button" data-bs-toggle="modal" data-bs-target="#cuentasModal" onclick='sendDataModalAcount("{{$plata->nombrePlataforma}}",@json($plata->CuentasPlataforma))'>
                    <i class="bi bi-people-fill"></i>
                  </button>
                </div>
            </div>
            <div class="row">
                <div class="col-10 col-md-11">
                    <div class="row mt-3 mb-2">
                        @foreach($plata->CuentasPlataforma as $cuenta)
                        <div class="col-5 col-md-2 border ms-2 mb-2">
                            <h6>{{$cuenta->nombreCuenta}}</h6>
                            <small class="text-secondary">{{$cuenta->estadoCuenta}}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-2 col-md-1 text-end">
                    <button class="btn btn-secondary text-light mt-3 mb-2" type="button" onclick="nuevaCuenta('{{$plata->nombrePlataforma}}',{{$plata->idPlataforma}})" data-bs-toggle="modal" data-bs-target="#nuevaCuentaModal"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <!-- Modal -->
    <form action="{{route('updatecuenta')}}" id="form-create"  method="POST">
    @csrf
    <div class="modal fade" id="cuentasModal" tabindex="-1" aria-labelledby="cuentasModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header ">
            <h5 class="modal-title w-100 text-start" id="cuentasModalLabel">Cuentas <span id="span-modal-cuentas"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body ps-0 pe-0 pt-0 pb-0">
            <ul class="list-group list-group-flush" id="ul-modal-cuentas">
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill"></i> Actualizar</button>
          </div>
        </div>
      </div>
    </div>
    </form>
    <div class="modal fade" id="plataformaModal" tabindex="-1" aria-labelledby="plataformaModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="plataformaModalLabel">Nueva plataforma</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            ...
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </div>
    </div>
    <form action="{{route('createcuenta')}}" id="form-create"  method="POST">
    @csrf
    <div class="modal fade" id="nuevaCuentaModal" tabindex="-1" aria-labelledby="nuevaCuentaModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="nuevaCuentaModalLabel">Nueva Cuenta <span id="tituloNuevaCuenta"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="plataforma" id="hiddenNuevaCuenta">
            <label class="form-label">Nombre de la Cuenta</label>
            <input type="text" name="cuenta" class="form-control" id="textNuevaCuenta">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill"></i> Agregar</button>
          </div>
        </div>
      </div>
    </div>
    </form>
</div>
<script>
    function sendDataModalAcount(titulo,cuentas){
      let ulList = document.getElementById('ul-modal-cuentas');
      let spanTitle = document.getElementById('span-modal-cuentas');

      ulList.textContent = '';
      spanTitle.textContent = '';

      spanTitle.textContent = titulo;
      
      cuentas.forEach(function(c){
        let liCuenta = document.createElement('li');
        liCuenta.classList.add('list-group-item');

        let divRow = document.createElement('div');
        divRow.classList.add('row');
        let hiddenAcount = document.createElement('input');
        hiddenAcount.type = 'hidden';
        hiddenAcount.value = c.estadoCuenta;
        hiddenAcount.name = 'cuentas['+ c.idCuentaPlataforma +']';
        hiddenAcount.id = 'hidden-modal-cuentas' + c.idCuentaPlataforma;
        divRow.appendChild(hiddenAcount);

        let divColLabel = document.createElement('div');
        divColLabel.classList.add('col-6');
        let labelAcount = document.createElement('h6');
        labelAcount.textContent = c.nombreCuenta;
        let smallAcount = document.createElement('small');
        smallAcount.classList.add(c.estadoCuenta == 'ACTIVO' ? 'text-success' : 'text-danger');
        smallAcount.textContent = c.estadoCuenta;
        smallAcount.id = 'small-modal-cuentas' + c.idCuentaPlataforma;
        divColLabel.appendChild(labelAcount);
        divColLabel.appendChild(smallAcount);

        let divColSwitch = document.createElement('div');
        divColSwitch.classList.add('col-6','text-end','form-check','form-switch','d-flex','justify-content-end');
        let switchAcount = document.createElement('input');
        switchAcount.type = 'checkbox';
        switchAcount.classList.add('form-check-input');
        switchAcount.checked = c.estadoCuenta == 'ACTIVO' ? true : false;
        switchAcount.addEventListener('change',function(event){changeState(c.idCuentaPlataforma,event);});
        divColSwitch.appendChild(switchAcount);

        divRow.appendChild(divColLabel);
        divRow.appendChild(divColSwitch);
        liCuenta.appendChild(divRow);
        ulList.appendChild(liCuenta);
      });
    }

    function changeState(id,event){
      let smallState = document.getElementById('small-modal-cuentas' + id);
      let hiddenState = document.getElementById('hidden-modal-cuentas' + id);

      if (event.target.checked) {
        hiddenState.value = 'ACTIVO';
        smallState.textContent = 'ACTIVO';
        smallState.classList.remove('text-danger');
        smallState.classList.add('text-success');
      }else{
        hiddenState.value = 'INACTIVO';
        smallState.textContent = 'INACTIVO';
        smallState.classList.remove('text-success');
        smallState.classList.add('text-danger');
      }
    }
    
    function nuevaCuenta(titulo,id){
        let tituloModalCuentaNueva = document.getElementById('tituloNuevaCuenta');
        let hiddenModalCuentaNueva = document.getElementById('hiddenNuevaCuenta');
        
        tituloModalCuentaNueva.textContent = titulo;
        hiddenModalCuentaNueva.value = id;
        
    }
    
    // document.addEventListener('DOMContentLoaded', function() {
    // });
</script>
@endsection