@extends('layouts.app')

@section('title', 'Editar Envío a Provincia')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-primary text-white p-4 border-0">
                    <h3 class="mb-0"><i class="bi bi-truck"></i> Editar Envío a Provincia</h3>
                    <small>Sección: ACTUALIZAR DESPACHO</small>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('envios.update', $envio->idEnvioProvincia) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-person-fill"></i> 1. Información del Cliente y Envío</h6>

                            <!-- Cliente -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Buscar Cliente <span class="text-danger">*</span></label>
                                <div class="input-group" id="div-input-group-cliente" style="position: relative">
                                    <input type="text" class="form-control" value="{{ $envio->Cliente->numeroDocumento ?? $envio->Cliente->nombre }}" placeholder="Buscar por Nro Documento o Nombre..." id="input-search-cliente" autocomplete="off">
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#nuevoClienteModal" title="Nuevo Cliente">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </button>
                                    <ul class="list-group w-100 shadow" style="position: absolute; top:100%; z-index: 1000;" id="suggestion-cliente"></ul>
                                </div>
                                <input type="hidden" name="idCliente" id="input-cliente-id" value="{{ $envio->idCliente }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombres del Cliente</label>
                                <input type="text" id="input-cliente-nombre" class="form-control bg-light" value="{{ $envio->Cliente->nombre }} {{ $envio->Cliente->apellidoPaterno ?? '' }} {{ $envio->Cliente->apellidoMaterno ?? '' }}" placeholder="Se completará automáticamente" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Celular / Contacto</label>
                                <input type="text" id="input-cliente-telefono" class="form-control bg-light" value="{{ $envio->Cliente->telefono ?? '' }}" placeholder="Número de contacto" readonly>
                            </div>

                            <!-- Plataforma / Cuenta -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Plataforma de Venta <span class="text-danger">*</span></label>
                                <select name="idPlataforma" id="idPlataforma" class="form-select" required onchange="filterAccounts()">
                                    <option value="">Seleccione plataforma...</option>
                                    @foreach($plataformas as $p)
                                        <option value="{{ $p->idPlataforma }}" {{ $envio->idPlataforma == $p->idPlataforma ? 'selected' : '' }}>{{ $p->nombrePlataforma }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cuenta Wasap/Venta</label>
                                <select name="idCuentaPlataforma" id="idCuentaPlataforma" class="form-select">
                                    <option value="">Seleccione cuenta...</option>
                                    @foreach($plataformas as $p)
                                       @foreach($p->CuentasPlataforma as $c)
                                           <option value="{{ $c->idCuentaPlataforma }}" data-plataforma="{{ $p->idPlataforma }}" {{ $envio->idCuentaPlataforma == $c->idCuentaPlataforma ? 'selected' : '' }}>{{ $c->nombreCuenta }} ({{ $p->nombrePlataforma }})</option>
                                       @endforeach
                                    @endforeach
                                </select>
                            </div>

                            <!-- Ubigeo Geográfico -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Departamento <span class="text-danger">*</span></label>
                                <select id="select-departamento" class="form-select" required onchange="cargarProvincias(this.value)">
                                    <option value="">Seleccione departamento...</option>
                                    @php
                                        $selectedDeptoId = optional(optional(optional($envio->Destino)->Provincia)->Departamento)->idDepartamento;
                                    @endphp
                                    @foreach($departamentos as $depto)
                                        <option value="{{ $depto->idDepartamento }}" {{ $selectedDeptoId == $depto->idDepartamento ? 'selected' : '' }}>{{ $depto->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Provincia <span class="text-danger">*</span></label>
                                <select id="select-provincia" class="form-select" required onchange="cargarDestinos(this.value)">
                                    <option value="">Seleccione provincia...</option>
                                    @php
                                        $selectedProvId = optional(optional($envio->Destino)->Provincia)->idProvincia;
                                    @endphp
                                    @foreach($provincias as $prov)
                                        <option value="{{ $prov->idProvincia }}" {{ $selectedProvId == $prov->idProvincia ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Destino (Distrito) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="idDestino" id="select-destino" class="form-select" required onchange="cargarSubAgencias()">
                                        <option value="">Seleccione destino...</option>
                                        @foreach($destinos as $destino)
                                            <option value="{{ $destino->idDestino }}" {{ $envio->idDestino == $destino->idDestino ? 'selected' : '' }}>{{ $destino->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewDestino" title="Nuevo Destino">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Agencia y Oficina/Sucursal -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Agencia de Transporte <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="idAgencia" class="form-select" required onchange="cargarSubAgencias()">
                                        <option value="">Seleccione agencia...</option>
                                        @foreach($agencias as $agencia)
                                            <option value="{{ $agencia->idAgencia }}" {{ $envio->idAgencia == $agencia->idAgencia ? 'selected' : '' }}>{{ $agencia->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewAgencia" title="Nueva Agencia">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Oficina / Sucursal <span class="text-muted">(Opcional)</span></label>
                                <div class="input-group">
                                    <select name="idSubAgencia" id="select-subagencia" class="form-select" {{ $subagencias->isEmpty() ? 'disabled' : '' }}>
                                        @if($subagencias->isEmpty())
                                            <option value="">Primero elija Agencia y Distrito...</option>
                                        @else
                                            <option value="">Seleccione oficina...</option>
                                            @foreach($subagencias as $sub)
                                                <option value="{{ $sub->idSubAgencia }}" {{ $envio->idSubAgencia == $sub->idSubAgencia ? 'selected' : '' }}>{{ $sub->nombre_oficina }} ({{ $sub->direccion }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewSubAgencia" title="Nueva Oficina/Sucursal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-file-earmark-text-fill"></i> 2. Información de la Guía</h6>

                            <!-- Guia -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Número de Guía</label>
                                <input type="text" name="numero_guia" class="form-control" value="{{ $envio->numero_guia }}" placeholder="Número de seguimiento">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Clave</label>
                                <input type="text" name="clave" class="form-control" value="{{ $envio->clave }}" placeholder="Clave para recojo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dato Adicional</label>
                                <input type="text" name="dato_adicional" class="form-control" value="{{ $envio->dato_adicional }}" placeholder="Observaciones">
                            </div>
                            
                            <!-- Dirección y Referencia -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dirección (Dir) <span class="text-muted">(Opcional)</span></label>
                                <input type="text" name="dir" class="form-control" maxlength="100" value="{{ $envio->Detalle->dir ?? '' }}" placeholder="Dirección de envío">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Referencia (Ref) <span class="text-muted">(Opcional)</span></label>
                                <input type="text" name="ref" class="form-control" maxlength="100" value="{{ $envio->Detalle->ref ?? '' }}" placeholder="Referencia del lugar">
                            </div>

                            <div class="col-md-12 d-flex align-items-center mt-3">
                                <div class="form-check form-switch border p-3 rounded w-100" style="background-color: #fff8f8;">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="pago_destino" name="pago_destino" value="1" {{ $envio->pago_destino ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-danger" for="pago_destino">¿Pago en Destino?</label>
                                </div>
                            </div>

                            <hr class="my-4">
                            <!-- Búsqueda de Producto -->
                            <div class="col-md-12 mb-3">
                                <div class="p-3 bg-light border rounded shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam"></i> 3. Productos a Enviar <span class="text-danger">*</span></h6>
                                        <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="agregarFilaProducto()">
                                            <i class="bi bi-plus-circle-fill"></i> Agregar Producto
                                        </button>
                                    </div>
                                    <div class="table-responsive" style="overflow: visible !important; min-height: 250px;">
                                        <table class="table table-bordered bg-white align-middle" id="tabla-productos">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50%;">Buscar y Seleccionar Producto</th>
                                                    <th style="width: 15%;" class="text-center">Cantidad</th>
                                                    <th style="width: 25%;">Nota / Número de Serie</th>
                                                    <th style="width: 10%;" class="text-center">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody-productos">
                                                <!-- Las filas se insertarán dinámicamente mediante JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 text-end mt-5">
                                <a href="{{ route('envios.index') }}" class="btn btn-light border px-4 py-2 me-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-save"></i> Actualizar Envío
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('envios.logic.edit')

@include('envios.components.modal_new_agencia')
@include('envios.components.modal_new_destino')
@include('envios.components.modal_new_provincia')
@include('envios.components.modal_new_sub_agencia')
@include('envios.components.modal_new_cliente')

@endsection
