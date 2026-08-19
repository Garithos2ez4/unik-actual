<script>
    function reportSerials(idAlmacen = null) {
        const baseUrl = "{{ route('seriesXProducto', [$producto->idProducto, 'ALMACEN_ID']) }}";
        const url = baseUrl.replace('ALMACEN_ID', idAlmacen || '');
        window.open(url, '_blank');
    }

    function confirmarCopia() {
        Swal.fire({
            title: '¿Copiar este producto?',
            text: 'Se creará un nuevo producto con los mismos datos. Deberás completar Modelo, PartNumber, UPC e imágenes.',
            icon: 'question',
            iconColor: '#00b1b9',
            showCancelButton: true,
            confirmButtonText: 'Sí, copiar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('createproducto', ['copy_from' => $producto->idProducto]) }}";
            }
        });
    }
</script>

<script>
    function openSeriesPdf(idProducto, idAlmacen) {
        window.open(
            `/pdf/producto-series/${idProducto}/${idAlmacen}`,
            '_blank'
        );
    }

    // Funciones para Herramientas de Servicio
    function openServiciosModal(idProducto) {
        const modal = new bootstrap.Modal(document.getElementById('modalServicios'));
        modal.show();
        loadSeriesServicios(idProducto);
    }

    function loadSeriesServicios(idProducto) {
        const tbody = document.getElementById('tbody-series-servicios');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Cargando series...</td></tr>';

        fetch(`/producto/${idProducto}/series-herramienta`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    tbody.innerHTML = '';
                    if (data.series.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No hay series registradas para este producto.</td></tr>';
                        return;
                    }

                    data.series.forEach(serie => {
                        const isChecked = serie.es_herramienta ? 'checked' : '';
                        const badgeClass = serie.estado === 'NUEVO' ? 'bg-success' : (serie.estado === 'EN_USO' ? 'bg-warning text-dark' : 'bg-secondary');

                        tbody.innerHTML += `
                            <tr>
                                <td class="fw-bold">${serie.numeroSerie}</td>
                                <td>${serie.almacen}</td>
                                <td><span class="badge ${badgeClass}">${serie.estado}</span></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input" style="cursor:pointer;" type="checkbox" role="switch" 
                                            onchange="toggleHerramienta(${serie.idRegistro}, this)" ${isChecked}>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-danger">Error: ${data.message}</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="4" class="text-danger">Error de conexión al cargar las series.</td></tr>';
            });
    }

    function toggleHerramienta(idRegistro, checkbox) {
        const isChecked = checkbox.checked;
        checkbox.disabled = true; // deshabilitar mientras carga

        fetch(`{{ route('producto.toggle.herramienta') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    idRegistro: idRegistro,
                    es_herramienta: isChecked
                })
            })
            .then(res => res.json())
            .then(data => {
                checkbox.disabled = false;
                if (!data.success) {
                    checkbox.checked = !isChecked; // revertir
                    Swal.fire('Error', data.message, 'error');
                } else {
                    // Pequeño toast o notificación visual opcional
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Serie actualizada'
                    });
                }
            })
            .catch(err => {
                console.error(err);
                checkbox.disabled = false;
                checkbox.checked = !isChecked; // revertir
                Swal.fire('Error', 'Error de conexión al guardar.', 'error');
            });
    }
</script>

{{-- Script para manejar el Precio Total Fijo --}}
<script>
    const TC_SUNAT = parseFloat("{{ $tc }}"); // Tasa de cambio SUNAT (ej. 3.52)
    const TC_FIJO = parseFloat("{{ $tasaFija }}"); // Tasa de cambio fija interna (ej. 3.80)

    function getPrecioEnDolares() {
        const divTotal = document.getElementById('div-total-price');
        const selectTipoPrecio = document.getElementById('select-tipoprecio');
        if (!divTotal || divTotal.children.length === 0) return null;

        const primerPrecioTotal = divTotal.querySelector('input[type="number"]');
        if (!primerPrecioTotal || !primerPrecioTotal.value) return null;

        let precioEnDolares = parseFloat(primerPrecioTotal.value);
        const moneda = selectTipoPrecio ? selectTipoPrecio.value : 'DOLAR';

        // Si la moneda es SOL, revertir a dólares usando TC SUNAT
        if (moneda === 'SOL') {
            precioEnDolares = precioEnDolares / TC_SUNAT;
        }
        return precioEnDolares;
    }

    function getTcEnUso() {
        const usarTcFijo = document.getElementById('usar_tc_fijo');
        let tcUsar = TC_SUNAT;
        
        if (usarTcFijo && !usarTcFijo.checked) {
            tcUsar = TC_FIJO;
            const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');
            if (tcFijoPersonalizado && tcFijoPersonalizado.value && parseFloat(tcFijoPersonalizado.value) > 0) {
                tcUsar = parseFloat(tcFijoPersonalizado.value);
            }
        }
        return tcUsar;
    }

    function actualizarPrecioTotalFijo() {
        const usarTcFijo = document.getElementById('usar_tc_fijo');
        const precioSunatInput = document.getElementById('precio-total-sunat');
        const precioFijoInput = document.getElementById('precio-total-fijo');
        const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');

        const precioEnDolares = getPrecioEnDolares();
        if (precioEnDolares === null) return;

        // Determinar qué tasa fija usar (personalizada o global)
        let tasaFijaUsar = TC_FIJO;
        if (tcFijoPersonalizado && tcFijoPersonalizado.value && parseFloat(tcFijoPersonalizado.value) > 0) {
            tasaFijaUsar = parseFloat(tcFijoPersonalizado.value);
        }

        // Precio con TC SUNAT (siempre se calcula, a menos que esté en foco)
        let precioSunatValue = (precioEnDolares * TC_SUNAT).toFixed(2);
        let precioFijoValue = (precioEnDolares * tasaFijaUsar).toFixed(2);

        let stateElement = document.getElementById('estado-product');
        if (stateElement && stateElement.value === 'LIQUIDACION' && window.APP_DATA && window.APP_DATA.isLiquidacion && window.APP_DATA.precioLiquidacionSoles) {
            precioSunatValue = parseFloat(window.APP_DATA.precioLiquidacionSoles).toFixed(2);
            precioFijoValue = precioSunatValue;
        }

        if (precioSunatInput && document.activeElement !== precioSunatInput) {
            precioSunatInput.value = precioSunatValue;
        }

        // Precio con TC Fijo (siempre se calcula, a menos que esté en foco)
        if (precioFijoInput && document.activeElement !== precioFijoInput) {
            precioFijoInput.value = precioFijoValue;
        }

        // Sync "Precio Web Directo" logic is removed, as it's now handled by the total inputs themselves
    }

    function toggleTipoCambio() {
        const usarTcFijo = document.getElementById('usar_tc_fijo');
        const precioSunatInput = document.getElementById('precio-total-sunat');
        const precioFijoInput = document.getElementById('precio-total-fijo');
        const labelSunat = document.querySelector('label[for="precio-total-sunat"]');
        const labelFijo = document.querySelector('label[for="precio-total-fijo"]');
        const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');

        if (!usarTcFijo) return;

        let tasaFijaUsar = TC_FIJO;
        if (tcFijoPersonalizado && tcFijoPersonalizado.value && parseFloat(tcFijoPersonalizado.value) > 0) {
            tasaFijaUsar = parseFloat(tcFijoPersonalizado.value);
        }

        if (usarTcFijo.checked) {
            // Switch ON → TC SUNAT es el precio principal
            if (labelSunat) labelSunat.innerHTML = 'Precio Total en Soles (TC SUNAT: {{ $tc }}) <span class="badge bg-success">En uso</span>';
            if (labelFijo) labelFijo.innerHTML = `Precio Total en Soles / Tasa Fija (${tasaFijaUsar}) <span class="badge bg-secondary">Referencia</span>`;
            if (precioSunatInput) {
                precioSunatInput.classList.add('border-success');
                precioSunatInput.classList.remove('border-warning');
            }
            if (precioFijoInput) {
                precioFijoInput.classList.remove('border-warning');
            }
        } else {
            // Switch OFF → TC Fijo es el precio principal
            if (labelSunat) labelSunat.innerHTML = 'Precio Total en Soles (TC SUNAT: {{ $tc }}) <span class="badge bg-secondary">Referencia</span>';
            if (labelFijo) labelFijo.innerHTML = `Precio Total en Soles / Tasa Fija (${tasaFijaUsar}) <span class="badge bg-warning text-dark">En uso</span>`;
            if (precioSunatInput) {
                precioSunatInput.classList.remove('border-success');
            }
            if (precioFijoInput) {
                precioFijoInput.classList.add('border-warning');
            }
        }

        actualizarPrecioTotalFijo();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const handleFocus = function () {
            window.APP_DATA.isEditingTotalPrice = true;
        };

        const handleBlur = function () {
            window.APP_DATA.isEditingTotalPrice = false;
            let precioWeb = parseFloat(this.value) || 0;
            
            // Determinar qué TC usar basándonos en qué input se editó
            let tcUsar = (this.id === 'precio-total-sunat') ? TC_SUNAT : getTcEnUso();
            
            let costoBase = window.APP_DATA.lastCalculado || 0;
            let ganancia = 0;

            let selectMoneda = document.getElementById('select-tipoprecio');
            let monedaActual = selectMoneda ? selectMoneda.value : 'DOLAR';

            if (monedaActual === 'SOL') {
                // Si la vista está en SOLES, costoBase (lastCalculado) ya está en SOLES.
                // precioWeb (lo que digitó) siempre está en SOLES.
                // Por lo tanto, la ganancia para la vista actual debe ser en SOLES.
                ganancia = precioWeb - costoBase;
            } else {
                // Si la vista está en DÓLARES, costoBase (lastCalculado) está en DÓLARES.
                // precioWeb está en SOLES, así que hay que pasarlo a DÓLARES.
                let precioVentaUsd = precioWeb / tcUsar;
                ganancia = precioVentaUsd - costoBase;
            }
            
            document.getElementById('precio-product-ganancia').value = ganancia.toFixed(2);
            
            if (typeof calcPrices === 'function') {
                calcPrices();
            }
        };

        const precioSunat = document.getElementById('precio-total-sunat');
        const precioFijo = document.getElementById('precio-total-fijo');

        if (precioSunat) {
            precioSunat.addEventListener('focus', handleFocus);
            precioSunat.addEventListener('blur', handleBlur);
        }

        if (precioFijo) {
            precioFijo.addEventListener('focus', handleFocus);
            precioFijo.addEventListener('blur', handleBlur);
        }

        document.addEventListener('calcPricesCompleted', function() {
            actualizarPrecioTotalFijo();
        });

        const usarTcFijo = document.getElementById('usar_tc_fijo');
        const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');

        if (usarTcFijo) {
            usarTcFijo.addEventListener('change', toggleTipoCambio);
            toggleTipoCambio(); // Estado inicial al cargar
        }

        if (tcFijoPersonalizado) {
            tcFijoPersonalizado.addEventListener('input', toggleTipoCambio);
        }
    });

    let _fbkProductoId = null;

    function abrirModalTitulosFbk(idProducto, nombreProducto) {
        _fbkProductoId = idProducto;
        // Limpiar estado
        document.getElementById('fbk-titulos-error').classList.add('d-none');
        document.getElementById('fbk-titulo1').value = nombreProducto || '';
        document.getElementById('fbk-titulo2').value = '';
        document.getElementById('fbk-titulo3').value = '';
        
        // Ocultar variaciones 2 y 3 inicialmente
        document.getElementById('fbk-titulos-extra').style.display = 'none';

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modalTitulosFbk'));
        modal.show();
    }

    function generarSugerenciasFbk() {
        if (!_fbkProductoId) return;

        const loading = document.getElementById('fbk-titulos-loading');
        const form    = document.getElementById('fbk-titulos-form');
        const btnSug  = document.getElementById('fbk-btn-sugerir');
        const errBox  = document.getElementById('fbk-titulos-error');

        loading.style.display = 'block';
        form.style.display    = 'none';
        btnSug.disabled       = true;
        errBox.classList.add('d-none');

        fetch(`/producto/${_fbkProductoId}/falabella-titulos-sugeridos`)
            .then(res => res.json())
            .then(data => {
                loading.style.display = 'none';
                form.style.display    = 'block';
                btnSug.disabled       = false;
                
                // Mostrar los títulos 2 y 3
                document.getElementById('fbk-titulos-extra').style.display = 'block';

                if (data.success) {
                    document.getElementById('fbk-titulo1').value = data.titulos.titulo1 || '';
                    document.getElementById('fbk-titulo2').value = data.titulos.titulo2 || '';
                    document.getElementById('fbk-titulo3').value = data.titulos.titulo3 || '';
                } else {
                    errBox.textContent = 'No se pudieron cargar las sugerencias: ' + (data.message || '');
                    errBox.classList.remove('d-none');
                }
            })
            .catch(() => {
                loading.style.display = 'none';
                form.style.display    = 'block';
                btnSug.disabled       = false;
                errBox.textContent    = 'Error de conexión al obtener sugerencias.';
                errBox.classList.remove('d-none');
            });
    }

    function descargarTemplateExpressFbk() {
        if (!_fbkProductoId) return;

        const t1 = document.getElementById('fbk-titulo1').value.trim();
        const t2 = document.getElementById('fbk-titulo2').value.trim();
        const t3 = document.getElementById('fbk-titulo3').value.trim();
        const errBox = document.getElementById('fbk-titulos-error');

        if (!t1) {
            errBox.textContent = 'El Título 1 no puede estar vacío.';
            errBox.classList.remove('d-none');
            return;
        }

        errBox.classList.add('d-none');
        const btn = document.getElementById('fbk-btn-descargar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando...';

        // POST con form invisible para forzar descarga del archivo
        const form  = document.createElement('form');
        form.method = 'POST';
        form.action = `/producto/${_fbkProductoId}/falabella-template-express`;
        form.style.display = 'none';

        const addField = (name, value) => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = name;
            input.value = value;
            form.appendChild(input);
        };

        addField('_token', document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value || '');
        addField('titulo1', t1);
        addField('titulo2', t2);
        addField('titulo3', t3);

        document.body.appendChild(form);
        form.submit();

        // Restaurar botón después de 3 segundos
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-download me-1"></i> Descargar Excel';
            document.body.removeChild(form);
            bootstrap.Modal.getInstance(document.getElementById('modalTitulosFbk'))?.hide();
        }, 3000);
    }
</script>

