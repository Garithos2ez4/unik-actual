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
                    <form action="{{ route('envios.update', $envio->idEnvioProvincia) }}" method="POST" id="form-edit-envio">
                        @csrf
                        @method('PUT')

                        @php $esFormularioPublico = (optional($envio->Detalle)->origen === 'FORMULARIO_PUBLICO'); @endphp
                        <div class="row g-3">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-person-fill"></i> 1. Información del Cliente y Envío</h6>

                            @if($esFormularioPublico)
                            <div class="col-md-12">
                                <div class="alert alert-info d-flex align-items-center py-2 mb-0" role="alert">
                                    <i class="bi bi-lock-fill me-2"></i>
                                    <small>Datos del cliente bloqueados (envío registrado desde formulario público).</small>
                                </div>
                            </div>
                            @endif

                            <!-- Cliente -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Buscar Cliente <span class="text-danger">*</span></label>
                                <div class="input-group" id="div-input-group-cliente" style="position: relative">
                                    <input type="text" class="form-control {{ $esFormularioPublico ? 'bg-light' : '' }}" value="{{ optional($envio->Cliente)->numeroDocumento ?? optional($envio->Cliente)->nombre }}" placeholder="Buscar por Nro Documento o Nombre..." id="input-search-cliente" autocomplete="off" {{ $esFormularioPublico ? 'readonly' : '' }}>
                                    @if(!$esFormularioPublico)
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#nuevoClienteModal" title="Nuevo Cliente">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </button>
                                    @endif
                                    <ul class="list-group w-100 shadow" style="position: absolute; top:100%; z-index: 1000;" id="suggestion-cliente"></ul>
                                </div>
                                <input type="hidden" name="idCliente" id="input-cliente-id" value="{{ $envio->idCliente }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombres del Cliente</label>
                                <input type="text" id="input-cliente-nombre" class="form-control bg-light" value="{{ optional($envio->Cliente)->nombre }} {{ optional($envio->Cliente)->apellidoPaterno }} {{ optional($envio->Cliente)->apellidoMaterno }}" placeholder="Se completará automáticamente" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Celular / Contacto</label>
                                <input type="text" id="input-cliente-telefono" class="form-control bg-light" value="{{ optional($envio->Cliente)->telefono }}" placeholder="Número de contacto" readonly>
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
                                        <option value="{{ $agencia->idAgencia }}" {{ $envio->idAgencia == $agencia->idAgencia ? 'selected' : '' }}>{{ $agencia->nombre }}</option>
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
                                    <select name="idSubAgencia" id="select-subagencia" class="form-select" {{ $subagencias->isEmpty() ? 'disabled' : '' }}>
                                        @if($subagencias->isEmpty())
                                        <option value="">Primero elija Agencia y Distrito...</option>
                                        @else
                                        <option value="">Seleccione oficina...</option>
                                        @foreach($subagencias as $sub)
                                        @php
                                            $partes = explode(' / ', $sub->nombre_oficina);
                                            $nombreTerminal = end($partes);
                                        @endphp
                                        <option value="{{ $sub->idSubAgencia }}" {{ $envio->idSubAgencia == $sub->idSubAgencia ? 'selected' : '' }}>{{ $nombreTerminal }} ({{ $sub->direccion }})</option>
                                        @endforeach
                                        @endif
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNewSubAgencia" title="Nueva Oficina/Sucursal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-outline-success" type="button" onclick="sincronizarAgencia()" title="Sincronizar Sucursales Oficiales">
                                        <i class="bi bi-cloud-arrow-down-fill"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" type="button" onclick="abrirCotizadorShalom()" title="Cotizar Envío (Shalom)">
                                        <i class="bi bi-calculator"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Medidas de Caja (Solo Shalom / Olva) -->
                            <div class="col-md-12 mb-3 d-none" id="seccion_medidas_caja">
                                <div class="card border-danger shadow-sm">
                                    <div class="card-header bg-danger text-white py-2">
                                        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Dimensiones del Paquete (Shalom / Olva)</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-2">
                                            <div class="col-md-12">
                                                <label class="form-label mb-0 fw-bold" style="font-size: 0.85rem">Tipo de Paquete predeterminado</label>
                                                <select name="idTipoPaquete" id="tipo_caja_select" class="form-select form-select-sm border-danger" onchange="aplicarMedidasCaja()">
                                                    <option value="custom" {{ optional($envio->Dimension)->idTipoPaquete ? '' : 'selected' }}>📦 Otra Medida (Personalizado / Vacio)</option>
                                                    @if(isset($tiposPaquete))
                                                        @foreach($tiposPaquete as $paquete)
                                                            <option value="{{ $paquete->idTipoPaquete }}" data-agencia="{{ $paquete->idAgencia }}" data-w="{{ $paquete->ancho_defecto }}" data-h="{{ $paquete->alto_defecto }}" data-l="{{ $paquete->largo_defecto }}" data-wt="{{ $paquete->peso_maximo }}" {{ optional($envio->Dimension)->idTipoPaquete == $paquete->idTipoPaquete ? 'selected' : '' }}>{{ $paquete->icono }} {{ $paquete->nombre }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.8rem">Largo (m)</label>
                                                <input type="number" name="largo" id="input_largo" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ optional($envio->Dimension)->largo_final }}">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.8rem">Ancho (m)</label>
                                                <input type="number" name="ancho" id="input_ancho" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ optional($envio->Dimension)->ancho_final }}">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 text-muted" style="font-size: 0.8rem">Alto (m)</label>
                                                <input type="number" name="alto" id="input_alto" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ optional($envio->Dimension)->alto_final }}">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label mb-0 fw-bold text-danger" style="font-size: 0.8rem">Peso (KG)</label>
                                                <input type="number" name="peso" id="input_peso" class="form-control form-control-sm border-danger" step="0.01" value="{{ optional($envio->Dimension)->peso_final }}">
                                            </div>
                                            <div class="col-4 d-none">
                                                <label class="form-label mb-0 fw-bold text-success" style="font-size: 0.8rem">Tarifa Estimada</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-success text-white border-success">S/</span>
                                                    <input type="number" name="precio_envio" id="input_precio_envio" class="form-control border-success" step="0.01" placeholder="0.00" value="{{ optional($envio->Dimension)->precio_calculado }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerta de Restricciones Shalom -->
                            <div class="col-md-12 mb-2 d-none" id="alerta_restricciones_shalom"></div>

                            <!-- Dirección y Referencia -->
                            <div class="col-md-12 d-flex align-items-center mb-2">
                                <div class="form-check form-switch border p-3 rounded w-100" style="background-color: #f8f9fa;">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="entrega_domicilio" name="entrega_domicilio" value="1" {{ optional($envio->Detalle)->entrega_domicilio ? 'checked' : '' }} {{ $esFormularioPublico ? 'disabled' : '' }}>
                                    @if($esFormularioPublico && optional($envio->Detalle)->entrega_domicilio)
                                        <input type="hidden" name="entrega_domicilio" value="1">
                                    @endif
                                    <label class="form-check-label fw-bold text-primary" for="entrega_domicilio">¿Entrega a Domicilio?</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold" id="label-dir">
                                    {{ optional($envio->Detalle)->entrega_domicilio ? 'Dirección Exacta (Dir)' : 'Dirección (Dir)' }} <span class="text-muted">(Opcional)</span>
                                </label>
                                <input type="text" name="dir" class="form-control {{ $esFormularioPublico ? 'bg-light' : '' }}" maxlength="100" value="{{ $envio->Detalle->dir ?? '' }}" placeholder="Dirección de envío" {{ $esFormularioPublico ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Referencia (Ref) <span class="text-muted">(Opcional)</span></label>
                                <input type="text" name="ref" class="form-control {{ $esFormularioPublico ? 'bg-light' : '' }}" maxlength="100" value="{{ $envio->Detalle->ref ?? '' }}" placeholder="Referencia del lugar" {{ $esFormularioPublico ? 'readonly' : '' }}>
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
                                <input type="text" id="input-clave" name="clave" class="form-control" value="{{ $envio->clave }}" placeholder="Clave para recojo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dato Adicional</label>
                                <input type="text" name="dato_adicional" class="form-control" value="{{ $envio->dato_adicional }}" placeholder="Observaciones">
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

<script>
    document.getElementById('entrega_domicilio').addEventListener('change', function() {
        const labelDir = document.getElementById('label-dir');
        if (this.checked) {
            labelDir.innerHTML = 'Dirección Exacta (Dir) <span class="text-muted">(Opcional)</span>';
        } else {
            labelDir.innerHTML = 'Dirección (Dir) <span class="text-muted">(Opcional)</span>';
        }
    });

    function aplicarMedidasCaja(isInitialLoad = false) {
        const select = document.getElementById('tipo_caja_select');
        const val = select.value;

        if (val === 'custom') {
            if (!isInitialLoad) {
                document.getElementById('input_largo').value = '';
                document.getElementById('input_ancho').value = '';
                document.getElementById('input_alto').value = '';
                document.getElementById('input_peso').value = '';
            }

            const inputs = [
                document.getElementById('input_peso'),
                document.getElementById('input_largo'),
                document.getElementById('input_ancho'),
                document.getElementById('input_alto')
            ];
            inputs.forEach(input => {
                input.readOnly = false;
                input.classList.remove('bg-light');
            });
        } else {
            const selectedOption = select.options[select.selectedIndex];
            if (!isInitialLoad) {
                document.getElementById('input_largo').value = selectedOption.getAttribute('data-l');
                document.getElementById('input_ancho').value = selectedOption.getAttribute('data-w');
                document.getElementById('input_alto').value = selectedOption.getAttribute('data-h');
                document.getElementById('input_peso').value = selectedOption.getAttribute('data-wt');
            }

            const inputs = [
                document.getElementById('input_peso'),
                document.getElementById('input_largo'),
                document.getElementById('input_ancho'),
                document.getElementById('input_alto')
            ];
            inputs.forEach(input => {
                input.readOnly = true;
                input.classList.add('bg-light');
            });
        }
    }

    function cargarSubAgencias() {
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        const idAgencia = selectAgencia.value;
        const selectedOption = selectAgencia.options[selectAgencia.selectedIndex];
        const nombreAgencia = selectedOption ? selectedOption.text.trim().toUpperCase() : '';

        // Mostrar/Ocultar seccion de medidas si es Shalom u Olva
        if (nombreAgencia === 'SHALOM' || nombreAgencia === 'OLVA') {
            document.getElementById('seccion_medidas_caja').classList.remove('d-none');
            
            // Filtrar opciones del select por idAgencia
            const selectCaja = document.getElementById('tipo_caja_select');
            const options = selectCaja.querySelectorAll('option[data-agencia]');
            
            options.forEach(opt => {
                if (opt.getAttribute('data-agencia') == idAgencia) {
                    opt.style.display = ''; // Mostrar
                } else {
                    opt.style.display = 'none'; // Ocultar
                }
            });
            
            // Si la opción seleccionada no pertenece a la agencia actual y no es 'custom', resetear a custom
            if (selectCaja.value !== 'custom') {
                const selectedOpt = selectCaja.options[selectCaja.selectedIndex];
                if (selectedOpt && selectedOpt.getAttribute('data-agencia') != idAgencia) {
                    selectCaja.value = 'custom';
                    aplicarMedidasCaja();
                }
            }
        } else {
            document.getElementById('seccion_medidas_caja').classList.add('d-none');
        }

        const idDestino = document.querySelector('select[name="idDestino"]').value;
        const selectSubAgencia = document.getElementById('select-subagencia');

        if (!idAgencia || !idDestino) {
            selectSubAgencia.innerHTML = '<option value="">Primero elija Agencia y Distrito...</option>';
            selectSubAgencia.disabled = true;
            return;
        }

        fetch(`/envios-provincias/subagencias-por-agencia-y-destino/${idAgencia}/${idDestino}`)
            .then(response => response.json())
            .then(data => {
                let html = '<option value="">Seleccione oficina...</option>';
                data.forEach(sub => {
                    const partes = sub.nombre_oficina.split(' / ');
                    const nombreTerminal = partes[partes.length - 1];
                    html += `<option value="${sub.idSubAgencia}">${nombreTerminal} - ${sub.direccion}</option>`;
                });
                selectSubAgencia.innerHTML = html;
                selectSubAgencia.disabled = false;
            })
            .catch(error => {
                console.error("Error al cargar subagencias: ", error);
            });
    }

    function sincronizarAgencia() {
        Swal.fire({
            title: 'Sincronización Masiva',
            text: 'Descargando y mapeando sucursales oficiales de TODAS las agencias (Olva, Shalom, Marvisur, Emtrafesa, Espinoza, Flores). Esto puede tardar unos minutos...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch("{{ url('/envios-provincias/sync-all') }}")
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sincronización Exitosa',
                        text: data.message
                    });
                    if (typeof cargarSubAgencias === 'function') {
                        cargarSubAgencias(); // Recargar el select automáticamente
                    }
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Hubo un problema de red al intentar sincronizar.', 'error');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar sección de medidas y visibilidad según la agencia cargada
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        const selectedOption = selectAgencia.options[selectAgencia.selectedIndex];
        const nombreAgencia = selectedOption ? selectedOption.text.trim().toUpperCase() : '';
        const idAgencia = selectAgencia.value;
        
        if (nombreAgencia === 'SHALOM' || nombreAgencia === 'OLVA') {
            document.getElementById('seccion_medidas_caja').classList.remove('d-none');
            
            // Filtrar opciones del select por idAgencia
            const selectCaja = document.getElementById('tipo_caja_select');
            const options = selectCaja.querySelectorAll('option[data-agencia]');
            
            options.forEach(opt => {
                if (opt.getAttribute('data-agencia') == idAgencia) {
                    opt.style.display = ''; // Mostrar
                } else {
                    opt.style.display = 'none'; // Ocultar
                }
            });
        } else {
            document.getElementById('seccion_medidas_caja').classList.add('d-none');
        }

        aplicarMedidasCaja(true); // true para no sobreescribir los valores cargados desde BD si es "custom"
    });
</script>

@include('envios.logic.cotizador-shalom')
@include('envios.logic.restricciones-shalom')
@include('envios.logic.restricciones-olva')
@endsection