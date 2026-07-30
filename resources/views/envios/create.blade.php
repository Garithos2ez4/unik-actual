@extends('layouts.app')

@section('title', 'Nuevo Envío a Provincia')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-primary text-white p-4 border-0">
                    <h3 class="mb-0"><i class="bi bi-truck"></i> Registrar Envío a Provincia</h3>
                    <small>Sección: NUEVO DESPACHO</small>
                </div>
                <div class="card-body p-4">
                    <form id="form-create-envio" action="{{ route('envios.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-person-fill"></i> 1. Información del Cliente y Envío</h6>

                            <!-- Cliente -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Buscar Cliente <span class="text-danger">*</span></label>
                                <div class="input-group" id="div-input-group-cliente" style="position: relative">
                                    <input type="text" class="form-control" placeholder="Buscar por Nro Documento o Nombre..." id="input-search-cliente" autocomplete="off">
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#nuevoClienteModal" title="Nuevo Cliente">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </button>
                                    <ul class="list-group w-100 shadow" style="position: absolute; top:100%; z-index: 1000;" id="suggestion-cliente"></ul>
                                </div>
                                <input type="hidden" name="idCliente" id="input-cliente-id" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombres del Cliente</label>
                                <input type="text" id="input-cliente-nombre" class="form-control bg-light" placeholder="Se completará automáticamente" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Celular / Contacto</label>
                                <input type="text" id="input-cliente-telefono" class="form-control bg-light" placeholder="Número de contacto" readonly>
                            </div>

                            <!-- Plataforma / Cuenta -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Plataforma de Venta <span class="text-danger">*</span></label>
                                <select name="idPlataforma" id="idPlataforma" class="form-select" required onchange="filterAccounts()">
                                    <option value="">Seleccione plataforma...</option>
                                    @foreach($plataformas as $p)
                                    <option value="{{ $p->idPlataforma }}">{{ $p->nombrePlataforma }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cuenta Wasap/Venta</label>
                                <select name="idCuentaPlataforma" id="idCuentaPlataforma" class="form-select">
                                    <option value="">Seleccione cuenta...</option>
                                    @foreach($plataformas as $p)
                                    @foreach($p->CuentasPlataforma as $c)
                                    <option value="{{ $c->idCuentaPlataforma }}" data-plataforma="{{ $p->idPlataforma }}">{{ $c->nombreCuenta }} ({{ $p->nombrePlataforma }})</option>
                                    @endforeach
                                    @endforeach
                                </select>
                            </div>

                            <!-- Ubigeo Geográfico -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Departamento <span class="text-danger">*</span></label>
                                <select id="select-departamento" class="form-select" required onchange="cargarProvincias(this.value)">
                                    <option value="">Seleccione departamento...</option>
                                    @foreach($departamentos as $depto)
                                    <option value="{{ $depto->idDepartamento }}">{{ $depto->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Provincia <span class="text-danger">*</span></label>
                                <select id="select-provincia" class="form-select" required disabled onchange="cargarDestinos(this.value)">
                                    <option value="">Primero elija departamento...</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Destino (Distrito) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="idDestino" id="select-destino" class="form-select" required disabled onchange="cargarSubAgencias()">
                                        <option value="">Primero elija provincia...</option>
                                    </select>
                                    {{--<button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewDestino" title="Nuevo Destino">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>--}}
                                </div>
                            </div>

                            <!-- Agencia y Oficina/Sucursal -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Agencia de Transporte <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="idAgencia" class="form-select" required onchange="cargarSubAgencias()">
                                        <option value="">Seleccione agencia...</option>
                                        @foreach($agencias as $agencia)
                                        <option value="{{ $agencia->idAgencia }}">{{ $agencia->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewAgencia" title="Nueva Agencia">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-9" id="container-subagencia">
                                <label class="form-label fw-bold">Oficina / Sucursal <span class="text-muted">(Opcional)</span></label>
                                <div class="input-group">
                                    <select name="idSubAgencia" id="select-subagencia" class="form-select" disabled>
                                        <option value="">Primero elija Agencia y Distrito...</option>
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewSubAgencia" title="Nueva Oficina/Sucursal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-outline-success" type="button" onclick="sincronizarAgencia()" title="Sincronizar Todas las Sucursales Oficiales">
                                        <i class="bi bi-cloud-arrow-down-fill"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" type="button" onclick="abrirCotizadorShalom()" title="Cotizar Envío (Shalom)">
                                        <i class="bi bi-calculator"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3 d-none" id="seccion_medidas_caja">
                                <div class="card border-danger shadow-sm">
                                    <div class="card-header bg-danger text-white py-2">
                                        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Dimensiones del Paquete (Shalom)</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-2">
                                            <div class="col-md-12">
                                                <label class="form-label mb-0 fw-bold" style="font-size: 0.85rem">Tipo de Paquete predeterminado</label>
                                                <select name="idTipoPaquete" id="tipo_caja_select" class="form-select form-select-sm border-danger" onchange="aplicarMedidasCaja()">
                                                    <option value="custom">📦 Otra Medida (Personalizado / Vacio)</option>
                                                    @if(isset($tiposPaquete))
                                                    @foreach($tiposPaquete as $paquete)
                                                    <option value="{{ $paquete->idTipoPaquete }}" data-agencia="{{ $paquete->idAgencia }}" data-w="{{ $paquete->ancho_defecto }}" data-h="{{ $paquete->alto_defecto }}" data-l="{{ $paquete->largo_defecto }}" data-wt="{{ $paquete->peso_maximo }}">{{ $paquete->icono }} {{ $paquete->nombre }}</option>
                                                    @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.8rem">Largo (m)</label>
                                                <input type="number" name="largo" id="input_largo" class="form-control form-control-sm" step="0.01" min="0.01" disabled>
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.8rem">Ancho (m)</label>
                                                <input type="number" name="ancho" id="input_ancho" class="form-control form-control-sm" step="0.01" min="0.01" disabled>
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.8rem">Alto (m)</label>
                                                <input type="number" name="alto" id="input_alto" class="form-control form-control-sm" step="0.01" min="0.01" disabled>
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 fw-bold text-danger" style="font-size: 0.8rem">Peso (KG)</label>
                                                <input type="number" name="peso" id="input_peso" class="form-control form-control-sm border-danger" step="0.01" disabled>
                                            </div>
                                            <div class="col-4 d-none">
                                                <label class="form-label mb-0 fw-bold text-success" style="font-size: 0.8rem">Tarifa Estimada</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-success text-white border-success">S/</span>
                                                    <input type="number" name="precio_envio" id="input_precio_envio" class="form-control border-success" step="0.01" placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerta de Restricciones Shalom -->
                            <div class="col-md-12 mb-2 d-none" id="alerta_restricciones_shalom"></div>

                            <div class="col-md-12 d-flex align-items-center mb-2">
                                <div class="form-check form-switch border p-3 rounded w-100" style="background-color: #f8f9fa;">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="entrega_domicilio" name="entrega_domicilio" value="1">
                                    <label class="form-check-label fw-bold text-primary" for="entrega_domicilio">¿Entrega a Domicilio?</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold" id="label-dir">Dirección (Dir) <span class="text-muted">(Opcional)</span></label>
                                <input type="text" name="dir" class="form-control" maxlength="100" placeholder="Dirección de envío">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Referencia (Ref) <span class="text-muted">(Opcional)</span></label>
                                <input type="text" name="ref" class="form-control" maxlength="100" placeholder="Referencia del lugar">
                            </div>

                            {{-- Persona que Recibe (Shalom + RUC) --}}
                            <div class="col-md-12 d-none" id="seccion-receptor-interno">
                                <div class="card border-info shadow-sm mt-2">
                                    <div class="card-header bg-info text-white py-2">
                                        <h6 class="mb-0"><i class="bi bi-person-check-fill"></i> Persona que Recibe <small>(Requerido por Shalom para clientes con RUC)</small></h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label mb-0 fw-bold" style="font-size: 0.85rem">Nombre completo del receptor</label>
                                                <input type="text" name="receptor[nombre]" id="receptor_nombre" class="form-control form-control-sm" placeholder="Ej: Juan Pérez García" maxlength="150" minlength="3">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-0 fw-bold" style="font-size: 0.85rem">DNI del receptor</label>
                                                <input type="text" name="receptor[dni]" id="receptor_dni" class="form-control form-control-sm" placeholder="72345678" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.85rem">Teléfono receptor</label>
                                                <input type="tel" name="receptor[telefono]" id="receptor_telefono" class="form-control form-control-sm" placeholder="987654321" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-file-earmark-text-fill"></i> 2. Información de la Guía</h6>

                            <!-- Guia -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Número de Guía</label>
                                <input type="text" name="numero_guia" class="form-control" placeholder="Número de seguimiento">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Clave</label>
                                <input type="text" id="input-clave" name="clave" class="form-control" placeholder="Clave para recojo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dato Adicional</label>
                                <input type="text" name="dato_adicional" class="form-control" placeholder="Observaciones">
                            </div>



                            <div class="col-md-12 d-flex align-items-center mt-3">
                                <div class="form-check form-switch border p-3 rounded w-100" style="background-color: #fff8f8;">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="pago_destino" name="pago_destino" value="1">
                                    <label class="form-check-label fw-bold text-danger" for="pago_destino">¿Pago en Destino?</label>
                                </div>
                            </div>

                            <hr class="my-4">
                            <!-- Búsqueda de Producto -->
                            <div class="col-md-12">
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
                                <button type="button" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" onclick="verificarClaveYGuardar()">
                                    <i class="bi bi-save"></i> Guardar Envío
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('envios.logic.create')

@include('envios.components.modal_new_agencia')
@include('envios.components.modal_new_destino')
@include('envios.components.modal_new_provincia')
@include('envios.components.modal_new_sub_agencia')
@include('envios.components.modal_new_cliente')

<!-- Modal de Confirmación de Guardado -->
<div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-labelledby="confirmSaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="confirmSaveModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> ¡Advertencia!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-shield-lock text-warning" style="font-size: 3rem;"></i>
                <h5 class="mt-3">¿Estás seguro de guardar?</h5>
                <p class="text-muted">Una vez guardado el envío, <strong>la Clave de recojo no podrá ser editada</strong> desde el sistema. Tendría que modificarse directamente en la base de datos.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Revisar de nuevo</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" onclick="document.getElementById('form-create-envio').submit();">
                    Sí, Guardar Envío
                </button>
            </div>
        </div>
    </div>
</div>

@include('envios.logic.cotizador-shalom')
@include('envios.logic.restricciones-shalom')
@include('envios.logic.restricciones-olva')
@endsection