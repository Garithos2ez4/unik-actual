const rowSerialVacio = document.getElementById('row-product-serial-number-vacio');
const rowSerialExists = document.getElementById('row-product-serial-number-exists');

function validateEgreso() {
    let inputsEgreso = document.querySelectorAll('.input-egreso');
    let disabledInput = false;

    inputsEgreso.forEach(function (x) {
        if (x.value == '') {
            disabledInput = true;
        }
    });

    return disabledInput;
}
function filtrarPorDia(fechaSeleccionada) {
    // Obtenemos la URL actual limpia (sin los parámetros ?dia= anteriores)
    let urlBase = window.location.href.split('?')[0];

    if (fechaSeleccionada) {
        // Redirigimos agregando el día a la URL
        window.location.href = urlBase + '?dia=' + fechaSeleccionada;
    } else {
        // Si el usuario borra el día usando la "X" del input, recargamos el mes completo
        window.location.href = urlBase;
    }
}
function handleBtnRegistrar() {
    let btnRegEgreso = document.getElementById('btnRegistrarEgreso');
    if (btnRegEgreso) {
        btnRegEgreso.disabled = validateEgreso();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    handleBtnRegistrar();
});


let checkSkuEgreso = document.getElementById('check-sku-egreso');
if (checkSkuEgreso) {
    checkSkuEgreso.addEventListener('change', handleBtnRegistrar);
}

let allInputsEgreso = document.querySelectorAll('.input-egreso');
allInputsEgreso.forEach(function (x) {
    x.addEventListener('input', handleBtnRegistrar);
});


function updateDataRowSerial(object) {
    let imagen = rowSerialExists.querySelector('img');
    let title = rowSerialExists.querySelector('h6');
    let modelo = rowSerialExists.querySelector('span');
    let codigo = rowSerialExists.querySelector('.cod');
    let estado = rowSerialExists.querySelector('p');

    imagen.src = path + '/' + object.image;
    title.textContent = object.nombreProducto;
    modelo.textContent = object.modelo;
    codigo.textContent = object.codigoProducto;
    estado.textContent = object.estado;
}


function searchEgreso(inputElement) {
    let query = inputElement.value;
    let hiddenBody = document.getElementById('hidden-body');
    function handleClickOutside(event) {
        let suggestions = document.getElementById('suggestions-egresos');
        if (!suggestions.contains(event.target) && event.target !== inputElement) {
            suggestions.innerHTML = ''; // Limpiar sugerencias si se hace clic fuera del input
            hiddenBody.style.display = 'none';
        }
    }

    // Agregar el manejador de clics al documento
    document.addEventListener('click', handleClickOutside);

    if (query.length > 2) { // Comenzar la búsqueda después de 3 caracteres
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/egresos/searchegreso?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions-egresos');
                hiddenBody.style.display = 'block';
                suggestions.innerHTML = '';

                data.forEach(item => {
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'pe-0');
                    li.classList.add('hover-sistema-uno', 'text-truncate');
                    li.style.cursor = "pointer";

                    let divRow = document.createElement('div');
                    divRow.classList.add('row', 'w-100');

                    let colSerie = document.createElement('div');
                    colSerie.classList.add('col-md-12', 'd-flex', 'justify-content-between', 'align-items-center');

                    let spanSerie = document.createElement('span');
                    spanSerie.textContent = item.numeroSerie;

                    let spanEstado = document.createElement('span');
                    spanEstado.classList.add('badge');
                    spanEstado.style.fontSize = '10px';

                    // Colores según estado
                    if (item.estado === 'ENTREGADO') spanEstado.classList.add('bg-success');
                    else if (item.estado === 'DEVOLUCION') spanEstado.classList.add('bg-warning', 'text-dark');
                    else if (item.estado === 'GARANTIA') spanEstado.classList.add('bg-danger');
                    else spanEstado.classList.add('bg-secondary');

                    spanEstado.textContent = item.estado;

                    colSerie.appendChild(spanSerie);
                    colSerie.appendChild(spanEstado);

                    let colProducto = document.createElement('div');
                    colProducto.classList.add('col-md-12');
                    let smallProducto = document.createElement('em');
                    smallProducto.textContent = item.codigoProducto;
                    smallProducto.style.fontSize = '12px';
                    colProducto.appendChild(smallProducto);

                    divRow.appendChild(colSerie);
                    divRow.appendChild(colProducto);
                    li.appendChild(divRow);

                    li.addEventListener('click', function () {
                        inputElement.value = item.numeroSerie;
                        suggestions.innerHTML = '';
                        viewModalEgreso(item);
                    });

                    suggestions.appendChild(li);

                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions-egresos').innerHTML = '';
        hiddenBody.style.display = 'none';
    }
}

document.getElementById('month').addEventListener('change', function () {
    let selectedMonth = this.value;

    if (selectedMonth == "") {
        alert('Fecha no valida.');
    } else {
        let url = "/egresos/" + selectedMonth;

        window.location.href = url;
    }
});

document.getElementById('month').addEventListener('keydown', function (event) {
    // Evita que la acci贸n de borrado ocurra si se presiona Backspace o Delete
    if (event.key === 'Backspace' || event.key === 'Delete') {
        event.preventDefault();
    }
});

function viewModalEgreso(json) {
    let modalEgreso = new bootstrap.Modal(document.getElementById('detailEgresoModal'));
    let hiddenIdEgreso = document.getElementById('modal-egreso-id');
    let labelTitulo = document.getElementById('modal-egreso-titulo');
    let labelSerialNumber = document.getElementById('modal-egreso-serialnumber');
    let labelEstado = document.getElementById('modal-egreso-estado');
    let labelUsuario = document.getElementById('modal-egreso-usuario');
    let divFecha = document.getElementById('modal-egreso-fecha');
    let divPublicacion = document.getElementById('modal-egreso-publicidad');
    let textAreaObservacion = document.getElementById('modal-egreso-observacion');
    let btnDevolucionEgreso = document.getElementById('modal-egreso-btn-devolucion');

    labelSerialNumber.textContent = json.numeroSerie;
    labelEstado.textContent = json.estado;
    labelTitulo.textContent = json.nombreProducto;
    textAreaObservacion.value = json.observacion;
    labelUsuario.textContent = json.usuario;
    hiddenIdEgreso.value = json.idEgreso;

    let containerDevolucion = document.getElementById('container-campos-devolucion');
    let inputFechaDevolucion = document.getElementById('modal-egreso-fecha-devolucion');

    if (json.estado == 'DEVOLUCION' || json.estado == 'GARANTIA') {
        btnDevolucionEgreso.style.display = 'none';
        containerDevolucion.style.display = 'none';
        inputFechaDevolucion.required = false;
    } else {
        btnDevolucionEgreso.style.display = 'block';
        containerDevolucion.style.display = 'block';
        inputFechaDevolucion.required = true;
    }

    // Detectar si la observación contiene "Fallo de entrega" para marcar el checkbox
    if (json.observacion && json.observacion.includes("Fallo de entrega")) {
        document.getElementById('check-fallo-entrega-egreso').checked = true;
    } else {
        document.getElementById('check-fallo-entrega-egreso').checked = false;
    }

    inputFechaDevolucion.value = ''; // Limpiar fecha cada vez que se abre para venta activa

    if (json.estado == 'ENTREGADO') {
        divFecha.innerHTML = '<p class="mb-0"><small><strong>Fecha Compra:</strong> ' + stringDate(json.fechaCompra) + '</small></p>' +
            '<p class="mt-0 mb-0"><small><strong>Fecha Despacho:</strong> ' + stringDate(json.fechaDespacho) + '</small></p>';
    } else {
        divFecha.innerHTML = '<p class="mb-0"><small><strong>Fecha Devolución:</strong> ' + stringDate(json.fechaMovimiento) + '</small></p>';
    }

    // Poblar campos de edición y precio
    document.getElementById('modal-egreso-precio-text').textContent = json.precioVenta !== undefined ? parseFloat(json.precioVenta).toFixed(2) : '0.00';
    
    let editPrecioInput = document.getElementById('modal-egreso-edit-precio');
    if (editPrecioInput) {
        editPrecioInput.value = json.precioVenta !== undefined ? json.precioVenta : '';
    }

    document.getElementById('modal-egreso-edit-fecha-compra').value = json.fechaCompra ? json.fechaCompra.split('T')[0] : '';
    document.getElementById('modal-egreso-edit-fecha-despacho').value = json.fechaDespacho ? json.fechaDespacho.split('T')[0] : '';
    document.getElementById('modal-egreso-edit-sku').value = json.sku || '';
    document.getElementById('modal-egreso-edit-nro-orden').value = json.numeroOrden || '';

    // Resetear modo edición
    const containerFechas = document.getElementById('container-edit-fechas');
    const containerPublicacion = document.getElementById('container-edit-publicacion');
    const containerPrecio = document.getElementById('container-edit-precio');
    const divPrecioDisplay = document.getElementById('modal-egreso-precio-display');
    const btnEdit = document.getElementById('btn-edit-egreso');

    containerFechas.classList.add('d-none');
    containerPublicacion.classList.add('d-none');
    if (containerPrecio) containerPrecio.classList.add('d-none');
    divFecha.classList.remove('d-none');
    divPublicacion.classList.remove('d-none');
    if (divPrecioDisplay) divPrecioDisplay.classList.remove('d-none');
    if (btnEdit) {
        btnEdit.innerHTML = '<i class="bi bi-pencil-fill"></i>';
    }

    if (json.cuenta == null) {
        divPublicacion.innerHTML = '<label class="fw-bold">Publicacion:</label>' + '<p class="text-secondary mb-1">Sin publicación</p>';
    } else {
        divPublicacion.innerHTML = '<label class="fw-bold">Publicacion:</label>' +
            '<div class="row border rounded-3 ms-1 me-1 pt-2">' +
            '<div class="col-9">' +
            '<h6>' + json.cuenta + '</h6>' +
            '</div>' +
            '<div class="col-3">' +
            '<img src="' + json.imagenPublicacion + '" alt="imagen" class="w-100 rounded-3">' +
            '</div>' +
            '<div class="col-6">' +
            '<label class="text-secondary"><small>sku:</small></label>' +
            '<p class="mb-1 pt-0"><small>' + json.sku + '</small></p>' +
            '</div>' +
            '<div class="col-6 text-end">' +
            '<label class="text-secondary"><small>Nro de Orden:</small></label>' +
            '<p class="mb-1 pt-0"><small>' + json.numeroOrden + '</small></p>' +
            '</div>' +
            '</div>';
    }

    modalEgreso.show();
}

function formDetailEgreso(transaction) {
    let formEgreso = document.getElementById('form-detail-egreso');
    let hiddenTransaction = document.getElementById('modal-egreso-transaccion');

    if (transaction === 'devolucion' && !formEgreso.checkValidity()) {
        formEgreso.reportValidity();
        return;
    }

    hiddenTransaction.value = transaction;
    formEgreso.submit();
}

document.getElementById('check-fallo-entrega-egreso').addEventListener('change', function () {
    let textarea = document.getElementById('modal-egreso-observacion');
    if (this.checked) {
        textarea.value = (textarea.value.trim() === "") ? "Fallo de entrega" : textarea.value + " - Fallo de entrega";
    } else {
        textarea.value = textarea.value.replace(" - Fallo de entrega", "").replace("Fallo de entrega", "").trim();
    }
});

function stringDate(date) {
    let fecha = new Date(date);
    let day = (fecha.getUTCDate()).toString().padStart(2, '0');
    let month = (fecha.getUTCMonth() + 1).toString().padStart(2, '0');
    let year = fecha.getUTCFullYear();

    return `${day}/${month}/${year}`;
}

function toggleEditEgreso() {
    const containerFechas = document.getElementById('container-edit-fechas');
    const containerPublicacion = document.getElementById('container-edit-publicacion');
    const containerPrecio = document.getElementById('container-edit-precio');
    const divFecha = document.getElementById('modal-egreso-fecha');
    const divPublicacion = document.getElementById('modal-egreso-publicidad');
    const divPrecioDisplay = document.getElementById('modal-egreso-precio-display');
    const btnEdit = document.getElementById('btn-edit-egreso');

    if (containerFechas.classList.contains('d-none')) {
        containerFechas.classList.remove('d-none');
        containerPublicacion.classList.remove('d-none');
        if (containerPrecio) containerPrecio.classList.remove('d-none');
        divFecha.classList.add('d-none');
        divPublicacion.classList.add('d-none');
        if (divPrecioDisplay) divPrecioDisplay.classList.add('d-none');
        if (btnEdit) btnEdit.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
    } else {
        containerFechas.classList.add('d-none');
        containerPublicacion.classList.add('d-none');
        if (containerPrecio) containerPrecio.classList.add('d-none');
        divFecha.classList.remove('d-none');
        divPublicacion.classList.remove('d-none');
        if (divPrecioDisplay) divPrecioDisplay.classList.remove('d-none');
        if (btnEdit) btnEdit.innerHTML = '<i class="bi bi-pencil-fill"></i>';
    }
}

function searchPublicacion(inputElement) {
    let query = inputElement.value;
    let suggestions = document.getElementById('suggestions-sku');

    function handleClickOutsideSku(event) {
        if (!suggestions.contains(event.target) && event.target !== inputElement) {
            suggestions.innerHTML = '';
            document.removeEventListener('click', handleClickOutsideSku);
        }
    }

    document.addEventListener('click', handleClickOutsideSku);

    if (query.length > 2) {
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/searchpublicacion?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                suggestions.innerHTML = '';

                data.forEach(item => {
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'pe-0', 'hover-sistema-uno', 'text-truncate');
                    li.style.cursor = "pointer";
                    li.style.fontSize = "12px";

                    let divRow = document.createElement('div');
                    divRow.classList.add('row', 'w-100');

                    let colSku = document.createElement('div');
                    colSku.classList.add('col-md-12', 'fw-bold');
                    colSku.textContent = item.sku;

                    let colTitulo = document.createElement('div');
                    colTitulo.classList.add('col-md-12', 'text-secondary');
                    colTitulo.textContent = item.titulo;
                    colTitulo.style.fontSize = '10px';

                    divRow.appendChild(colSku);
                    divRow.appendChild(colTitulo);
                    li.appendChild(divRow);

                    li.addEventListener('click', function () {
                        inputElement.value = item.sku;
                        suggestions.innerHTML = '';
                    });

                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        suggestions.innerHTML = '';
    }
}

function filterEgresosByUser(idUser) {
    const rows = document.querySelectorAll('.row-egreso');
    rows.forEach(row => {
        if (idUser === 'all' || row.dataset.user === idUser) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
    });
}
