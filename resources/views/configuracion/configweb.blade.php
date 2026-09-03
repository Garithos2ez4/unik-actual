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
    <div class="row border shadow rounded-3 pt-2 pb-2" id="correos-empresa">
        <form action="{{route('updatecorreos')}}" method="POST">
            @csrf
            <div class="col-md-12">
                <div class="row ">
                    <div class="col-8 col-md-6">
                        <h3>Correos Web</h3>
                        <p class="text-secondary">Correos corporativos registrados para la web.</p>
                    </div>
                    <div class="col-4 col-md-6 text-end">
                        <button class="btn btn-success" type="submit" id="btnSaveCorreos"> <i class="bi bi-floppy"></i></button>
                        <button class="btn btn-secondary" type="button" onclick="correosEmpresaDisabled()"> <i class="bi bi-pencil"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="row">
                    @foreach($empresas as $empresa)
                    <div class="col-md-6">
                        <label>{{$empresa->nombreComercial}}:</label>
                        <input type="text" name="correos[{{$empresa->idEmpresa}}]" maxlength="50" class="form-control input-edit" value="{{$empresa->correoEmpresa}}">
                    </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div>
    <br>

    <br>
    {{-- <div class="row border shadow rounded-3 pt-2 pb-2" id="deltron-empresa">
        <form action="{{route('updateclavedeltron')}}" method="POST">
            @csrf
            <div class="col-md-12">
                <div class="row ">
                    <div class="col-8 col-md-6">
                        <h3>Contraseña Deltron</h3>
                        <p class="text-secondary">Actualiza la contraseña del bot de scraping de Deltron (cada 3 meses).</p>
                    </div>
                    <div class="col-4 col-md-6 text-end">
                        <button class="btn btn-success" type="submit"> <i class="bi bi-floppy"></i></button>
                        <button class="btn btn-secondary" type="button" onclick="document.querySelectorAll('.input-deltron').forEach(el => el.disabled = false)"> <i class="bi bi-pencil"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="col-md-6 mb-3">
                    <label>Clave actual:</label>
                    <input type="text" name="claveDeltron" maxlength="50" class="form-control input-deltron" value="{{$claveDeltronActual}}" disabled>
                </div>
            </div>
        </form>
    </div> --}}
    <br>
    <div class="row border shadow rounded-3 pt-2 pb-2">
        <div class="col-md-12">
            <div class="row ">
                <div class="col-md-12 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">Datos Bancarios</h3>
                        <p class="text-secondary mb-0">Números de cuenta registrados para la web.</p>
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCuentaBancariaModal" title="Añadir Cuenta">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="row ">
                <div class="accordion accordion-flush" id="accordionFlushExample">
                    @foreach($empresas as $empresa)
                    <div class="accordion-item">
                        <h2 class="accordion-header d-flex justify-content-between align-items-center" id="flush-headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-empresa-{{$empresa->idEmpresa}}" aria-expanded="false" aria-controls="flush-empresa-{{$empresa->idEmpresa}}">
                                <h5 class="mb-0">{{$empresa->nombreComercial}}</h5>
                            </button>
                        </h2>
                        <div id="flush-empresa-{{$empresa->idEmpresa}}" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                <ul class="list-group">
                                    <li class="list-group-item bg-sistema-uno text-light">
                                        <div class="row text-center">
                                            <div class="col-3 col-md-2 text-start">
                                                <h6>Bancos</h6>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <h6>Números de cuenta</h6>
                                            </div>
                                            <div class="col-3 col-md-2">
                                                <h6>Tipo</h6>
                                            </div>
                                            <div class="col-md-1 text-start d-none d-md-block">
                                                <h6>Moneda</h6>
                                            </div>
                                            <div class="col-md-3 d-none d-md-block">
                                                <h6>Titular</h6>
                                            </div>
                                            <div class="col-md-1 d-none d-md-block">
                                                <h6>Editar</h6>
                                            </div>
                                        </div>
                                    </li>
                                    @foreach($empresa->CuentasTransferencia->sortBy('idBanco') as $cuenta)
                                    <li class="list-group-item {{$cuenta->tipoCuenta == 'INTERBANCARIA' ? 'bg-list' : ''}}">
                                        <div class="row">
                                            <div class="col-3 col-md-2 fw-bold">
                                                <small style="color:{{$cuenta->Banco->colorBanco}}">{{$cuenta->Banco->nombreBanco}}</small>
                                            </div>
                                            <div class="col-6 col-md-3 text-center">
                                                <small>{{$cuenta->numeroCuenta}}</small>
                                            </div>
                                            <div class="col-3 col-md-2 text-center truncate">
                                                <small>{{$cuenta->tipoCuenta}}</small>
                                            </div>
                                            <div class="col-3 col-md-1 text-start">
                                                <small>{{$cuenta->tipoMoneda}}</small>
                                            </div>
                                            <div class="col-6 col-md-3 text-center">
                                                <small>{{$cuenta->titular}}</small>
                                            </div>
                                            <div class="col-3 col-md-1 text-center">
                                                <button class="btn" onclick="sendDataToModalCuentas({{$cuenta->idCuentaBancaria}},'{{$cuenta->Banco->nombreBanco}}','{{$cuenta->tipoCuenta}}','{{$cuenta->titular}}','{{$cuenta->numeroCuenta}}')"
                                                    data-bs-toggle="modal" data-bs-target="#cuentasBancariasModal">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <br>

    <!-- Nueva Sección: Tipos de Método de Pago -->
    <div class="row border shadow rounded-3 pt-2 pb-2">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Tipos de Métodos de Pago</h3>
                <p class="text-secondary mb-0">Categorías de métodos de pago (ej. Efectivo, Tarjeta POS).</p>
            </div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTipoMetodoPagoModal" title="Añadir Tipo">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        <div class="col-md-12 mt-3">
            <ul class="list-group">
                <li class="list-group-item bg-sistema-uno text-light">
                    <div class="row text-center">
                        <div class="col-10">
                            <h6>Tipo de Método</h6>
                        </div>
                        <div class="col-2">
                            <h6>Editar</h6>
                        </div>
                    </div>
                </li>
                @foreach($tiposMetodoPago as $tipo)
                <li class="list-group-item">
                    <div class="row text-center align-items-center">
                        <div class="col-10 fw-bold">{{ $tipo->nombreTipo }}</div>
                        <div class="col-2">
                            <button class="btn" onclick="openEditTipoMetodoPagoModal({{$tipo->idTipoMetodo}}, '{{$tipo->nombreTipo}}')" data-bs-toggle="modal" data-bs-target="#editTipoMetodoPagoModal">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    <br>

    <!-- Nueva Sección: Métodos de Pago -->
    <div class="row border shadow rounded-3 pt-2 pb-2">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Métodos de Pago</h3>
                <p class="text-secondary mb-0">Métodos de pago habilitados para el registro de ventas y egresos.</p>
            </div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMetodoPagoModal" title="Añadir Método">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        <div class="col-md-12 mt-3">
            <ul class="list-group">
                <li class="list-group-item bg-sistema-uno text-light">
                    <div class="row text-center">
                        <div class="col-3">
                            <h6>Método</h6>
                        </div>
                        <div class="col-3">
                            <h6>Tipo</h6>
                        </div>
                        <div class="col-3">
                            <h6>Banco</h6>
                        </div>
                        <div class="col-3">
                            <h6>Editar</h6>
                        </div>
                    </div>
                </li>
                @foreach($metodosPago as $metodo)
                <li class="list-group-item {{$metodo->estado == 0 ? 'bg-list text-muted' : ''}}">
                    <div class="row text-center align-items-center">
                        <div class="col-3 fw-bold">{{ $metodo->nombreMetodo }} {!! $metodo->estado == 0 ? '<small>(Inactivo)</small>' : '' !!}</div>
                        <div class="col-3"><small>{{ $metodo->TipoMetodoPago->nombreTipo ?? 'N/A' }}</small></div>
                        <div class="col-3"><small>{{ $metodo->Banco->nombreBanco ?? '-' }}</small></div>
                        <div class="col-3">
                            <button class="btn" onclick="openEditMetodoPagoModal({{$metodo->idMetodoPago}}, '{{$metodo->nombreMetodo}}', '{{$metodo->idTipoMetodo}}', '{{$metodo->idBanco}}', {{$metodo->estado}})" data-bs-toggle="modal" data-bs-target="#editMetodoPagoModal">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    <br>

    <form action="{{route('updatecuentasbancarias')}}" method="POST">
        @csrf
        <div class="modal fade" id="cuentasBancariasModal" tabindex="-1" aria-labelledby="cuentasBancariasModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <h5 class="modal-title" id="cuentasBancariasModalLabel">Cuenta <span id="span-modal-cuenta"></span></h5>
                            <small id="small-modal-cuenta" class="text-secondary"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Titular:</label>
                                <input type="text" class="form-control" maxlength="50" name="titular" value="" id="input-titular-modal-cuenta">
                                <input type="hidden" name="id" id="hidden-modal-cuenta" value="">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Numero de cuenta:</label>
                                <input type="text" class="form-control" maxlength="30" name="cuenta" value="" id="input-cuenta-modal-cuenta">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar <i class="bi bi-floppy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form action="{{route('insertcuentasbancarias')}}" method="POST">
        @csrf
        <div class="modal fade" id="addCuentaBancariaModal" tabindex="-1" aria-labelledby="addCuentaBancariaModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCuentaBancariaModalLabel">Añadir Cuenta Bancaria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Empresa:</label>
                                <select class="form-select" name="idEmpresa" required>
                                    <option value="">Seleccione Empresa...</option>
                                    @foreach($empresas as $emp)
                                    <option value="{{$emp->idEmpresa}}">{{$emp->nombreComercial}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-2">
                                <label class="form-label">Banco:</label>
                                <select class="form-select" name="idBanco" required>
                                    <option value="">Seleccione Banco...</option>
                                    @foreach($bancos as $banco)
                                    <option value="{{$banco->idBanco}}">{{$banco->nombreBanco}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-2">
                                <label class="form-label">Tipo de Cuenta:</label>
                                <select class="form-select" name="tipoCuenta" required>
                                    <option value="BANCARIA">BANCARIA</option>
                                    <option value="INTERBANCARIA">INTERBANCARIA</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-2">
                                <label class="form-label">Moneda:</label>
                                <select class="form-select" name="tipoMoneda" required>
                                    <option value="SOLES">SOLES</option>
                                    <option value="DOLARES">DOLARES</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-2">
                                <label class="form-label">Titular:</label>
                                <input type="text" class="form-control" maxlength="50" name="titular" required>
                            </div>

                            <div class="col-md-12 mb-2">
                                <label class="form-label">Numero de cuenta:</label>
                                <input type="text" class="form-control" maxlength="30" name="cuenta" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar <i class="bi bi-floppy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal Añadir Método de Pago -->
    <form action="{{route('insertmetodopago')}}" method="POST">
        @csrf
        <div class="modal fade" id="addMetodoPagoModal" tabindex="-1" aria-labelledby="addMetodoPagoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMetodoPagoModalLabel">Añadir Método de Pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Nombre del Método:</label>
                                <input type="text" class="form-control" maxlength="50" name="nombreMetodo" required placeholder="Ej: Izypay">
                            </div>

                            <div class="col-md-12 mb-2">
                                <label class="form-label">Tipo de Método:</label>
                                <select class="form-select" name="idTipoMetodo" required>
                                    <option value="">Seleccione Tipo...</option>
                                    @foreach($tiposMetodoPago as $tipo)
                                    <option value="{{$tipo->idTipoMetodo}}">{{$tipo->nombreTipo}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-2">
                                <label class="form-label">Banco Asociado (Opcional):</label>
                                <select class="form-select" name="idBanco">
                                    <option value="">Ninguno</option>
                                    @foreach($bancos as $banco)
                                    <option value="{{$banco->idBanco}}">{{$banco->nombreBanco}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar <i class="bi bi-floppy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal Añadir Tipo de Método de Pago -->
    <form action="{{route('inserttipometodopago')}}" method="POST">
        @csrf
        <div class="modal fade" id="addTipoMetodoPagoModal" tabindex="-1" aria-labelledby="addTipoMetodoPagoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTipoMetodoPagoModalLabel">Añadir Tipo de Método de Pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Nombre del Tipo:</label>
                                <input type="text" class="form-control" maxlength="50" name="nombreTipo" required placeholder="Ej: LINK DE PAGO VIRTUAL">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar <i class="bi bi-floppy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal Editar Método de Pago -->
    <form action="{{route('updatemetodopago')}}" method="POST">
        @csrf
        <div class="modal fade" id="editMetodoPagoModal" tabindex="-1" aria-labelledby="editMetodoPagoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMetodoPagoModalLabel">Editar Método de Pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" name="idMetodoPago" id="hidden-edit-metodo-id">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Nombre del Método:</label>
                                <input type="text" class="form-control" maxlength="50" name="nombreMetodo" id="input-edit-metodo-nombre" required>
                            </div>

                            <div class="col-md-12 mb-2">
                                <label class="form-label">Tipo de Método:</label>
                                <select class="form-select" name="idTipoMetodo" id="select-edit-metodo-tipo" required>
                                    <option value="">Seleccione Tipo...</option>
                                    @foreach($tiposMetodoPago as $tipo)
                                    <option value="{{$tipo->idTipoMetodo}}">{{$tipo->nombreTipo}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-2">
                                <label class="form-label">Banco Asociado (Opcional):</label>
                                <select class="form-select" name="idBanco" id="select-edit-metodo-banco">
                                    <option value="">Ninguno</option>
                                    @foreach($bancos as $banco)
                                    <option value="{{$banco->idBanco}}">{{$banco->nombreBanco}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-2 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="estado" id="switch-edit-metodo-estado" value="1">
                                    <label class="form-check-label" for="switch-edit-metodo-estado">Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar <i class="bi bi-floppy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal Editar Tipo de Método de Pago -->
    <form action="{{route('updatetipometodopago')}}" method="POST">
        @csrf
        <div class="modal fade" id="editTipoMetodoPagoModal" tabindex="-1" aria-labelledby="editTipoMetodoPagoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTipoMetodoPagoModalLabel">Editar Tipo de Método de Pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" name="idTipoMetodo" id="hidden-edit-tipo-metodo-id">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Nombre del Tipo:</label>
                                <input type="text" class="form-control" maxlength="50" name="nombreTipo" id="input-edit-tipo-metodo-nombre" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar <i class="bi bi-floppy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="{{asset('js/configweb.js')}}"></script>
@endsection