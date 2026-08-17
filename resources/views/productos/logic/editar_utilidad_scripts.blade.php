<script>
    let _utilidadData = {};

    function abrirModalEditarUtilidad(idProducto) {
        fetch('{{ url("productos/get-utilidad") }}/' + idProducto)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    _utilidadData = data;
                    document.getElementById('editUtilidadIdProducto').value = idProducto;
                    document.getElementById('editUtilidadNombreProducto').textContent = data.nombreProducto;
                    document.getElementById('editUtilidadNombreProducto').title = data.nombreProducto;
                    document.getElementById('editUtilidadPrecioBase').value = parseFloat(data.precioDolar).toFixed(2);
                    document.getElementById('editUtilidadPrecioIgv').value = parseFloat(data.precioConIgv).toFixed(2);
                    document.getElementById('editUtilidadPrecioBaseSoles').value = parseFloat(data.precioBaseSoles).toFixed(2);
                    document.getElementById('editUtilidadPrecioIgvSoles').value = parseFloat(data.precioIgvSoles).toFixed(2);
                    document.getElementById('editUtilidadGanancia').value = parseFloat(data.gananciaExtra).toFixed(2);
                    
                    if (data.isLiquidacion) {
                        document.getElementById('editUtilidadAlertaLiquidacion').classList.remove('d-none');
                    } else {
                        document.getElementById('editUtilidadAlertaLiquidacion').classList.add('d-none');
                    }
                    
                    recalcularPreviewUtilidad();
                    
                    let showModal = () => {
                        let modal = new bootstrap.Modal(document.getElementById('modalEditarUtilidad'));
                        modal.show();
                    };
                    
                    showModal();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un error al obtener la utilidad.', 'error');
            });
    }

    function recalcularPreviewUtilidad() {
        let ganancia = parseFloat(document.getElementById('editUtilidadGanancia').value) || 0;
        let precioCalculado = parseFloat(_utilidadData.precioCalculado) || 0;
        let tc = parseFloat(_utilidadData.tasaCambio) || 0;

        let precioVentaUsd = precioCalculado + ganancia;
        let precioVentaSoles = precioVentaUsd * tc;

        // Sobrescribir si es liquidación
        if (_utilidadData.isLiquidacion && _utilidadData.precioLiquidacionSoles) {
            precioVentaSoles = parseFloat(_utilidadData.precioLiquidacionSoles);
            precioVentaUsd = precioVentaSoles / tc;
            // Opcional: Deshabilitar campos o advertir
            document.getElementById('editUtilidadPrecioWeb').readOnly = true;
            document.getElementById('editUtilidadPrecioWeb').title = "Precio fijado por liquidación";
        } else {
            document.getElementById('editUtilidadPrecioWeb').readOnly = false;
            document.getElementById('editUtilidadPrecioWeb').title = "";
        }

        document.getElementById('editUtilidadPrecioVentaUsd').textContent = '$' + precioVentaUsd.toFixed(2);
        document.getElementById('editUtilidadTC').textContent = tc.toFixed(2) + (_utilidadData.tipoTcLabel ? ' (' + _utilidadData.tipoTcLabel + ')' : '');
        document.getElementById('editUtilidadPrecioWeb').value = precioVentaSoles.toFixed(2);
    }

    function recalcularDesdeBase() {
        let nuevoPrecioBase = parseFloat(document.getElementById('editUtilidadPrecioBase').value) || 0;
        
        // Update visual fields
        let tc = parseFloat(_utilidadData.tasaCambio) || 0;
        let precioConIgv = nuevoPrecioBase * 1.18;
        
        document.getElementById('editUtilidadPrecioIgv').value = precioConIgv.toFixed(2);
        document.getElementById('editUtilidadPrecioBaseSoles').value = (nuevoPrecioBase * tc).toFixed(2);
        document.getElementById('editUtilidadPrecioIgvSoles').value = (precioConIgv * tc).toFixed(2);
        
        // Exact recalculation using the server-provided multiplier
        let multiplier = parseFloat(_utilidadData.precioCalculadoMultiplier) || 0;
        _utilidadData.precioCalculado = nuevoPrecioBase * multiplier;

        recalcularPreviewUtilidad();
    }

    function recalcularDesdeIgv() {
        let nuevoPrecioIgv = parseFloat(document.getElementById('editUtilidadPrecioIgv').value) || 0;
        let nuevoPrecioBase = nuevoPrecioIgv / 1.18;
        
        document.getElementById('editUtilidadPrecioBase').value = nuevoPrecioBase.toFixed(2);
        
        let tc = parseFloat(_utilidadData.tasaCambio) || 0;
        
        document.getElementById('editUtilidadPrecioBaseSoles').value = (nuevoPrecioBase * tc).toFixed(2);
        document.getElementById('editUtilidadPrecioIgvSoles').value = (nuevoPrecioIgv * tc).toFixed(2);
        
        // Exact recalculation using the server-provided multiplier
        let multiplier = parseFloat(_utilidadData.precioCalculadoMultiplier) || 0;
        _utilidadData.precioCalculado = nuevoPrecioBase * multiplier;
        
        recalcularPreviewUtilidad();
    }

    function recalcularDesdeWeb() {
        let precioWeb = parseFloat(document.getElementById('editUtilidadPrecioWeb').value) || 0;
        let tc = parseFloat(_utilidadData.tasaCambio) || 1;
        let precioCalculado = parseFloat(_utilidadData.precioCalculado) || 0;
        
        // Calcular precio de venta USD
        let precioVentaUsd = precioWeb / tc;
        
        // Calcular la ganancia
        let ganancia = precioVentaUsd - precioCalculado;
        
        document.getElementById('editUtilidadGanancia').value = ganancia.toFixed(2);
        
        // Solo actualizar texto USD para no causar un loop infinito con el input
        document.getElementById('editUtilidadPrecioVentaUsd').textContent = '$' + precioVentaUsd.toFixed(2);
    }

    function guardarUtilidad() {
        let idProducto = document.getElementById('editUtilidadIdProducto').value;
        let ganancia = document.getElementById('editUtilidadGanancia').value;

        if(!idProducto || ganancia === '' || ganancia === null) {
            Swal.fire('Advertencia', 'Por favor complete el campo de ganancia.', 'warning');
            return;
        }

        let btn = document.getElementById('btnGuardarUtilidad');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        fetch('{{ route("producto.updateUtilidad") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                idProducto: idProducto,
                ganancia: ganancia,
                precioDolar: document.getElementById('editUtilidadPrecioBase').value
            })
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Guardar';
            
            if(data.success) {
                let modalEl = document.getElementById('modalEditarUtilidad');
                let modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                Swal.fire({
                    title: 'Éxito',
                    text: data.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    let btnSearch = document.getElementById('btn-search-product');
                    if(btnSearch) {
                        btnSearch.click();
                    } else {
                        location.reload();
                    }
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Guardar';
            Swal.fire('Error', 'Ocurrió un error al guardar.', 'error');
        });
    }
</script>
