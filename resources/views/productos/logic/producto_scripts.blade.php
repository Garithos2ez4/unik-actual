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

        // Precio con TC SUNAT (siempre se calcula)
        if (precioSunatInput) {
            precioSunatInput.value = (precioEnDolares * TC_SUNAT).toFixed(2);
        }

        // Precio con TC Fijo (siempre se calcula usando la tasa determinada)
        if (precioFijoInput) {
            precioFijoInput.value = (precioEnDolares * tasaFijaUsar).toFixed(2);
        }
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
</script>

