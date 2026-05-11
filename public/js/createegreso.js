
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
    let inputNumberOrder = document.getElementById('input-numero-orden');

    if (checkSku.checked) {
        inputEgreso.disabled = true;
        inputEgreso.value = checkSku.value;
        inputNumberOrder.disabled = true;
        inputNumberOrder.value = checkSku.value;
        hiddenEgreso.value = 'NULO';
        validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
        validateIconSku.classList.add('bi-check-circle', 'text-success');
    } else {
        inputEgreso.disabled = false;
        inputEgreso.value = '';
        inputNumberOrder.disabled = false;
        inputNumberOrder.value = '';
        hiddenEgreso.value = '';
        validateIconSku.classList.add('bi-exclamation-circle', 'text-danger');
        validateIconSku.classList.remove('bi-check-circle', 'text-success');
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
    let inputHiddenSku = createInput([], null, 'hidden', selectedSkuId, `items[${currentIndex}][idpublicacion]`);
    
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
                // Aquí se elimina el producto y se actualiza el contador
                divRowItem.remove(); // Elimina el producto del DOM
                cartManager.eliminarProducto(); // Resta del contador
                validateSubmit(); // Validamos el estado del formulario

                productosAgregados = productosAgregados.filter(id => id !== object.idRegistroProducto);

            }
        ]
    );
    divColBtnDelete.appendChild(btnDeleteItem);

    let divColModelo = createDiv(['col-4'], null);
    divColModelo.innerHTML = 'Modelo: ' + object.modelo;

    let divColCodigo = createDiv(['col-3'], null);
    divColCodigo.innerHTML = 'Codigo: ' + object.codigoProducto;

    let divColSerial = createDiv(['col-3'], null);
    divColSerial.innerHTML = 'SN: ' + object.numeroSerie;

    let divColEstado = createDiv(['col-2', 'text-end'], null);
    divColEstado.innerHTML = object.estado;

    let selectedSkuText = document.getElementById('input-sku-egreso').value;
    let divColSku = createDiv(['col-12', 'mt-1', 'text-primary'], null);
    divColSku.innerHTML = '<small><i class="bi bi-tag-fill"></i> SKU Vinculado: <strong>' + (selectedSkuText || 'Ninguno') + '</strong></small>';

    divRowContent.appendChild(divColTitle);
    divRowContent.appendChild(divColBtnDelete);
    divRowContent.appendChild(divColModelo);
    divRowContent.appendChild(divColCodigo);
    divRowContent.appendChild(divColSerial);
    divRowContent.appendChild(divColEstado);
    divRowContent.appendChild(divColSku);
    divColContent.appendChild(divRowContent);
    divRowItem.appendChild(divColImg);
    divRowItem.appendChild(inputHiddenRegistro);
    divRowItem.appendChild(inputHiddenSku);
    divRowItem.appendChild(divColContent);
    itemEgresoDiv.insertBefore(divRowItem, itemEgresoDiv.firstChild);


    cartManager.agregarProducto();
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
    validateSubmit();
})
