@extends('layouts.app')

@section('title', 'Registrar Reclamo')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-primary text-white p-4 border-0">
                    <h3 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Nuevo Registro de Reclamo</h3>
                    <small>Sección: PRE RECLAMO (Información Inicial)</small>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('reclamos.store') }}" method="POST">
                        @csrf
                        
                        <div class="row g-3">
                             <!-- Identificación del Producto y Venta -->
                             <div class="col-md-12">
                                <div class="p-3 bg-light border rounded shadow-sm">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-search"></i> 1. Buscar Venta / Producto</h6>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <div class="input-group" style="position: relative">
                                                <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                                                <input type="text" class="form-control form-control-lg" placeholder="Escriba SKU, Serial o Nro de Orden..." id="input-search-egreso">
                                                <ul class="list-group w-100 shadow" style="position: absolute; top:100%; z-index: 1000;" id="suggestion-egreso"></ul>
                                            </div>
                                            <div class="form-text">El sistema buscará en el historial de ventas para amarrar toda la información.</div>
                                        </div>
                                    </div>
                                </div>
                             </div>

                             <hr class="my-4">
                             <h6 class="fw-bold mb-3 text-secondary">2. Información de la Venta (Autocompletado)</h6>

                             <!-- Plataforma de Venta -->
                             <div class="col-md-12">
                                 <label class="form-label fw-bold">Plataforma / Origen del Reclamo <span class="text-danger">*</span></label>
                                 <select name="idPlataforma" id="idPlataforma" class="form-select" required onchange="filterAccounts()">
                                     <option value="">Seleccione plataforma...</option>
                                     @foreach($plataformas as $p)
                                         <option value="{{ $p->idPlataforma }}">{{ $p->nombrePlataforma }}</option>
                                     @endforeach
                                 </select>
                             </div>

                             <!-- Cuenta y Orden -->
                             <div class="col-md-6">
                                 <label class="form-label fw-bold">Cuenta <span class="text-danger">*</span></label>
                                 <select name="idCuentaPlataforma" id="idCuentaPlataforma" class="form-select" required>
                                     <option value="">Seleccione cuenta...</option>
                                     @foreach($plataformas as $p)
                                        @foreach($p->CuentasPlataforma as $c)
                                            <option value="{{ $c->idCuentaPlataforma }}" data-plataforma="{{ $p->idPlataforma }}">{{ $c->nombreCuenta }} ({{ $p->nombrePlataforma }})</option>
                                        @endforeach
                                     @endforeach
                                 </select>
                             </div>
                             <div class="col-md-6">
                                 <label class="form-label fw-bold">Orden de Compra / Comprobante</label>
                                 <input type="text" name="ordenCompra" id="input-orden-compra" class="form-control" placeholder="Ejem: 200000...">
                             </div>

                             <!-- Hidden Fields for IDs -->
                             <input type="hidden" name="idPublicacion" id="input-id-publicacion">
                             <input type="hidden" name="idRegistro" id="input-id-registro">

                             <div class="col-md-12">
                                <div id="card-product-selected" class="card d-none border-primary bg-light">
                                    <div class="card-body py-2">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <i class="bi bi-box-seam fs-2 text-primary"></i>
                                            </div>
                                            <div class="col">
                                                <h6 class="mb-0 fw-bold" id="text-product-name">Nombre del Producto</h6>
                                                <small class="text-muted">Serial: <span id="text-product-serial">---</span> | SKU: <span id="text-product-sku">---</span></small>
                                            </div>
                                            <div class="col-auto">
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearProductSelection()">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                             </div>

                            <!-- Cliente -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Buscar Cliente (Opcional)</label>
                                <div class="input-group" id="div-input-group-cliente" style="position: relative">
                                    <input type="text" class="form-control" placeholder="Buscar por Nro Documento o Nombre..." id="input-search-cliente">
                                    <x-btn_modal_cliente :clases="'btn-outline-success'" :spanClass="'d-none'"/>
                                    <ul class="list-group w-100" style="position: absolute; top:100%; z-index: 1000;" id="suggestion-cliente"></ul>
                                </div>
                                <input type="hidden" name="idCliente" id="input-cliente-id">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombres del Cliente</label>
                                <input type="text" name="nombresCliente" id="input-cliente-nombre" class="form-control" placeholder="Nombre completo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Celular</label>
                                <input type="text" name="celularCliente" id="input-cliente-telefono" class="form-control" placeholder="Número de contacto">
                            </div>

                            <!-- Fechas -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fecha del Reclamo <span class="text-danger">*</span></label>
                                <input type="date" name="fechaReclamo" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fecha Max. Respuesta</label>
                                <input type="date" name="fechaMaxRespuesta" class="form-control">
                            </div>

                            <!-- Motivo y Caso -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Motivo del Reclamo <span class="text-danger">*</span></label>
                                <select name="idTipoReclamoPlataforma" class="form-select" required>
                                    <option value="">Seleccione motivo...</option>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t->idTipoReclamoPlataforma }}">{{ $t->nombreTipoReclamo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Número de Caso (Opcional)</label>
                                <input type="text" name="numeroCaso" class="form-control" placeholder="Número generado por la plataforma">
                            </div>

                            <hr class="my-4">

                             <!-- Descripciones -->
                             <div class="col-12">
                                 <label class="form-label fw-bold">Detalle del Reclamo (Relato del Cliente / Situación) <span class="text-danger">*</span></label>
                                 <textarea name="detalleReclamo" class="form-control" rows="5" placeholder="Describa detalladamente el problema reportado por el cliente..." required></textarea>
                             </div>

                            <div class="col-12 text-end mt-5">
                                <a href="{{ route('reclamos.index') }}" class="btn btn-light border px-4 py-2 me-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-save"></i> Guardar Reclamo
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('envios.components.modal_new_cliente', ['documentos' => $tipoDocumentos])

<script>
    const plataformas = @json($plataformas);
    
    function filterAccounts() {
        const plataformaId = document.getElementById('idPlataforma').value;
        const cuentaSelect = document.getElementById('idCuentaPlataforma');
        
        cuentaSelect.innerHTML = '<option value="">Seleccione cuenta...</option>';
        
        if (plataformaId) {
            const plataforma = plataformas.find(p => p.idPlataforma == plataformaId);
            const cuentas = plataforma.cuentas_plataforma || plataforma.CuentasPlataforma;
            if (plataforma && cuentas) {
                cuentas.forEach(cuenta => {
                    if (cuenta.estadoCuenta === 'ACTIVO') {
                        const option = document.createElement('option');
                        option.value = cuenta.idCuentaPlataforma;
                        option.textContent = cuenta.nombreCuenta;
                        cuentaSelect.appendChild(option);
                    }
                });
            }
            cuentaSelect.disabled = false;
        } else {
            cuentaSelect.disabled = true;
            cuentaSelect.innerHTML = '<option value="">Primero seleccione una plataforma...</option>';
        }
    }

    // Lógica de búsqueda de clientes
    const inputSearchCliente = document.getElementById('input-search-cliente');
    const suggestionCliente = document.getElementById('suggestion-cliente');
    const inputClienteId = document.getElementById('input-cliente-id');
    const inputClienteNombre = document.getElementById('input-cliente-nombre');
    const inputClienteTelefono = document.getElementById('input-cliente-telefono');

    inputSearchCliente.addEventListener('input', function() {
        if (this.value.length > 2) {
            fetch(`/cliente/searchcliente?query=${this.value}`)
                .then(response => response.json())
                .then(data => {
                    suggestionCliente.innerHTML = '';
                    data.forEach(cliente => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action cursor-pointer';
                        li.innerHTML = `
                            <div class="row">
                                <div class="col-8"><strong>${cliente.nombre} ${cliente.apellidoPaterno || ''}</strong></div>
                                <div class="col-4 text-end text-muted small">${cliente.numeroDocumento}</div>
                            </div>
                        `;
                        li.addEventListener('click', function() {
                            inputClienteId.value = cliente.idCliente;
                            inputClienteNombre.value = `${cliente.nombre} ${cliente.apellidoPaterno || ''} ${cliente.apellidoMaterno || ''}`.trim();
                            inputClienteTelefono.value = cliente.telefono || '';
                            inputSearchCliente.value = cliente.numeroDocumento;
                            suggestionCliente.innerHTML = '';
                        });
                        suggestionCliente.appendChild(li);
                    });
                });
        } else {
            suggestionCliente.innerHTML = '';
        }
    });

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (e.target !== inputSearchCliente) {
            suggestionCliente.innerHTML = '';
        }
    });

    // Capturar nuevo cliente desde modal
    document.getElementById('btn-modal-new-cliente').addEventListener('click', function() {
        setTimeout(function() {
            const cliente = getCliente();
            if (cliente) {
                inputClienteId.value = cliente.idCliente;
                inputClienteNombre.value = `${cliente.nombre} ${cliente.apellidoPaterno || ''} ${cliente.apellidoMaterno || ''}`.trim();
                inputClienteTelefono.value = cliente.telefono || '';
                inputSearchCliente.value = cliente.numeroDocumento;
            }
        }, 1000);
    });

    // Lógica de búsqueda de Egresos (Ventas)
    const inputSearchEgreso = document.getElementById('input-search-egreso');
    const suggestionEgreso = document.getElementById('suggestion-egreso');
    
    const selectPlataforma = document.getElementById('idPlataforma');
    const selectCuenta = document.getElementById('idCuentaPlataforma');
    const inputOrdenCompra = document.getElementById('input-orden-compra');
    const inputIdPublicacion = document.getElementById('input-id-publicacion');
    const inputIdRegistro = document.getElementById('input-id-registro');
    
    const cardProduct = document.getElementById('card-product-selected');
    const textProdName = document.getElementById('text-product-name');
    const textProdSerial = document.getElementById('text-product-serial');
    const textProdSku = document.getElementById('text-product-sku');

    inputSearchEgreso.addEventListener('input', function() {
        if (this.value.length > 2) {
            fetch(`/egresos/searchegreso?query=${this.value}`)
                .then(r => r.json())
                .then(data => {
                    suggestionEgreso.innerHTML = '';
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action cursor-pointer';
                        li.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-primary">${item.numeroSerie}</span><br>
                                    <small>${item.nombreProducto}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark border">${item.nombrePlataforma}</span><br>
                                    <small class="text-muted">Orden: ${item.numeroOrden || 'N/A'}</small>
                                </div>
                            </div>
                        `;
                        li.onclick = () => {
                            // Autocompletar Plataforma y Cuenta
                            if (item.idPlataforma) {
                                selectPlataforma.value = item.idPlataforma;
                                filterAccounts(); // Poblar las cuentas correspondientes
                                if (item.idCuentaPlataforma) {
                                    selectCuenta.value = item.idCuentaPlataforma;
                                }
                            }
                            
                            // Autocompletar Orden y IDs
                            inputOrdenCompra.value = item.numeroOrden || 'S/N';
                            inputIdPublicacion.value = item.idPublicacion || '';
                            inputIdRegistro.value = item.idRegistro;
                            
                            // Mostrar Card de Producto
                            textProdName.textContent = item.nombreProducto;
                            textProdSerial.textContent = item.numeroSerie;
                            textProdSku.textContent = item.sku || 'N/A';
                            cardProduct.classList.remove('d-none');
                            
                            suggestionEgreso.innerHTML = '';
                            inputSearchEgreso.value = item.numeroSerie;
                        };
                        suggestionEgreso.appendChild(li);
                    });
                });
        } else {
            suggestionEgreso.innerHTML = '';
        }
    });

    function clearProductSelection() {
        inputIdPublicacion.value = '';
        inputIdRegistro.value = '';
        cardProduct.classList.add('d-none');
        inputSearchEgreso.value = '';
        inputOrdenCompra.value = '';
    }

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (e.target !== inputSearchEgreso) suggestionEgreso.innerHTML = '';
        if (e.target !== inputSearchCliente) suggestionCliente.innerHTML = '';
    });
</script>
@endsection
