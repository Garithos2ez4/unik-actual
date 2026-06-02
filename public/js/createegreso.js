
const validateIconSku = document.getElementById('sku-modal-egreso-validate');
const hiddenBody = document.getElementById('hidden-body');
const itemEgresoDiv = document.getElementById('div-items-create-egreso');
const btnSubmitCreateEgreso = document.getElementById('btn-create-egreso-submit');
var path = window.assetUrl;

const cartManager = {
    productosAgregados: 0,
    agregarProducto: function () {
        this.productosAgregados++;
        document.getElementById('contador-productos').textContent = `Productos Agregados: ${this.productosAgregados}`;
    },
    eliminarProducto: function () {
        if (this.productosAgregados > 0) {
            this.productosAgregados--;
            document.getElementById('contador-productos').textContent = `Productos Agregados: ${this.productosAgregados}`;
        }
    }
};

// Asegúrate de usar el objeto cartManager globalmente
window.cartManager = cartManager;

function searchPublicacion(inputElement) {
    let query = inputElement.value;

    function handleClickOutside(event) {
        let suggestions = document.getElementById('suggestions-sku');
        if (!suggestions.contains(event.target) && event.target !== inputElement) {
            suggestions.innerHTML = ''; // Limpiar sugerencias si se hace clic fuera del input
            hiddenBody.style.display = 'none';
        }
    }

    // Agregar el manejador de clics al documento
    document.removeEventListener('click', handleClickOutside);
    document.addEventListener('click', handleClickOutside);

    if (query.length > 2) { // Comenzar la búsqueda después de 3 caracteres
        document.getElementById('hidden-publicacion-sku').value = "";
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/searchpublicacion?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions-sku');
                hiddenBody.style.display = 'block';
                inputElement.style.zIndex = '1000';
                suggestions.innerHTML = '';

                // AUTO-SELECT si hay una coincidencia exacta (ideal para escáneres)
                let exactMatch = data.find(item => item.sku.toUpperCase() === query.toUpperCase());
                if (exactMatch) {
                    document.getElementById('hidden-publicacion-sku').value = exactMatch.idPublicacion;
                    document.getElementById('hidden-publicacion-precio').value = exactMatch.precio;
                    validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
                    validateIconSku.classList.add('bi-check-circle', 'text-success');
                    applySkuToUnassignedItems(exactMatch.idPublicacion, exactMatch.sku, exactMatch.precio);
                    validateSubmit();
                } else {
                    document.getElementById('hidden-publicacion-sku').value = "";
                    validateIconSku.classList.add('bi-exclamation-circle', 'text-danger');
                    validateIconSku.classList.remove('bi-check-circle', 'text-success');
                }

                data.forEach(item => {
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'pe-0');
                    li.classList.add('hover-sistema-uno', 'text-truncate');
                    li.style.cursor = "pointer";

                    let divRow = document.createElement('div');
                    divRow.classList.add('row', 'w-100');

                    let colSerie = document.createElement('div');
                    colSerie.classList.add('col-md-8');
                    colSerie.textContent = item.sku;

                    let colAlmacen = document.createElement('div');
                    colAlmacen.classList.add('col-md-4', 'text-end');
                    colAlmacen.textContent = item.fechaPublicacion;

                    let colProducto = document.createElement('div');
                    colProducto.classList.add('col-md-12');
                    let smallProducto = document.createElement('em');
                    smallProducto.textContent = item.titulo;
                    smallProducto.style.fontSize = '12px';
                    colProducto.appendChild(smallProducto);

                    divRow.appendChild(colSerie);
                    divRow.appendChild(colAlmacen);
                    divRow.appendChild(colProducto);
                    li.appendChild(divRow);

                    li.addEventListener('click', function () {
                        inputElement.value = item.sku;
                        document.getElementById('hidden-publicacion-sku').value = item
                            .idPublicacion;
                        hiddenBody.style.display = 'none';
                        inputElement.style.zIndex = '1';
                        suggestions.innerHTML = ''; // Limpiar sugerencias después de seleccionar una
                        validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
                        validateIconSku.classList.add('bi-check-circle', 'text-success');
                        document.getElementById('hidden-publicacion-precio').value = item.precio;
                        applySkuToUnassignedItems(item.idPublicacion, item.sku, item.precio);
                        validateSubmit();
                    });

                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions-sku').innerHTML = ''; // Limpiar si hay menos de 3 caracteres
        document.getElementById('hidden-publicacion-sku').value = "";
        document.getElementById('hidden-publicacion-precio').value = "";
        validateIconSku.classList.add('bi-exclamation-circle', 'text-danger');
        validateIconSku.classList.remove('bi-check-circle', 'text-success');
        hiddenBody.style.display = 'none';
        inputElement.style.zIndex = '1';
    }
}

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

let productosAgregados = [];
let itemIndex = 0;

function searchRegistro(inputElement) {
    let query = inputElement.value;

    function handleClickOutside(event) {
        let suggestions = document.getElementById('suggestions-serial-number');
        if (!suggestions.contains(event.target) && event.target !== inputElement) {
            suggestions.innerHTML = ''; // Limpiar sugerencias si se hace clic fuera del input
            hiddenBody.style.display = 'none';
        }
    }

    // Agregar el manejador de clics al documento
    document.addEventListener('click', handleClickOutside);

    if (query.length > 2) { // Comenzar la búsqueda después de 3 caracteres
        document.getElementById('hidden-product-serial-number').value = "";
        let xhr = new XMLHttpRequest();
        let excluded = productosAgregados.join(',');
        xhr.open('GET', `/egresos/searchregistro?query=${query}&exclude=${excluded}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions-serial-number');
                hiddenBody.style.display = 'block';
                inputElement.style.zIndex = '1000';
                suggestions.innerHTML = '';


                data.forEach(item => {

                    if (productosAgregados.includes(item.idRegistroProducto)) {
                        return;
                    }

                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'pe-0');
                    li.classList.add('hover-sistema-uno', 'text-truncate');
                    li.style.cursor = "pointer";

                    let divRow = document.createElement('div');
                    divRow.classList.add('row', 'w-100');

                    let colSerie = document.createElement('div');
                    colSerie.classList.add('col-md-8');
                    colSerie.textContent = item.numeroSerie;

                    let colAlmacen = document.createElement('div');
                    colAlmacen.classList.add('col-md-4', 'text-end');
                    colAlmacen.textContent = item.almacen;

                    let colProducto = document.createElement('div');
                    colProducto.classList.add('col-md-12');
                    let smallProducto = document.createElement('em');
                    smallProducto.textContent = item.nombreProducto;
                    smallProducto.style.fontSize = '12px';
                    colProducto.appendChild(smallProducto);

                    divRow.appendChild(colSerie);
                    divRow.appendChild(colAlmacen);
                    divRow.appendChild(colProducto);
                    li.appendChild(divRow);

                    li.addEventListener('click', function () {
                        inputElement.value = item.numeroSerie;
                        document.getElementById('hidden-product-serial-number').value = item
                            .idRegistroProducto;
                        suggestions.innerHTML = '';
                        hiddenBody.style.display = 'none';
                        inputElement.style.zIndex = '1';
                        createItem(item, query);
                        validateSubmit();
                    });

                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions-serial-number').innerHTML = ''; // Limpiar si hay menos de 3 caracteres
        document.getElementById('hidden-product-serial-number').value = "";
        hiddenBody.style.display = 'none';
        inputElement.style.zIndex = '1';
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

function createItem(object, query) {
    if (object == null || Object.keys(object).length == 0) {
        alertBootstrap('Producto ' + query + ' no encontrado', 'warning');
        return;
    }

    if (validateSerialById(object.idRegistroProducto)) {
        alertBootstrap('Producto ' + object.numeroSerie + ' ya agregado', 'warning');
        return;
    }

    productosAgregados.push(object.idRegistroProducto);

    let selectedSkuId = document.getElementById('hidden-publicacion-sku').value;

    let currentIndex = itemIndex++;

    let divRowItem = createDiv(['row', 'pt-2', 'pb-2', 'border'], null);
    let inputHiddenRegistro = createInput(['body-form', 'hidden-form'], null, 'hidden', object.idRegistroProducto, `items[${currentIndex}][idregistro]`);
    let inputHiddenSku = createInput(['hidden-sku-item'], null, 'hidden', selectedSkuId, `items[${currentIndex}][idpublicacion]`);

    let divColImg = createDiv(['col-3', 'col-md-1'], null);
    let divColContent = createDiv(['col-9', 'col-md-11'], null);
    let divRowContent = createDiv(['row'], null);

    let imgItem = document.createElement('img');
    imgItem.classList.add('w-100', 'border');
    imgItem.style.width = '100%';
    imgItem.src = path + '/' + object.image;
    divColImg.appendChild(imgItem);

    let divColTitle = createDiv(['col-10', 'pt-2'], null);
    let h4Title = createH5(null, null, object.nombreProducto);
    divColTitle.appendChild(h4Title);

    let divColBtnDelete = createDiv(['col-2', 'text-end'], null);
    let btnDeleteItem = createLink(
        ['text-danger', 'fs-4'],
        null,
        '<i class="bi bi-x-lg"></i>',
        'javascript:void(0)',
        [
            () => {
                divRowItem.remove();
                cartManager.eliminarProducto();
                validateSubmit();
                calculateTotalVenta();

                productosAgregados = productosAgregados.filter(id => id !== object.idRegistroProducto);
            }
        ]
    );
    divColBtnDelete.appendChild(btnDeleteItem);

    let divColModelo = createDiv(['col-12', 'col-md-3'], null);
    divColModelo.innerHTML = 'Modelo: ' + object.modelo;

    let divColCodigo = createDiv(['col-12', 'col-md-2'], null);
    divColCodigo.innerHTML = 'Codigo: ' + object.codigoProducto;

    let divColSerial = createDiv(['col-12', 'col-md-3'], null);
    divColSerial.innerHTML = 'SN: ' + object.numeroSerie;

    let divColEstado = createDiv(['col-6', 'col-md-2', 'text-md-center'], null);
    divColEstado.innerHTML = object.estado;

    let divColPrecio = createDiv(['col-6', 'col-md-2', 'text-end'], null);
    let inputPrecio = document.createElement('input');
    inputPrecio.type = 'number';
    inputPrecio.step = '0.01';
    inputPrecio.name = `items[${currentIndex}][precioVenta]`;
    inputPrecio.className = 'form-control form-control-sm border-success text-end input-precio-item';
    inputPrecio.placeholder = 'Precio Venta';
    inputPrecio.title = 'Precio Venta';

    let selectedSkuPrecio = document.getElementById('hidden-publicacion-precio').value;
    let fallbackPrecio = object.precioSoles || '';
    inputPrecio.dataset.precioSoles = fallbackPrecio;

    if (selectedSkuPrecio && selectedSkuPrecio !== 'null' && selectedSkuPrecio !== 'undefined' && selectedSkuPrecio !== 'NULO' && !isNaN(parseFloat(selectedSkuPrecio))) {
        inputPrecio.value = selectedSkuPrecio;
    } else {
        inputPrecio.value = fallbackPrecio;
    }

    inputPrecio.addEventListener('input', calculateTotalVenta);

    divColPrecio.appendChild(inputPrecio);

    let selectedSkuText = document.getElementById('input-sku-egreso').value;
    let textColorClass = selectedSkuId ? 'text-success' : 'text-danger';
    let divColSku = createDiv(['col-12', 'mt-1', 'sku-text-display', textColorClass], null);
    divColSku.innerHTML = '<small><i class="bi bi-tag-fill"></i> SKU Vinculado: <strong>' + (selectedSkuText || 'FALTA ASIGNAR SKU') + '</strong></small>';

    divRowContent.appendChild(divColTitle);
    divRowContent.appendChild(divColBtnDelete);
    divRowContent.appendChild(divColModelo);
    divRowContent.appendChild(divColCodigo);
    divRowContent.appendChild(divColSerial);
    divRowContent.appendChild(divColEstado);
    divRowContent.appendChild(divColPrecio);
    divRowContent.appendChild(divColSku);
    divColContent.appendChild(divRowContent);
    divRowItem.appendChild(divColImg);
    divRowItem.appendChild(inputHiddenRegistro);
    divRowItem.appendChild(inputHiddenSku);
    divRowItem.appendChild(divColContent);
    itemEgresoDiv.insertBefore(divRowItem, itemEgresoDiv.firstChild);

    cartManager.agregarProducto();
    calculateTotalVenta();
}

function applySkuToUnassignedItems(skuId, skuText, skuPrecio, forceAll = false) {
    let hiddenSkus = document.querySelectorAll('.hidden-sku-item');
    let textDisplays = document.querySelectorAll('.sku-text-display');
    let precioInputs = document.querySelectorAll('.input-precio-item');

    hiddenSkus.forEach((hiddenInput, index) => {
        // Solo aplicar si se fuerza, o si el item no tiene un SKU asignado previamente
        let isUnassigned = !hiddenInput.value || hiddenInput.value === 'NULO' || hiddenInput.value === '';

        if (forceAll || isUnassigned) {
            hiddenInput.value = skuId;
            textDisplays[index].innerHTML = '<small><i class="bi bi-tag-fill"></i> SKU Vinculado: <strong>' + skuText + '</strong></small>';
            textDisplays[index].classList.remove('text-danger');
            textDisplays[index].classList.add('text-success');

            if (skuPrecio !== undefined && skuPrecio !== null && skuPrecio !== '' && skuPrecio !== 'null' && !isNaN(parseFloat(skuPrecio))) {
                precioInputs[index].value = skuPrecio;
            } else {
                precioInputs[index].value = precioInputs[index].dataset.precioSoles || '';
            }
        }
    });
    calculateTotalVenta();
}

function calculateTotalVenta() {
    let inputs = document.querySelectorAll('.input-precio-item');
    let total = 0;

    inputs.forEach(input => {
        let val = parseFloat(input.value);
        if (!isNaN(val)) {
            total += val;
        }
    });

    let contenedor = document.getElementById('contenedor-total-venta');
    let spanTotal = document.getElementById('span-total-venta');

    if (inputs.length > 0) {
        contenedor.style.display = 'block';
        spanTotal.textContent = total.toFixed(2);
    } else {
        contenedor.style.display = 'none';
        spanTotal.textContent = '0.00';
    }
}

function validateSerialById(id) {
    let inputHidden = document.querySelectorAll('.hidden-form');

    return Array.from(inputHidden).some(function (x) {
        return x.value == id;
    });
}


function validateSubmit() {
    let validate = true;
    let inputsCab = document.querySelectorAll('.cab-form');
    let inputBody = document.querySelectorAll('.body-form');

    inputsCab.forEach(function (x) {
        if (x.value === '') {
            validate = false;
        }
    });

    if (inputBody.length < 1) {
        validate = false;
    }

    // Validación estricta de pagos (Solo si agregaron al menos 1)
    let checkSku = document.getElementById('check-sku-egreso');
    if (checkSku && checkSku.checked) {
        let totalVenta = 0;
        document.querySelectorAll('.input-precio-item').forEach(input => {
            let val = parseFloat(input.value);
            if (!isNaN(val)) totalVenta += val;
        });

        if (pagosAgregados.length > 0) {
            let totalPagado = 0;
            pagosAgregados.forEach(p => totalPagado += p.monto);

            // Si el total de la venta es mayor a 0, debe ser cubierto por los pagos
            if (totalVenta > 0 && Math.abs(totalVenta - totalPagado) > 0.01) {
                validate = false;
            }
        }
    }

    btnSubmitCreateEgreso.disabled = !validate;
}

function scanOperations() {
    searchCodeToController(getSerial());
}

function searchCodeToController(query) {
    let data = null;
    let xhr = new XMLHttpRequest();
    xhr.open('GET', `/egresos/getoneegreso?query=${query}`, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            data = JSON.parse(xhr.responseText);
            createItem(data, query);
        }
    };
    xhr.send();
    return data;
}


document.getElementById('check-sku-egreso').addEventListener('change', checkSku);
document.getElementById('check-sku-egreso').addEventListener('change', validateSubmit);

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input').forEach(function (x) {
        x.addEventListener('input', validateSubmit);
    });

    // Validaciones de fecha
    const inputFechaPedido = document.getElementById('fechapedido');
    const inputFechaDespacho = document.getElementById('fechadespacho');

    if (inputFechaPedido && inputFechaDespacho) {
        inputFechaPedido.addEventListener('blur', function () {
            // No permitir fechas anteriores a 2024
            if (this.value && this.value < '2024-01-01') {
                alertBootstrap('La fecha de pedido no puede ser anterior al año 2024', 'warning');
                this.value = '2024-01-01';
            }
            // La fecha de despacho no puede ser anterior a la de pedido
            inputFechaDespacho.min = this.value;
            if (inputFechaDespacho.value && inputFechaDespacho.value < this.value) {
                inputFechaDespacho.value = this.value;
            }
            validateSubmit();
        });

        inputFechaDespacho.addEventListener('blur', function () {
            if (inputFechaPedido.value && this.value < inputFechaPedido.value) {
                alertBootstrap('La fecha de despacho no puede ser anterior a la fecha de pedido', 'warning');
                this.value = inputFechaPedido.value;
            }
            validateSubmit();
        });
    }

    validateSubmit();
});

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

document.getElementById('btn-confirmar-guardar')?.addEventListener('click', function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
    document.getElementById('form-egreso').submit();
});


