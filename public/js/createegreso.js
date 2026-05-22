
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
                    validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
                    validateIconSku.classList.add('bi-check-circle', 'text-success');
                    applySkuToUnassignedItems(exactMatch.idPublicacion, exactMatch.sku);
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

    if (checkSku.checked) {
        inputEgreso.disabled = true;
        inputEgreso.value = checkSku.value;
        inputNumberOrder.disabled = true;
        inputNumberOrder.value = checkSku.value;
        hiddenEgreso.value = 'NULO';
        hiddenPrecio.value = '';
        validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
        validateIconSku.classList.add('bi-check-circle', 'text-success');
        applySkuToUnassignedItems('NULO', 'No aplica', '');
        if (divCliente) divCliente.style.display = 'block';
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
        xhr.open('GET', `/egresos/searchregistro?query=${query}`, true);
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

    let divColImg = createDiv(['col-1'], null);
    let divColContent = createDiv(['col-11'], null);
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

    let divColModelo = createDiv(['col-3'], null);
    divColModelo.innerHTML = 'Modelo: ' + object.modelo;

    let divColCodigo = createDiv(['col-2'], null);
    divColCodigo.innerHTML = 'Codigo: ' + object.codigoProducto;

    let divColSerial = createDiv(['col-3'], null);
    divColSerial.innerHTML = 'SN: ' + object.numeroSerie;

    let divColEstado = createDiv(['col-2', 'text-center'], null);
    divColEstado.innerHTML = object.estado;

    let divColPrecio = createDiv(['col-2', 'text-end'], null);
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
    
    if(selectedSkuPrecio && selectedSkuPrecio !== 'null' && selectedSkuPrecio !== 'undefined' && selectedSkuPrecio !== 'NULO' && !isNaN(parseFloat(selectedSkuPrecio))) {
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

function applySkuToUnassignedItems(skuId, skuText, skuPrecio) {
    let hiddenSkus = document.querySelectorAll('.hidden-sku-item');
    let textDisplays = document.querySelectorAll('.sku-text-display');
    let precioInputs = document.querySelectorAll('.input-precio-item');

    hiddenSkus.forEach((hiddenInput, index) => {
        hiddenInput.value = skuId;
        textDisplays[index].innerHTML = '<small><i class="bi bi-tag-fill"></i> SKU Vinculado: <strong>' + skuText + '</strong></small>';
        textDisplays[index].classList.remove('text-danger');
        textDisplays[index].classList.add('text-success');

        if (skuPrecio !== undefined && skuPrecio !== null && skuPrecio !== '' && skuPrecio !== 'null' && !isNaN(parseFloat(skuPrecio))) {
            precioInputs[index].value = skuPrecio;
        } else {
            precioInputs[index].value = precioInputs[index].dataset.precioSoles || '';
        }
    });
    calculateTotalVenta();
}

function calculateTotalVenta() {
    let inputs = document.querySelectorAll('.input-precio-item');
    let total = 0;
    
    inputs.forEach(input => {
        let val = parseFloat(input.value);
        if(!isNaN(val)) {
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
        inputFechaPedido.addEventListener('blur', function() {
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

        inputFechaDespacho.addEventListener('blur', function() {
            if (inputFechaPedido.value && this.value < inputFechaPedido.value) {
                alertBootstrap('La fecha de despacho no puede ser anterior a la fecha de pedido', 'warning');
                this.value = inputFechaPedido.value;
            }
            validateSubmit();
        });
    }

    validateSubmit();
})
