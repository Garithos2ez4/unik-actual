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
    
    let tipoDocumentoClienteSeleccionado = null;

    window.onClienteCreado = function(cliente) {
        inputClienteId.value = cliente.idCliente;
        inputClienteNombre.value = `${cliente.nombre} ${cliente.apellidoPaterno || ''} ${cliente.apellidoMaterno || ''}`.trim();
        inputClienteTelefono.value = cliente.telefono || '';
        inputSearchCliente.value = cliente.numeroDocumento;
        suggestionCliente.innerHTML = '';
        tipoDocumentoClienteSeleccionado = cliente.idTipoDocumento;
        toggleSeccionReceptorInterno();
    };

    inputSearchCliente.addEventListener('input', function() {
        inputClienteId.value = '';
        inputClienteNombre.value = '';
        inputClienteTelefono.value = '';
        tipoDocumentoClienteSeleccionado = null;
        toggleSeccionReceptorInterno();

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
                            tipoDocumentoClienteSeleccionado = cliente.idTipoDocumento;
                            toggleSeccionReceptorInterno();
                            
                            // Autocompletar último envío
                            fetch(`/envios-provincias/ultimo-envio-cliente/${cliente.idCliente}`)
                                .then(response => response.json())
                                .then(res => {
                                    if (res.success && res.data) {
                                        autoCompletarUltimoEnvio(res.data);
                                    }
                                })
                                .catch(err => console.error('Error al obtener último envío:', err));
                        });
                        suggestionCliente.appendChild(li);
                    });
                });
        } else {
            suggestionCliente.innerHTML = '';
        }
    });

    async function autoCompletarUltimoEnvio(data) {
        if (data.idPlataforma) {
            document.getElementById('idPlataforma').value = data.idPlataforma;
            filterAccounts();
            if (data.idCuentaPlataforma) {
                document.getElementById('idCuentaPlataforma').value = data.idCuentaPlataforma;
            }
        }

        if (data.destino && data.destino.provincia && data.destino.provincia.idDepartamento) {
            document.getElementById('select-departamento').value = data.destino.provincia.idDepartamento;
            await cargarProvincias(data.destino.provincia.idDepartamento);
            
            document.getElementById('select-provincia').value = data.destino.idProvincia;
            await cargarDestinos(data.destino.idProvincia);
            
            document.getElementById('select-destino').value = data.idDestino;
        }

        if (data.idAgencia) {
            const selectAgencia = document.querySelector('select[name="idAgencia"]');
            if(selectAgencia) {
                selectAgencia.value = data.idAgencia;
                toggleSeccionReceptorInterno();
            }
            
            await cargarSubAgencias();
            
            if (data.idSubAgencia) {
                document.getElementById('select-subagencia').value = data.idSubAgencia;
            }
        }

        if (data.detalle && data.detalle.dir) document.querySelector('input[name="dir"]').value = data.detalle.dir;
        if (data.detalle && data.detalle.ref) document.querySelector('input[name="ref"]').value = data.detalle.ref;
        if (data.dato_adicional) document.querySelector('input[name="dato_adicional"]').value = data.dato_adicional;
        
        if (data.pago_destino !== null && data.pago_destino !== undefined) {
            const pagoDestinoCheck = document.getElementById('pago_destino');
            if (pagoDestinoCheck) pagoDestinoCheck.checked = !!data.pago_destino;
        }
    }

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

    // Agregar la primera fila automáticamente en la carga
    document.addEventListener("DOMContentLoaded", function() {
        agregarFilaProducto();
    });

    // Validación general del Formulario antes de Enviar
    document.querySelector('form[action*="envios"]').addEventListener('submit', function(e) {
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

            return fetch(`/envios-provincias/provincias-por-departamento/${idDepartamento}`)
                .then(response => response.json())
                .then(data => {
                    selectProvincia.innerHTML = '<option value="">Seleccione provincia...</option>';
                    data.forEach(provincia => {
                        selectProvincia.innerHTML += `<option value="${provincia.idProvincia}">${provincia.nombre}</option>`;
                    });
                    selectProvincia.disabled = false;
                })
                .catch(error => {
                    console.error('Error cargando provincias:', error);
                    selectProvincia.innerHTML = '<option value="">Error al cargar</option>';
                });
        } else {
            selectProvincia.innerHTML = '<option value="">Primero elija departamento...</option>';
            selectProvincia.disabled = true;
            return Promise.resolve();
        }
    }

    function cargarDestinos(idProvincia) {
        const selectDestino = document.getElementById('select-destino');
        
        if (idProvincia) {
            selectDestino.innerHTML = '<option value="">Cargando...</option>';
            selectDestino.disabled = true;

            return fetch(`/envios-provincias/destinos-por-provincia/${idProvincia}`)
                .then(response => response.json())
                .then(data => {
                    selectDestino.innerHTML = '<option value="">Seleccione destino...</option>';
                    data.forEach(destino => {
                        selectDestino.innerHTML += `<option value="${destino.idDestino}">${destino.nombre}</option>`;
                    });
                    selectDestino.disabled = false;
                })
                .catch(error => {
                    console.error('Error cargando destinos:', error);
                    selectDestino.innerHTML = '<option value="">Error al cargar</option>';
                });
        } else {
            selectDestino.innerHTML = '<option value="">Primero elija provincia...</option>';
            selectDestino.disabled = true;
            return Promise.resolve();
        }
    }

    function verificarClaveYGuardar() {
        const form = document.getElementById('form-create-envio');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        let claveInput = document.getElementById('input-clave');
        let clave = claveInput ? claveInput.value.trim() : '';
        if (clave !== '') {
            var myModal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
            myModal.show();
        } else {
            form.submit();
        }
    }

    const chkDomicilio = document.getElementById('entrega_domicilio');
    if (chkDomicilio) {
        chkDomicilio.addEventListener('change', function() {
            const labelDir = document.getElementById('label-dir');
            if (labelDir) {
                if (this.checked) {
                    labelDir.innerHTML = 'Dirección Exacta (Dir) <span class="text-muted">(Opcional)</span>';
                } else {
                    labelDir.innerHTML = 'Dirección (Dir) <span class="text-muted">(Opcional)</span>';
                }
            }
        });
    }

    function aplicarMedidasCaja() {
        const select = document.getElementById('tipo_caja_select');
        if (!select) return;
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
                if (input) {
                    input.readOnly = false;
                    input.classList.remove('bg-light');
                }
            });
        } else {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption) {
                document.getElementById('input_largo').value = selectedOption.getAttribute('data-l') || '';
                document.getElementById('input_ancho').value = selectedOption.getAttribute('data-w') || '';
                document.getElementById('input_alto').value = selectedOption.getAttribute('data-h') || '';
                document.getElementById('input_peso').value = selectedOption.getAttribute('data-wt') || '';

                const inputs = [
                    document.getElementById('input_peso'),
                    document.getElementById('input_largo'),
                    document.getElementById('input_ancho'),
                    document.getElementById('input_alto')
                ];
                inputs.forEach(input => {
                    if (input) {
                        input.readOnly = true;
                        input.classList.add('bg-light');
                    }
                });
            }
        }
    }

    function cargarSubAgencias() {
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        const idAgencia = selectAgencia ? selectAgencia.value : '';
        const selectedOption = selectAgencia && selectAgencia.selectedIndex >= 0 ? selectAgencia.options[selectAgencia.selectedIndex] : null;
        const nombreAgencia = selectedOption ? selectedOption.text.trim().toUpperCase() : '';

        // Mostrar/Ocultar seccion de medidas si es Shalom u Olva
        const seccionMedidas = document.getElementById('seccion_medidas_caja');
        if (seccionMedidas) {
            if (nombreAgencia === 'SHALOM' || nombreAgencia === 'OLVA') {
                seccionMedidas.classList.remove('d-none');
                ['input_largo', 'input_ancho', 'input_alto', 'input_peso'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = false;
                });
                
                const selectCaja = document.getElementById('tipo_caja_select');
                if (selectCaja) {
                    const options = selectCaja.querySelectorAll('option[data-agencia]');
                    options.forEach(opt => {
                        if (opt.getAttribute('data-agencia') == idAgencia) {
                            opt.style.display = '';
                        } else {
                            opt.style.display = 'none';
                        }
                    });
                    
                    if (selectCaja.value !== 'custom') {
                        const selectedOpt = selectCaja.options[selectCaja.selectedIndex];
                        if (selectedOpt && selectedOpt.getAttribute('data-agencia') != idAgencia) {
                            selectCaja.value = 'custom';
                            aplicarMedidasCaja();
                        }
                    }
                }
            } else {
                seccionMedidas.classList.add('d-none');
                ['input_largo', 'input_ancho', 'input_alto', 'input_peso'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = true;
                });
                const selectCaja = document.getElementById('tipo_caja_select');
                if (selectCaja) {
                    selectCaja.value = 'custom';
                    aplicarMedidasCaja();
                }
            }
        }

        const selectDestino = document.getElementById('select-destino');
        const selectSubAgencia = document.getElementById('select-subagencia');
        const idDestino = selectDestino ? selectDestino.value : '';

        if (!idAgencia || !idDestino) {
            if (selectSubAgencia) {
                selectSubAgencia.innerHTML = '<option value="">Primero elija Agencia y Distrito...</option>';
                selectSubAgencia.disabled = true;
                selectSubAgencia.required = false;
            }
            return Promise.resolve();
        }

        return fetch(`/envios-provincias/subagencias-por-agencia-y-destino/${idAgencia}/${idDestino}`)
            .then(response => response.json())
            .then(data => {
                const containerSub = document.getElementById('container-subagencia');
                if (data.length > 0) {
                    if (containerSub) containerSub.style.display = 'block';
                    data.sort((a, b) => a.nombre_oficina.localeCompare(b.nombre_oficina));

                    selectSubAgencia.innerHTML = '<option value="">Seleccione oficina...</option>';
                    data.forEach(sub => {
                        const partes = sub.nombre_oficina.split(' / ');
                        const nombreTerminal = partes[partes.length - 1];
                        selectSubAgencia.innerHTML += `<option value="${sub.idSubAgencia}">${nombreTerminal} (${sub.direccion})</option>`;
                    });
                    selectSubAgencia.disabled = false;
                    selectSubAgencia.required = true;
                } else {
                    if (containerSub) containerSub.style.display = 'none';
                    selectSubAgencia.innerHTML = '<option value="">Sin oficinas registradas...</option>';
                    selectSubAgencia.disabled = true;
                    selectSubAgencia.required = false;
                }
            })
            .catch(error => {
                console.error("Error cargando oficinas:", error);
                selectSubAgencia.innerHTML = '<option value="">Error al cargar oficinas</option>';
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
                        cargarSubAgencias();
                    }
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Hubo un problema de red al intentar sincronizar.', 'error');
            });
    }

    // ─── Lógica para Persona que Recibe (Shalom + RUC) ───
    function toggleSeccionReceptorInterno() {
        const seccionReceptor = document.getElementById('seccion-receptor-interno');
        if (!seccionReceptor) return;

        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        const esShalom = selectAgencia && selectAgencia.value === '1';
        
        // Asumiendo que 3 es el ID de RUC
        const esRuc = tipoDocumentoClienteSeleccionado == 3;

        if (esShalom && esRuc) {
            seccionReceptor.classList.remove('d-none');
            document.getElementById('receptor_nombre').setAttribute('required', 'required');
            document.getElementById('receptor_dni').setAttribute('required', 'required');
        } else {
            seccionReceptor.classList.add('d-none');
            document.getElementById('receptor_nombre').removeAttribute('required');
            document.getElementById('receptor_dni').removeAttribute('required');
            document.getElementById('receptor_nombre').value = '';
            document.getElementById('receptor_dni').value = '';
            document.getElementById('receptor_telefono').value = '';
        }
    }

    // Escuchar cambio de agencia
    const selAgencia = document.querySelector('select[name="idAgencia"]');
    if(selAgencia) {
        selAgencia.addEventListener('change', toggleSeccionReceptorInterno);
    }
</script>
