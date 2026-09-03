
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
    inputHiddenRegistro.dataset.idgrupo = object.idGrupo || 0; // Store idGrupo
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

    let divColModelo = createDiv(['col-12', 'col-md-3', 'mb-2'], null);
    divColModelo.innerHTML = '<strong>Modelo:</strong><br>' + object.modelo;

    let divColCodigo = createDiv(['col-12', 'col-md-2', 'mb-2'], null);
    divColCodigo.innerHTML = '<strong>Codigo:</strong><br>' + object.codigoProducto;

    let divColSerial = createDiv(['col-12', 'col-md-3', 'mb-2'], null);
    divColSerial.innerHTML = '<strong>SN:</strong><br>' + object.numeroSerie;

    let divColEstado = createDiv(['col-6', 'col-md-2', 'text-md-center', 'mb-2'], null);
    divColEstado.innerHTML = '<strong>Estado:</strong><br>' + object.estado;

    let divColPrecio = createDiv(['col-6', 'col-md-2', 'text-end', 'mb-2'], null);
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

    // ── Validación: Series de SERVICIO (RESETT / EN_USO) ──
    // Si la serie contiene "RESETT" y su estado es "EN_USO", es una serie de servicio.
    // Los servicios de reseteo cuestan máximo S/ 30. Si alguien pone un precio mayor,
    // probablemente está intentando egresar un producto normal con una serie de servicio.
    const esSerieServicio = (object.numeroSerie || '').toUpperCase().includes('RESETT') && (object.estado || '').toUpperCase() === 'EN_USO';

    if (esSerieServicio) {
        // Marcar visualmente como serie de servicio
        divColSerial.innerHTML += '<br><span class="badge bg-info text-dark"><i class="bi bi-gear-fill"></i> Serie de Servicio</span>';
        inputPrecio.dataset.serieServicio = '1';
        inputPrecio.dataset.serieNumero = object.numeroSerie;
    }

    inputPrecio.addEventListener('input', function () {
        if (esSerieServicio) {
            let precio = parseFloat(inputPrecio.value);
            if (!isNaN(precio) && precio > 30) {
                inputPrecio.classList.remove('border-success');
                inputPrecio.classList.add('border-danger');
                Swal.fire({
                    icon: 'error',
                    title: '¡Precio no permitido!',
                    html: `<b>${object.numeroSerie}</b> es una serie de <b>reseteo (servicio)</b>.<br>` +
                          `El costo máximo de un servicio es <b>S/ 30.00</b>.<br><br>` +
                          `<b>No se puede registrar el egreso con este precio.</b><br>` +
                          `Si necesitas egresar un producto normal, busca una serie con estado <b>NUEVO</b>.`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#dc3545'
                });
            } else {
                inputPrecio.classList.remove('border-danger');
                inputPrecio.classList.add('border-success');
            }
        }
        validateSubmit();
    });

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

    // Verificar stock bajo al agregar un producto al egreso
    checkStockBajo(object.idRegistroProducto, divRowItem);
}

function checkStockBajo(idRegistro, divRowItem) {
    fetch(`/egresos/check-stock?idRegistro=${idRegistro}`)
        .then(res => res.json())
        .then(data => {
            if (!data.alertaTipo) return;

            // Agregar badge visual en el item
            let badgeDiv = document.createElement('div');
            badgeDiv.classList.add('col-12', 'mt-1');

            if (data.alertaTipo === 'traer_almacen') {
                let almacenesTexto = data.stockOtrosAlmacenes.map(a => `<b>${a.almacen}</b> (${a.stock} uds)`).join(', ');
                badgeDiv.innerHTML = `<small class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i> <b>Stock bajo en ${data.almacenOrigen}</b>: quedarán ${data.stockDespuesEgreso} uds. Traer de: ${almacenesTexto}</small>`;

                Swal.fire({
                    icon: 'warning',
                    title: 'Stock bajo en ' + data.almacenOrigen,
                    html: `
                        <p>El producto <b>${data.producto}</b> quedará con <b>${data.stockDespuesEgreso}</b> unidad(es) en <b>${data.almacenOrigen}</b> después de este egreso.</p>
                        <p class="text-muted">(Stock mínimo: ${data.stockMin})</p>
                        <hr>
                        <p><b>Traer de:</b></p>
                        <ul class="list-unstyled">
                            ${data.stockOtrosAlmacenes.map(a => `<li><i class="bi bi-box-seam"></i> <b>${a.almacen}</b>: ${a.stock} unidad(es)</li>`).join('')}
                        </ul>
                    `,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f0ad4e'
                });
            } else if (data.alertaTipo === 'traer_proveedor') {
                badgeDiv.innerHTML = `<small class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> <b>Stock total bajo</b>: quedarán ${data.stockTotalDespues} uds en total. Stock proveedor: ${data.stockProveedor} uds</small>`;

                Swal.fire({
                    icon: 'error',
                    title: 'Stock total bajo',
                    html: `
                        <p>El producto <b>${data.producto}</b> quedará con solo <b>${data.stockTotalDespues}</b> unidad(es) en total después de este egreso.</p>
                        <p class="text-muted">(Stock mínimo: ${data.stockMin})</p>
                        <hr>
                        <p><b>Solicitar al proveedor:</b></p>
                        <p><i class="bi bi-box-seam"></i> Stock disponible en proveedor: <b>${data.stockProveedor}</b> unidad(es)</p>
                    `,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#d9534f'
                });
            }

            // Insertar el badge en el item del egreso
            let rowContent = divRowItem.querySelector('.row');
            if (rowContent) {
                rowContent.appendChild(badgeDiv);
            }
        })
        .catch(err => console.error('Error al verificar stock:', err));
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

    // ── Bloquear si hay serie de servicio con precio > 30 ──
    document.querySelectorAll('.input-precio-item[data-serie-servicio="1"]').forEach(function (input) {
        let precio = parseFloat(input.value);
        if (!isNaN(precio) && precio > 30) {
            validate = false;
        }
    });

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


document.getElementById('btn-confirmar-guardar')?.addEventListener('click', function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
    document.getElementById('form-egreso').submit();
});

// ═══════════════════════════════════════════════════════════════════
// COMPONENTES / UPGRADES (RAM, SSD, etc.)
// ═══════════════════════════════════════════════════════════════════

let componentesAgregados = [];
let componenteIndex = 0;

// Mostrar sección de componentes cuando se agrega al menos 1 producto (Laptops / AIO)
const observerComponentes = new MutationObserver(function () {
    let seccion = document.getElementById('seccion-componentes');
    let items = document.querySelectorAll('.body-form');
    let showComponents = false;
    
    items.forEach(function(item) {
        let idGrupo = parseInt(item.dataset.idgrupo || 0);
        // Grupos Laptops = 1, 2, 3. Grupo AIO = 10
        if ([1, 2, 3, 10].includes(idGrupo)) {
            showComponents = true;
        }
    });

    if (seccion) {
        seccion.style.display = showComponents ? 'flex' : 'none';
    }
});
observerComponentes.observe(document.getElementById('div-items-create-egreso'), { childList: true });

// Búsqueda de componentes
document.getElementById('input-buscar-componente')?.addEventListener('input', function () {
    let query = this.value;
    let suggestions = document.getElementById('suggestions-componente');

    if (query.length < 2) {
        suggestions.innerHTML = '';
        return;
    }

    let xhr = new XMLHttpRequest();
    xhr.open('GET', `/egresos/search-producto-ajax?query=${encodeURIComponent(query)}&tipo=componente`, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            let data = JSON.parse(xhr.responseText);
            suggestions.innerHTML = '';

            data.forEach(item => {
                let li = document.createElement('li');
                li.classList.add('list-group-item', 'hover-sistema-uno', 'text-truncate');
                li.style.cursor = 'pointer';
                li.innerHTML = `<strong>${item.nombreProducto}</strong> <small class="text-muted">${item.modelo || ''}</small>`;

                li.addEventListener('click', function () {
                    document.getElementById('input-buscar-componente').value = item.nombreProducto;
                    let hiddenIdProducto = document.getElementById('hidden-componente-idProducto');
                    hiddenIdProducto.value = item.idProducto;
                    hiddenIdProducto.dataset.modelo = item.modelo || '';
                    suggestions.innerHTML = '';

                    // Cargar series disponibles
                    cargarSeriesComponente(item.idProducto);
                });

                suggestions.appendChild(li);
            });
        }
    };
    xhr.send();
});

// Click fuera cierra las sugerencias de componentes
document.addEventListener('click', function (e) {
    let input = document.getElementById('input-buscar-componente');
    let suggestions = document.getElementById('suggestions-componente');
    if (input && suggestions && !input.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.innerHTML = '';
    }
});

function cargarSeriesComponente(idProducto) {
    let selectSerie = document.getElementById('select-serie-componente');
    let inputCosto = document.getElementById('input-costo-componente');
    selectSerie.innerHTML = '<option value="">Cargando...</option>';
    selectSerie.disabled = true;
    inputCosto.value = '';

    let xhr = new XMLHttpRequest();
    xhr.open('GET', `/egresos/series-disponibles?idProducto=${idProducto}`, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            let data = JSON.parse(xhr.responseText);
            selectSerie.innerHTML = '<option value="">Selecciona una serie</option>';

            if (data.length === 0) {
                selectSerie.innerHTML = '<option value="">Sin stock disponible</option>';
                return;
            }

            data.forEach(serie => {
                // No mostrar las que ya fueron agregadas como componente
                if (componentesAgregados.some(c => c.idRegistro == serie.idRegistro)) return;

                let opt = document.createElement('option');
                opt.value = serie.idRegistro;
                opt.textContent = `${serie.numeroSerie} (${serie.almacen})`;
                opt.dataset.serie = serie.numeroSerie;
                selectSerie.appendChild(opt);
            });

            selectSerie.disabled = false;
        }
    };
    xhr.send();
}

// Al seleccionar una serie, cargar su costo
document.getElementById('select-serie-componente')?.addEventListener('change', function () {
    let idRegistro = this.value;
    let inputCosto = document.getElementById('input-costo-componente');

    if (!idRegistro) {
        inputCosto.value = '';
        return;
    }

    let xhr = new XMLHttpRequest();
    xhr.open('GET', `/egresos/costo-registro?idRegistro=${idRegistro}`, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            let data = JSON.parse(xhr.responseText);
            inputCosto.value = data.costo ? parseFloat(data.costo).toFixed(2) : '0.00';
        }
    };
    xhr.send();
});

// Añadir componente
document.getElementById('btn-add-componente')?.addEventListener('click', function () {
    let selectSerie = document.getElementById('select-serie-componente');
    let inputCosto = document.getElementById('input-costo-componente');
    let inputBuscar = document.getElementById('input-buscar-componente');
    let hiddenIdProducto = document.getElementById('hidden-componente-idProducto');
    let idProducto = hiddenIdProducto.value;
    let modeloComponente = hiddenIdProducto.dataset.modelo || '';

    let idRegistro = selectSerie.value;
    if (!idRegistro) {
        alertBootstrap('Selecciona una serie de componente', 'warning');
        return;
    }

    let serieName = selectSerie.options[selectSerie.selectedIndex].dataset.serie || selectSerie.options[selectSerie.selectedIndex].text;
    let costo = parseFloat(inputCosto.value) || 0;
    let nombreComponente = inputBuscar.value;

    // Verificar duplicado
    if (componentesAgregados.some(c => c.idRegistro == idRegistro)) {
        alertBootstrap('Este componente ya fue agregado', 'warning');
        return;
    }

    let comp = {
        id: componenteIndex++,
        idRegistro: idRegistro,
        idProducto: idProducto,
        nombre: nombreComponente,
        serie: serieName,
        costo: costo,
        modelo: modeloComponente
    };

    componentesAgregados.push(comp);
    renderComponentes();

    // Limpiar campos
    inputBuscar.value = '';
    selectSerie.innerHTML = '<option value="">Primero selecciona un componente</option>';
    selectSerie.disabled = true;
    inputCosto.value = '';
    document.getElementById('hidden-componente-idProducto').value = '';
});

function renderComponentes() {
    let tbody = document.getElementById('tbody-componentes');
    let tabla = document.getElementById('tabla-componentes');
    let contenedorHidden = document.getElementById('hidden-componentes-container');

    tbody.innerHTML = '';
    contenedorHidden.innerHTML = '';

    let totalCosto = 0;

    if (componentesAgregados.length > 0) {
        tabla.style.display = 'table';
    } else {
        tabla.style.display = 'none';
    }

    // Calculate the starting index for componentes (after the main items)
    let mainItemCount = document.querySelectorAll('.body-form').length;

    componentesAgregados.forEach((comp, index) => {
        totalCosto += comp.costo;
        let itemIdx = mainItemCount + index;

        // Fila visual
        let tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3">
                <span class="fw-bold">${comp.nombre}</span>
                ${comp.modelo ? `<br><small class="text-muted" style="font-size:11px;">Mod: ${comp.modelo}</small>` : ''}
            </td>
            <td><small class="text-muted">${comp.serie}</small></td>
            <td class="text-end">
                <span class="text-danger fw-bold">S/ ${comp.costo.toFixed(2)}</span>
            </td>
            <td class="text-center pe-3">
                <button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeComponente(${comp.id})">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);

        // Hidden inputs — el componente se envía como un item más con precio 0 y su costo
        contenedorHidden.innerHTML += `
            <input type="hidden" name="items[${itemIdx}][idregistro]" value="${comp.idRegistro}">
            <input type="hidden" name="items[${itemIdx}][idpublicacion]" value="NULO">
            <input type="hidden" name="items[${itemIdx}][precioVenta]" value="0.01">
            <input type="hidden" name="items[${itemIdx}][costo]" value="${comp.costo}">
        `;
    });

    document.getElementById('total-componentes-text').innerText = totalCosto.toFixed(2);
}

window.removeComponente = function (id) {
    componentesAgregados = componentesAgregados.filter(c => c.id !== id);
    renderComponentes();
};
