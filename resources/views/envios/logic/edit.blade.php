<script>
    const plataformas = @json($plataformas);
    const selectedCuenta = "{{ $envio->idCuentaPlataforma }}";
    
    function filterAccounts() {
        const plataformaId = document.getElementById('idPlataforma').value;
        const cuentaSelect = document.getElementById('idCuentaPlataforma');
        
        cuentaSelect.innerHTML = '<option value="">Seleccione cuenta...</option>';
        
        if (plataformaId) {
            const plataforma = plataformas.find(p => p.idPlataforma == plataformaId);
            const cuentas = plataforma.cuentas_plataforma || plataforma.CuentasPlataforma;
            if (plataforma && cuentas) {
                cuentas.forEach(cuenta => {
                    if (cuenta.estadoCuenta === 'ACTIVO' || cuenta.idCuentaPlataforma == selectedCuenta) {
                        const option = document.createElement('option');
                        option.value = cuenta.idCuentaPlataforma;
                        option.textContent = cuenta.nombreCuenta;
                        if(cuenta.idCuentaPlataforma == selectedCuenta) {
                            option.selected = true;
                        }
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
    
    document.addEventListener('DOMContentLoaded', function() {
        filterAccounts();
    });

    // Lógica de búsqueda de clientes
    const inputSearchCliente = document.getElementById('input-search-cliente');
    const suggestionCliente = document.getElementById('suggestion-cliente');
    const inputClienteId = document.getElementById('input-cliente-id');
    const inputClienteNombre = document.getElementById('input-cliente-nombre');
    const inputClienteTelefono = document.getElementById('input-cliente-telefono');

    window.onClienteCreado = function(cliente) {
        inputClienteId.value = cliente.idCliente;
        inputClienteNombre.value = `${cliente.nombre} ${cliente.apellidoPaterno || ''} ${cliente.apellidoMaterno || ''}`.trim();
        inputClienteTelefono.value = cliente.telefono || '';
        inputSearchCliente.value = cliente.numeroDocumento;
        suggestionCliente.innerHTML = '';
    };

    inputSearchCliente.addEventListener('input', function() {
        inputClienteId.value = '';
        inputClienteNombre.value = '';
        inputClienteTelefono.value = '';

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

    // Lógica de múltiples Productos ("N" Productos)
    let filaIndex = 0;

    function agregarFilaProducto(idProducto = '', nombreProducto = '', codigoProducto = '', cantidad = 1, notaProducto = '') {
        const tbody = document.getElementById('tbody-productos');
        const index = filaIndex++;
        
        const tr = document.createElement('tr');
        tr.id = `fila-producto-${index}`;
        
        tr.innerHTML = `
            <td>
                <div style="position: relative">
                    <input type="text" class="form-control" placeholder="Buscar por serie, SKU, modelo o nombre del producto..." id="search-prod-${index}" autocomplete="off" ${idProducto ? 'style="display:none;"' : ''}>
                    <div id="selected-badge-${index}" class="p-2 border rounded bg-light d-flex justify-content-between align-items-center ${idProducto ? '' : 'd-none'}">
                        <div>
                            <strong class="text-primary" id="badge-name-${index}">${nombreProducto}</strong><br>
                            <small class="text-muted" id="badge-sku-${index}">${codigoProducto}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarSeleccionProducto(${index})"><i class="bi bi-x"></i></button>
                    </div>
                    <ul class="list-group w-100 shadow" style="position: absolute; top:100%; z-index: 1000; max-height: 300px; overflow-y: auto;" id="suggestions-prod-${index}"></ul>
                    <input type="hidden" name="productos[${index}][idProducto]" id="id-prod-${index}" value="${idProducto}" required>
                </div>
            </td>
            <td>
                <input type="number" name="productos[${index}][cantidad]" class="form-control text-center" min="1" value="${cantidad}" required>
            </td>
            <td>
                <input type="text" name="productos[${index}][nota_producto]" id="nota-prod-${index}" class="form-control" placeholder="Número de serie o nota..." value="${notaProducto}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFila(${index})"><i class="bi bi-trash-fill"></i></button>
            </td>
        `;
        
        tbody.appendChild(tr);
        setupAutocompleteRow(index);
    }

    function setupAutocompleteRow(index) {
        const input = document.getElementById(`search-prod-${index}`);
        const suggestions = document.getElementById(`suggestions-prod-${index}`);
        const idInput = document.getElementById(`id-prod-${index}`);
        const badge = document.getElementById(`selected-badge-${index}`);
        const badgeName = document.getElementById(`badge-name-${index}`);
        const badgeSku = document.getElementById(`badge-sku-${index}`);
        const notaInput = document.getElementById(`nota-prod-${index}`);

        input.addEventListener('input', function() {
            if (this.value.length > 2) {
                fetch(`/envios-provincias/buscar-registro?query=${this.value}`)
                    .then(r => r.json())
                    .then(data => {
                        suggestions.innerHTML = '';
                        data.forEach(item => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action cursor-pointer';
                            
                            const detalle = item.detalle_comprobante || {};
                            const prod = detalle.producto || {};
                            const nombreProd = prod.nombreProducto || 'Desconocido';
                            const sku = prod.codigoProducto || 'N/A';
                            const serial = item.numeroSerie || 'S/N';

                            li.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold text-primary">${nombreProd}</span><br>
                                        <small>SKU: ${sku} | <strong>Serie: ${serial}</strong></small>
                                    </div>
                                </div>
                            `;
                            li.onclick = () => {
                                // Validar número de serie duplicado al seleccionar
                                let duplicado = false;
                                const cleanSerial = serial.trim().toUpperCase();
                                if (cleanSerial !== 'S/N' && cleanSerial !== '') {
                                    const otherNotas = document.querySelectorAll('input[name^="productos"][name$="[nota_producto]"]');
                                    otherNotas.forEach(otherInput => {
                                        if (otherInput.id !== `nota-prod-${index}`) {
                                            const otherVal = otherInput.value.trim().replace(/s\/n:\s*/i, '').trim().toUpperCase();
                                            if (otherVal === cleanSerial) {
                                                duplicado = true;
                                            }
                                        }
                                    });
                                }

                                if (duplicado) {
                                    alert(`El número de serie "${serial}" ya está seleccionado en otra fila.`);
                                    return;
                                }

                                idInput.value = prod.idProducto;
                                badgeName.textContent = nombreProd;
                                badgeSku.textContent = `SKU: ${sku} | Serie: ${serial}`;
                                
                                input.style.display = 'none';
                                badge.classList.remove('d-none');
                                
                                if (serial !== 'S/N') {
                                    notaInput.value = `S/N: ${serial}`;
                                }
                                
                                suggestions.innerHTML = '';
                                input.value = serial;
                            };
                            suggestions.appendChild(li);
                        });
                    });
            } else {
                suggestions.innerHTML = '';
            }
        });

        document.addEventListener('click', function(e) {
            if (suggestions && e.target !== input) suggestions.innerHTML = '';
        });
    }

    function quitarSeleccionProducto(index) {
        const input = document.getElementById(`search-prod-${index}`);
        const badge = document.getElementById(`selected-badge-${index}`);
        const idInput = document.getElementById(`id-prod-${index}`);
        
        idInput.value = '';
        input.value = '';
        input.style.display = 'block';
        badge.classList.add('d-none');
    }

    function eliminarFila(index) {
        const tr = document.getElementById(`fila-producto-${index}`);
        if (tr) {
            tr.remove();
        }
    }

    // Agregar las filas existentes precargadas desde PHP
    document.addEventListener("DOMContentLoaded", function() {
        @foreach($envio->Productos as $envioProd)
            agregarFilaProducto(
                "{{ $envioProd->idProducto }}",
                "{{ $envioProd->Producto->nombreProducto }}",
                "SKU: {{ $envioProd->Producto->codigoProducto ?? 'N/A' }}",
                {{ $envioProd->cantidad }},
                "{{ $envioProd->nota_producto }}"
            );
        @endforeach
        
        // Si no hay productos guardados, iniciamos con una fila en blanco
        if (filaIndex === 0) {
            agregarFilaProducto();
        }
    });

    // Validación general del Formulario antes de Enviar
    document.getElementById('form-edit-envio').addEventListener('submit', function(e) {
        if (!inputClienteId.value) {
            e.preventDefault();
            Swal.fire('Error', 'Debe buscar y seleccionar un cliente de la lista de sugerencias o crear uno nuevo.', 'error');
            return false;
        }

        const serials = [];
        let hasDuplicate = false;
        let duplicateSerial = '';
        
        const notaInputs = document.querySelectorAll('input[name^="productos"][name$="[nota_producto]"]');
        notaInputs.forEach(input => {
            const val = input.value.trim();
            if (val && val.toUpperCase() !== 'S/N') {
                const cleanSerial = val.replace(/s\/n:\s*/i, '').trim().toUpperCase();
                if (cleanSerial && cleanSerial !== 'S/N') {
                    if (serials.includes(cleanSerial)) {
                        hasDuplicate = true;
                        duplicateSerial = cleanSerial;
                    } else {
                        serials.push(cleanSerial);
                    }
                }
            }
        });
        
        if (hasDuplicate) {
            e.preventDefault();
            alert(`Error: El número de serie "${duplicateSerial}" está duplicado en los productos a enviar.`);
            return false;
        }

        // Validar Shalom si está habilitado
        if (typeof validarRestriccionesAntesDeGuardar === 'function') {
            const selectAgencia = document.querySelector('select[name="idAgencia"]');
            const selectedAgencia = selectAgencia.options[selectAgencia.selectedIndex];
            const nombreAgencia = selectedAgencia ? selectedAgencia.text.trim().toUpperCase() : '';

            if (nombreAgencia === 'SHALOM') {
                // Validar dimensiones máximas (1.5m)
                const MAX_DIM = 1.5;
                const largo = parseFloat(document.getElementById('input_largo')?.value) || 0;
                const ancho = parseFloat(document.getElementById('input_ancho')?.value) || 0;
                const alto = parseFloat(document.getElementById('input_alto')?.value) || 0;

                let dimErrores = [];
                if (largo > MAX_DIM) dimErrores.push(`Largo: ${largo}m`);
                if (ancho > MAX_DIM) dimErrores.push(`Ancho: ${ancho}m`);
                if (alto > MAX_DIM) dimErrores.push(`Alto: ${alto}m`);

                if (dimErrores.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: '📐 Dimensiones fuera de rango',
                        html: `
                            <p>Las dimensiones no pueden superar <strong>${MAX_DIM} metros</strong>:</p>
                            <div class="text-start" style="font-size:0.95rem;">
                                ${dimErrores.map(e => `<div class="mb-1">• <strong>${e}</strong> (máx ${MAX_DIM}m)</div>`).join('')}
                            </div>
                        `,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Corregir'
                    });
                    return false;
                }

                if (!validarRestriccionesAntesDeGuardar()) {
                    e.preventDefault();
                    return false;
                }
            }
        }

        // Validar que haya al menos un producto seleccionado
        let hasProduct = false;
        const idInputs = document.querySelectorAll('input[name^="productos"][name$="[idProducto]"]');
        idInputs.forEach(input => {
            if (input.value) hasProduct = true;
        });

        if (!hasProduct) {
            e.preventDefault();
            alert('Error: Debe seleccionar al menos un producto para registrar el envío.');
            return false;
        }
    });

    // Cerrar sugerencias al hacer clic fuera para clientes
    document.addEventListener('click', function(e) {
        if (e.target !== inputSearchCliente) suggestionCliente.innerHTML = '';
    });

    function cargarProvincias(idDepartamento) {
        const selectProvincia = document.getElementById('select-provincia');
        const selectDestino = document.getElementById('select-destino');
        
        selectDestino.innerHTML = '<option value="">Primero elija provincia...</option>';
        selectDestino.disabled = true;
        
        if (idDepartamento) {
            selectProvincia.innerHTML = '<option value="">Cargando...</option>';
            selectProvincia.disabled = true;

            fetch(`/envios-provincias/provincias-por-departamento/${idDepartamento}`)
                .then(response => response.json())
                .then(data => {
                    selectProvincia.innerHTML = '<option value="">Seleccione provincia...</option>';
                    data.forEach(provincia => {
                        selectProvincia.innerHTML += `<option value="${provincia.idProvincia}">${provincia.nombre}</option>`;
                    });
                    selectProvincia.disabled = false;
                })
                .catch(error => {
                    console.error("Error cargando provincias:", error);
                    selectProvincia.innerHTML = '<option value="">Error al cargar</option>';
                });
        } else {
            selectProvincia.innerHTML = '<option value="">Primero elija departamento...</option>';
            selectProvincia.disabled = true;
        }
    }

    function cargarDestinos(idProvincia) {
        const selectDestino = document.getElementById('select-destino');
        
        if (idProvincia) {
            selectDestino.innerHTML = '<option value="">Cargando...</option>';
            selectDestino.disabled = true;

            fetch(`/envios-provincias/destinos-por-provincia/${idProvincia}`)
                .then(response => response.json())
                .then(data => {
                    selectDestino.innerHTML = '<option value="">Seleccione destino...</option>';
                    data.forEach(destino => {
                        selectDestino.innerHTML += `<option value="${destino.idDestino}">${destino.nombre}</option>`;
                    });
                    selectDestino.disabled = false;
                })
                .catch(error => {
                    console.error("Error cargando destinos:", error);
                    selectDestino.innerHTML = '<option value="">Error al cargar</option>';
                });
        } else {
            selectDestino.innerHTML = '<option value="">Primero elija provincia...</option>';
            selectDestino.disabled = true;
        }
    }

    function aplicarMedidasCaja() {
        const select = document.getElementById('tipo_caja_select');
        const val = select.value;

        if (val === 'custom') {
            document.getElementById('input_largo').value = '';
            document.getElementById('input_ancho').value = '';
            document.getElementById('input_alto').value = '';
            document.getElementById('input_peso').value = '';
            
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
            document.getElementById('input_largo').value = selectedOption.getAttribute('data-l');
            document.getElementById('input_ancho').value = selectedOption.getAttribute('data-w');
            document.getElementById('input_alto').value = selectedOption.getAttribute('data-h');
            document.getElementById('input_peso').value = selectedOption.getAttribute('data-wt');
            
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
        const selectDestino = document.getElementById('select-destino');
        const selectSubAgencia = document.getElementById('select-subagencia');
        
        const idAgencia = selectAgencia ? selectAgencia.value : '';
        const idDestino = selectDestino ? selectDestino.value : '';
        const selectedOption = selectAgencia.options[selectAgencia.selectedIndex];
        const nombreAgencia = selectedOption ? selectedOption.text.trim().toUpperCase() : '';

        // Mostrar/Ocultar seccion de medidas si es Shalom u Olva
        const seccionMedidas = document.getElementById('seccion_medidas_caja');
        if (seccionMedidas) {
            if (nombreAgencia === 'SHALOM' || nombreAgencia === 'OLVA') {
                seccionMedidas.classList.remove('d-none');
            } else {
                seccionMedidas.classList.add('d-none');
                // Limpiar valores si se oculta y no hay datos pre-guardados
                if (document.getElementById('tipo_caja_select')) {
                    document.getElementById('tipo_caja_select').value = 'custom';
                    aplicarMedidasCaja();
                }
            }
        }
        
        if (idAgencia && idDestino) {
            selectSubAgencia.innerHTML = '<option value="">Cargando oficinas...</option>';
            selectSubAgencia.disabled = true;

            fetch(`/envios-provincias/subagencias-por-agencia-y-destino/${idAgencia}/${idDestino}`)
                .then(response => response.json())
                .then(data => {
                    const containerSub = document.getElementById('container-subagencia');
                    if (data.length > 0) {
                        if (containerSub) containerSub.style.display = 'block';
                        selectSubAgencia.innerHTML = '<option value="">Seleccione oficina...</option>';
                        data.forEach(sub => {
                            const partes = sub.nombre_oficina.split(' / ');
                            const nombreTerminal = partes[partes.length - 1];
                            selectSubAgencia.innerHTML += `<option value="${sub.idSubAgencia}">${nombreTerminal} (${sub.direccion})</option>`;
                        });
                        selectSubAgencia.disabled = false;
                    } else {
                        if (containerSub) containerSub.style.display = 'none';
                        selectSubAgencia.innerHTML = '<option value="">Sin oficinas registradas...</option>';
                        selectSubAgencia.disabled = true;
                    }
                })
                .catch(error => {
                    console.error("Error cargando oficinas:", error);
                    selectSubAgencia.innerHTML = '<option value="">Error al cargar oficinas</option>';
                });
        } else {
            selectSubAgencia.innerHTML = '<option value="">Primero elija Agencia y Distrito...</option>';
            selectSubAgencia.disabled = true;
        }
    }

    // Inicializar visualización de medidas al cargar la página si la agencia ya está seleccionada
    document.addEventListener("DOMContentLoaded", function() {
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        if (selectAgencia && selectAgencia.value) {
            const selectedOption = selectAgencia.options[selectAgencia.selectedIndex];
            const nombreAgencia = selectedOption ? selectedOption.text.trim().toUpperCase() : '';
            if (nombreAgencia === 'SHALOM' || nombreAgencia === 'OLVA') {
                const seccionMedidas = document.getElementById('seccion_medidas_caja');
                if (seccionMedidas) seccionMedidas.classList.remove('d-none');
            }
        }
    });
</script>
