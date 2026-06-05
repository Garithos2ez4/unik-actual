function checkSku() {
    let checkSku = document.getElementById('check-sku-egreso');
    let inputEgreso = document.getElementById('input-sku-egreso');
    let hiddenEgreso = document.getElementById('hidden-publicacion-sku');
    let hiddenPrecio = document.getElementById('hidden-publicacion-precio');
    let inputNumberOrder = document.getElementById('input-numero-orden');
    let divCliente = document.getElementById('div-cliente-egreso');
    let seccionPagos = document.getElementById('seccion-pagos');

    if (checkSku.checked) {
        inputEgreso.disabled = true;
        inputEgreso.value = checkSku.value;
        inputNumberOrder.disabled = true;
        inputNumberOrder.value = checkSku.value;
        hiddenEgreso.value = 'NULO';
        hiddenPrecio.value = '';
        validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
        validateIconSku.classList.add('bi-check-circle', 'text-success');
        applySkuToUnassignedItems('NULO', 'No aplica', '', true);
        if (divCliente) divCliente.style.display = 'block';
        if (seccionPagos) seccionPagos.style.display = 'flex';
    } else {
        inputEgreso.disabled = false;
        inputEgreso.value = '';
        inputNumberOrder.disabled = false;
        inputNumberOrder.value = '';
        hiddenEgreso.value = '';
        hiddenPrecio.value = '';
        validateIconSku.classList.add('bi-exclamation-circle', 'text-danger');
        validateIconSku.classList.remove('bi-check-circle', 'text-success');
        if (divCliente) divCliente.style.display = 'none';
        if (seccionPagos) seccionPagos.style.display = 'none';
        clearCliente(); // Limpiar el cliente si se oculta
    }
}

// Búsqueda de Cliente
function searchClienteAjax(inputElement) {
    let query = inputElement.value;

    function handleClickOutside(event) {
        let suggestions = document.getElementById('suggestions-cliente');
        if (!suggestions.contains(event.target) && event.target !== inputElement) {
            suggestions.innerHTML = '';
            hiddenBody.style.display = 'none';
        }
    }

    document.removeEventListener('click', handleClickOutside);
    document.addEventListener('click', handleClickOutside);

    if (query.length > 2) {
        document.getElementById('hidden-id-cliente').value = "";
        document.getElementById('btn-clear-cliente').style.display = 'none';

        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/cliente/searchcliente?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions-cliente');
                hiddenBody.style.display = 'block';
                inputElement.style.zIndex = '1000';
                suggestions.innerHTML = '';

                data.forEach(item => {
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'pe-0', 'hover-sistema-uno', 'text-truncate');
                    li.style.cursor = "pointer";

                    let nombreCompleto = item.nombre + (item.apePaterno ? ' ' + item.apePaterno : '');
                    li.innerHTML = `<strong>${item.numeroDocumento}</strong> - ${nombreCompleto}`;

                    li.addEventListener('click', function () {
                        inputElement.value = nombreCompleto;
                        document.getElementById('hidden-id-cliente').value = item.idCliente;
                        document.getElementById('btn-clear-cliente').style.display = 'block';

                        suggestions.innerHTML = '';
                        hiddenBody.style.display = 'none';
                        inputElement.style.zIndex = '1';
                        inputElement.readOnly = true;
                    });

                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions-cliente').innerHTML = '';
        document.getElementById('hidden-id-cliente').value = "";
        document.getElementById('btn-clear-cliente').style.display = 'none';
        hiddenBody.style.display = 'none';
        inputElement.style.zIndex = '1';
    }
}

function clearCliente() {
    let inputElement = document.getElementById('input-cliente-egreso');
    inputElement.value = '';
    inputElement.readOnly = false;
    document.getElementById('hidden-id-cliente').value = '';
    document.getElementById('btn-clear-cliente').style.display = 'none';
    document.getElementById('suggestions-cliente').innerHTML = '';
}

let pagosAgregados = [];
let pagoIndex = 0;

function filterCuentasDestino() {
    let selectMetodo = document.getElementById('pago-metodo');
    let selectEmpresa = document.getElementById('pago-empresa');
    let selectCuenta = document.getElementById('pago-cuenta');
    let divCuenta = document.getElementById('div-pago-cuenta');
    let divEmpresa = document.getElementById('div-pago-empresa');

    if (!selectMetodo) return;

    let textMetodo = selectMetodo.options[selectMetodo.selectedIndex]?.text.toUpperCase() || '';
    let idEmpresa = selectEmpresa.value;

    if (textMetodo.includes('TRANSFERENCIA') || textMetodo.includes('YAPE') || textMetodo.includes('PLIN')) {
        divEmpresa.style.display = 'block';
        divCuenta.style.display = 'block';

        let options = selectCuenta.querySelectorAll('option');
        options.forEach(opt => {
            if (opt.value === '') {
                opt.style.display = 'block';
                return;
            }

            let banco = opt.getAttribute('data-banco');
            let optIdEmpresa = opt.getAttribute('data-idempresa');

            let show = true;

            if (idEmpresa !== '' && optIdEmpresa !== idEmpresa) {
                show = false;
            }

            if (show) {
                if (textMetodo.includes('YAPE')) {
                    show = (banco && banco.includes('BCP')) ? true : false;
                } else if (textMetodo.includes('PLIN')) {
                    show = (banco && (banco.includes('BBVA') || banco.includes('INTERBANK') || banco.includes('SCOTIABANK'))) ? true : false;
                }
            }

            opt.style.display = show ? 'block' : 'none';
        });

        if (selectCuenta.selectedIndex > 0) {
            let selectedOpt = selectCuenta.options[selectCuenta.selectedIndex];
            if (selectedOpt.style.display === 'none') {
                selectCuenta.value = '';
            }
        }
    } else {
        divEmpresa.style.display = 'none';
        divCuenta.style.display = 'none';
        selectEmpresa.value = '';
        selectCuenta.value = '';
    }
}

document.getElementById('pago-metodo')?.addEventListener('change', function () {
    document.getElementById('pago-empresa').value = '';
    document.getElementById('pago-cuenta').value = '';
    filterCuentasDestino();
});

document.getElementById('pago-empresa')?.addEventListener('change', function () {
    document.getElementById('pago-cuenta').value = '';
    filterCuentasDestino();
});

document.getElementById('btn-add-pago')?.addEventListener('click', function () {
    let selectMetodo = document.getElementById('pago-metodo');
    let selectCuenta = document.getElementById('pago-cuenta');
    let inputMonto = document.getElementById('pago-monto');
    let inputRef = document.getElementById('pago-ref');

    let idMetodo = selectMetodo.value;
    let nombreMetodo = selectMetodo.options[selectMetodo.selectedIndex].text;
    let textMetodo = nombreMetodo.toUpperCase();

    let idCuenta = null;
    let nombreCuenta = '';

    if (textMetodo.includes('TRANSFERENCIA') || textMetodo.includes('YAPE') || textMetodo.includes('PLIN')) {
        idCuenta = selectCuenta.value;
        if (!idCuenta) {
            alertBootstrap('Debe seleccionar una Cuenta Destino para este método', 'warning');
            return;
        }
        nombreCuenta = selectCuenta.options[selectCuenta.selectedIndex].text;
        nombreMetodo += ' (' + nombreCuenta + ')';
    }

    let monto = parseFloat(inputMonto.value);
    let ref = inputRef.value.trim();

    if (!idMetodo) {
        alertBootstrap('Debe seleccionar un Método de Pago', 'warning');
        return;
    }

    if (isNaN(monto) || monto <= 0) {
        alertBootstrap('El monto debe ser mayor a 0', 'warning');
        return;
    }

    let pago = {
        id: pagoIndex++,
        idMetodo: idMetodo,
        idCuentaBancaria: idCuenta,
        nombreMetodo: nombreMetodo,
        monto: monto,
        ref: ref
    };

    pagosAgregados.push(pago);
    renderPagos();

    // Limpiar inputs
    selectMetodo.value = '';
    selectCuenta.value = '';
    document.getElementById('div-pago-cuenta').style.display = 'none';
    inputMonto.value = '';
    inputRef.value = '';
});

function renderPagos() {
    let tbody = document.querySelector('#tabla-pagos tbody');
    let tabla = document.getElementById('tabla-pagos');
    let contenedorHidden = document.getElementById('hidden-pagos-container');

    tbody.innerHTML = '';
    contenedorHidden.innerHTML = '';

    let totalPagado = 0;

    if (pagosAgregados.length > 0) {
        tabla.style.display = 'table';
    } else {
        tabla.style.display = 'none';
    }

    pagosAgregados.forEach((pago, index) => {
        totalPagado += pago.monto;

        // Fila visual
        let tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${pago.nombreMetodo}</td>
            <td>${pago.ref || '-'}</td>
            <td>
                <div class="input-group input-group-sm mb-0 mx-auto" style="max-width: 120px;">
                    <span class="input-group-text">S/</span>
                    <input type="number" class="form-control text-end" step="0.01" value="${pago.monto.toFixed(2)}" onchange="updatePagoMonto(${pago.id}, this.value)">
                </div>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removePago(${pago.id})">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);

        // Inputs ocultos para enviar al backend
        contenedorHidden.innerHTML += `
            <input type="hidden" name="pagos[${index}][idMetodo]" value="${pago.idMetodo}">
            <input type="hidden" name="pagos[${index}][monto]" value="${pago.monto}">
            <input type="hidden" name="pagos[${index}][ref]" value="${pago.ref}">
            ${pago.idCuentaBancaria ? `<input type="hidden" name="pagos[${index}][idCuentaBancaria]" value="${pago.idCuentaBancaria}">` : ''}
        `;
    });

    document.getElementById('total-pagado-text').innerText = totalPagado.toFixed(2);

    // Llamar a la validación para habilitar/deshabilitar el botón de submit
    validateSubmit();
}

// Funciones globales para que el onclick/onchange del HTML las encuentre
window.removePago = function (id) {
    pagosAgregados = pagosAgregados.filter(p => p.id !== id);
    renderPagos();
};

window.updatePagoMonto = function (id, nuevoMonto) {
    let monto = parseFloat(nuevoMonto);
    if (!isNaN(monto) && monto >= 0) {
        let pago = pagosAgregados.find(p => p.id === id);
        if (pago) {
            pago.monto = monto;
        }
    }
    renderPagos();
};

// Interceptar envío del formulario para mostrar modal
document.getElementById('form-egreso')?.addEventListener('submit', function (e) {
    e.preventDefault();

    let checkSku = document.getElementById('check-sku-egreso');
    let modalBody = document.getElementById('modal-body-confirmacion');

    let totalVenta = 0;
    document.querySelectorAll('.input-precio-item').forEach(input => {
        let val = parseFloat(input.value);
        if (!isNaN(val)) totalVenta += val;
    });

    if (checkSku && checkSku.checked) {
        // Venta de tienda

        let selectMetodo = document.getElementById('pago-metodo');
        if (selectMetodo && selectMetodo.value !== "" && pagosAgregados.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No has añadido el pago',
                text: 'Seleccionaste un método de pago pero olvidaste presionar el botón "Añadir". Por favor, añádelo antes de registrar la venta.'
            });
            return; // Detener ejecución
        }

        if (pagosAgregados.length === 0) {
            modalBody.innerHTML = `
                <div class="alert alert-warning py-2 mb-0">
                    <i class="bi bi-cash-coin fs-4 d-block mb-1"></i>
                    <strong>No seleccionaste métodos de pago.</strong><br>
                    Esta venta se guardará automáticamente como <b>EFECTIVO</b> por <b>S/ ${totalVenta.toFixed(2)}</b>.
                </div>
            `;
        } else {
            let sumaPagos = pagosAgregados.reduce((sum, p) => sum + p.monto, 0);
            if (Math.abs(sumaPagos - totalVenta) > 0.01) {
                Swal.fire({
                    icon: 'error',
                    title: 'Montos no coinciden',
                    text: `El total de la venta es S/ ${totalVenta.toFixed(2)}, pero los pagos añadidos suman S/ ${sumaPagos.toFixed(2)}. Por favor ajusta los pagos para que coincidan.`
                });
                return;
            }

            let listHtml = '<ul class="list-group list-group-flush text-start border">';
            pagosAgregados.forEach(p => {
                listHtml += `<li class="list-group-item py-1 px-2 d-flex justify-content-between align-items-center">
                    <span><small>${p.nombreMetodo} ${p.ref ? '(' + p.ref + ')' : ''}</small></span>
                    <span class="badge bg-success rounded-pill">S/ ${p.monto.toFixed(2)}</span>
                </li>`;
            });
            listHtml += '</ul>';

            modalBody.innerHTML = `
                <p class="mb-2 text-muted"><small>Se registrarán los siguientes pagos para cubrir el total de <b>S/ ${totalVenta.toFixed(2)}</b>:</small></p>
                ${listHtml}
            `;
        }
    } else {
        // Venta plataforma
        modalBody.innerHTML = `
            <div class="alert alert-info py-2 mb-0">
                <i class="bi bi-box-seam fs-4 d-block mb-1"></i>
                Venta de Plataforma (SKU vinculado).<br>No se registrarán métodos de pago en esta tabla.
            </div>
        `;
    }

    let modal = new bootstrap.Modal(document.getElementById('modalConfirmacionPago'));
    modal.show();
});

document.getElementById('check-sku-egreso')?.addEventListener('change', checkSku);
document.getElementById('check-sku-egreso')?.addEventListener('change', validateSubmit);
