document.addEventListener('keydown', function (event) {

    if (event.key === 'Enter') {
        const activeElement = document.activeElement;
        const isInput = activeElement.tagName == 'INPUT';
        const isSelect = activeElement.tagName == 'SELECT';

        if (isInput || isSelect) {
            event.preventDefault();
        }
    }
});

function calcPrices() {
    let price = document.getElementById('precio-product').value;
    let type = document.getElementById('select-tipoprecio').value;
    let idGrupo = document.getElementById('grupo-product').value;
    let state = document.getElementById('estado-product').value;
    let ganancia = document.getElementById('precio-product-ganancia').value;

    if (price > -1) {
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/producto/calculate?price=${encodeURIComponent(price)}&type=${encodeURIComponent(type)}&idGrupo=${encodeURIComponent(idGrupo)}&state=${encodeURIComponent(state)}&ganancia=${encodeURIComponent(ganancia)}`, true);

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    let data = JSON.parse(xhr.responseText);
                    let precioCalculado = document.getElementById('precio-product-calculado');
                    let divTotal = document.getElementById('div-total-price');
                    let gananciaVal = parseFloat(ganancia) || 0;
                    
                    let calculadoUsd = data[0].calculado;
                    if (type === 'SOL') {
                        calculadoUsd = calculadoUsd / window.APP_DATA.tc;
                    }

                    window.APP_DATA.lastCalculado = calculadoUsd;
                    precioCalculado.value = (calculadoUsd + gananciaVal).toFixed(2);
                    divTotal.innerHTML = '';


                    data[1].total.forEach(function (x) {
                        let divPrecio = document.createElement('div');
                        divPrecio.classList.add('col-lg-4', 'col-md-8');

                        let labelEmpresa = document.createElement('label');
                        labelEmpresa.classList.add('form-label');
                        labelEmpresa.textContent = x.empresa;

                        let labelPrecio = document.createElement('label');
                        labelPrecio.classList.add('form-label');
                        labelPrecio.textContent = 'Precio Total:';

                        let inputPrecio = document.createElement('input');
                        inputPrecio.type = 'number';
                        inputPrecio.disabled = true;
                        inputPrecio.step = '0.01';
                        inputPrecio.classList.add('form-control');
                        inputPrecio.classList.add('price-product');
                        inputPrecio.value = x.precio.toFixed(2);

                        divPrecio.appendChild(labelEmpresa);
                        divPrecio.appendChild(labelPrecio);
                        divPrecio.appendChild(inputPrecio);
                        divTotal.appendChild(divPrecio);
                    });

                    document.dispatchEvent(new CustomEvent('calcPricesCompleted'));

                } else {
                    console.error('Error en la solicitud:', xhr.statusText);
                }
            }
        };

        xhr.send();
    } else {
        document.getElementById('precio-product-igv').value = 0.00; // Limpiar si no hay entrada
    }
}

function removeIgv() {
    let price = this.value;
    let dolarSinIgv = document.getElementById('precio-product');

    if (price > 0) {


        dolarSinIgv.value = (price / 1.18).toFixed(2);
    } else {
        dolarSinIgv.value = 0.00;
    }
}

function calcIgv() {
    let price = this.value;
    let dolarSinIgv = document.getElementById('precio-product-igv');

    if (price > 0) {

        dolarSinIgv.value = (price * 1.18).toFixed(2);
    } else {
        dolarSinIgv.value = 0.00;
    }
}


function changeTC() {
    let selectPrice = document.getElementById('select-tipoprecio').value;
    let priceProduct = document.querySelectorAll('.price-product:not(#precio-product-ganancia):not(#precio-product-calculado):not(#precio-total-fijo)');

    priceProduct.forEach(function (x) {
        if (selectPrice == 'SOL') {
            x.value = (x.value * window.APP_DATA.tc).toFixed(2);
        } else {
            x.value = (x.value / window.APP_DATA.tc).toFixed(2);
        }
    });


}

document.addEventListener('DOMContentLoaded', calcPrices);

document.getElementById('precio-product-igv').addEventListener('input', removeIgv);
document.getElementById('precio-product-igv').addEventListener('input', calcPrices);

document.getElementById('precio-product').addEventListener('input', calcIgv);
document.getElementById('precio-product').addEventListener('input', calcPrices);

document.getElementById('precio-product-ganancia').addEventListener('input', calcPrices);

document.getElementById('select-tipoprecio').addEventListener('change', calcPrices);
document.getElementById('estado-product').addEventListener('change', calcPrices);
document.getElementById('grupo-product').addEventListener('change', calcPrices);

// Cache de códigos
const codigos = window.APP_DATA.codigos;

function getCategoryPrefix(name) {
    if (!name) return 'UNK';
    let normalized = name.normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-zA-Z0-9]/g, "")
        .toUpperCase();
    if (normalized.length < 3) {
        normalized = (normalized + 'XXX').substring(0, 3);
    } else {
        normalized = normalized.substring(0, 3);
    }
    return normalized;
}

function getGroupPrefix(name) {
    if (!name) return 'UNK';
    let normalized = name.normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-zA-Z0-9]/g, "")
        .toUpperCase();
    if (normalized.length < 3) {
        normalized = (normalized + 'XXX').substring(0, 3);
    } else {
        normalized = normalized.substring(0, 3);
    }
    return normalized;
}

function actualizarGrupoCodigoPreview() {
    const selectGrup = document.getElementById('grupo-product');
    const containerWrapper = document.getElementById('container-codigo-wrapper');
    const inputCod = document.getElementById('codigo-product');
    const helpDiv = document.getElementById('codigo-product-help');

    if (!selectGrup || !selectGrup.value) {
        if (containerWrapper) containerWrapper.classList.add('d-none');
        if (inputCod) inputCod.value = 'ERROR';
        if (typeof disableButton === 'function') disableButton();
        return;
    }

    const grupo = selectGrup.value.trim();
    const selectedOption = selectGrup.options[selectGrup.selectedIndex];
    const categoryName = selectedOption ? (selectedOption.getAttribute('data-categoria') || '') : '';
    const groupName = selectedOption ? (selectedOption.textContent || '') : '';

    let find = false;
    let cod = '';

    for (const [codigoProducto, idGrupo] of Object.entries(codigos)) {
        if (idGrupo == grupo) {
            cod = codigoProducto;
            find = true;
            break;
        }
    }

    if (find) {
        // El grupo ya tiene productos. Ocultamos el campo editable para evitar confusiones
        // ya que el backend usará el correlativo secuencial autoincremental de forma automática.
        if (containerWrapper) containerWrapper.classList.add('d-none');

        if (inputCod) {
            inputCod.maxLength = 10;
            if (!inputCod.dataset.lastGroup || inputCod.dataset.lastGroup !== grupo) {
                inputCod.value = cod;
                inputCod.dataset.lastGroup = grupo;
            }
        }
    } else {
        // Nuevo grupo sin productos. Se muestra el campo editable para definir el prefijo inicial de 6 caracteres.
        if (containerWrapper) containerWrapper.classList.remove('d-none');

        const catPrefix = getCategoryPrefix(categoryName);
        const groupPrefix = getGroupPrefix(groupName);
        const baseCod = catPrefix + groupPrefix;

        if (inputCod) {
            inputCod.maxLength = 6;
            if (!inputCod.dataset.lastGroup || inputCod.dataset.lastGroup !== grupo) {
                inputCod.value = baseCod;
                inputCod.dataset.lastGroup = grupo;
            }
        }

        const currentVal = inputCod ? inputCod.value : baseCod;
        const expectedFirstCod = currentVal.substring(0, 6) + '0001';

        if (helpDiv) {
            helpDiv.innerHTML = `
         <div class="alert alert-warning d-flex align-items-center mb-0 py-2 border-0 fs-7" style="background-color: rgba(255, 193, 7, 0.15); color: #856404; border-radius: 8px;">
         <div>
             <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
             <strong>¡Aviso!</strong> Este grupo es nuevo. Se creará el código de producto base como <strong>${currentVal}</strong> (el primer producto se registrará automáticamente como <strong class="text-decoration-underline text-uppercase">${expectedFirstCod}</strong>). Puedes editar estas 6 letras si lo deseas.
         </div>
         </div>
         `;
        }
    }

    if (typeof disableButton === 'function') {
        disableButton();
    }
}

function manejarSubmit(e) {
    const eventObj = e || window.event;
    if (!validarCodigos()) {
        if (eventObj) eventObj.preventDefault();
        return false;
    }
    return true;
}

function validarCodigos() {
    const selectGrup = document.getElementById('grupo-product');
    if (!selectGrup || !selectGrup.value) return false;

    const inputCod = document.getElementById('codigo-product');
    if (!inputCod) return false;

    const grupo = selectGrup.value.trim();
    let find = false;
    let cod = '';

    for (const [codigoProducto, idGrupo] of Object.entries(codigos)) {
        if (idGrupo == grupo) {
            cod = codigoProducto;
            find = true;
            break;
        }
    }

    if (find) {
        // Grupo con productos existentes: ya tiene el código completo de 10 caracteres
        const codeValue = inputCod.value.trim();
        const codeRegex = /^[A-Z0-9]{10}$/;
        return codeRegex.test(codeValue);
    } else {
        // Primer producto del grupo: el usuario ingresa exactamente 6 caracteres
        const codeValue = inputCod.value.trim();
        const codeRegex = /^[A-Z0-9]{6}$/;
        if (!codeRegex.test(codeValue)) {
            Swal.fire({
                title: 'Código Inválido',
                text: 'El código de producto debe tener exactamente 6 caracteres alfanuméricos (letras y números). Ej: LAPGAM',
                icon: 'warning',
                confirmButtonText: 'Aceptar',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            return false;
        }

        return true;
    }
}

const dropAreas = document.querySelectorAll('.img-div');

dropAreas.forEach(function (dropArea) {
    let input = dropArea.querySelector('.img-input');
    let img = dropArea.querySelector('.img-preview');

    img.addEventListener('click', function () {
        input.click();
    });

    dataImage(input, dropArea, img);
    input.addEventListener('change', function (event) {
        changeImage(event, input, img);
    });
});

function dataImage(input, dropArea, img) {
    // Prevenir el comportamiento por defecto
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Resaltar el área de arrastre
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    // Manejar el drop
    dropArea.addEventListener('drop', (e) => handleDrop(e, input, img), false);
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight() {
    this.classList.add('hover');
}

function unhighlight() {
    this.classList.remove('hover');
}

function handleDrop(event, input, img) {
    const dt = event.dataTransfer;
    const files = dt.files;

    if (files.length) {
        const newDt = new DataTransfer();
        newDt.items.add(files[0]);
        input.files = newDt.files; // Asignar los archivos al input usando un DataTransfer persistente
        changeImage({ target: { files: newDt.files } }, input, img);
    }
}

function changeImage(event, input, img) {
    const file = event.target.files ? event.target.files[0] : null;

    let imgElement = img;
    if (typeof img === 'string') {
        let triggerId = arguments[3];
        if (triggerId) {
            imgElement = document.getElementById(triggerId);
        } else {
            imgElement = document.getElementById(img);
        }
    }

    if (file) {

        const reader = new FileReader();

        reader.onload = function (e) {
            const image = new Image();
            image.src = e.target.result;

            image.onload = function () {
                const maxWidth = 1000; // Ancho máximo permitido
                const maxHeight = 1000; // Alto máximo permitido

                if (image.width !== maxWidth || image.height !== maxHeight) {
                    alert('La imagen no coincide con las dimensiones permitidas ' + maxWidth + ' x ' + maxHeight + ' píxeles.');
                    input.value = ''; // Limpiar el input si no coincide
                    if (typeof disableButton === 'function') disableButton();
                    return;
                }

                // Si la imagen cumple con las dimensiones, actualiza la vista previa
                if (imgElement) imgElement.src = e.target.result;
                if (typeof disableButton === 'function') disableButton();
            }

            image.onerror = function () {
                alert('El archivo seleccionado no es una imagen válida.');
                input.value = '';
                if (typeof disableButton === 'function') disableButton();
            }
        };

        reader.readAsDataURL(file);
    } else {
        if (typeof disableButton === 'function') disableButton();
    }
}

function validateForm() {
    let isValid = true;

    const name = document.getElementById('name-product').value.trim();
    const precio = document.getElementById('precio-product').value.trim();
    const ganancia = document.getElementById('precio-product-ganancia').value.trim();
    const upc = document.getElementById('upc-product').value.trim();
    const modelo = document.getElementById('modelo-product').value.trim();
    const partnumber = document.getElementById('partnumber-product').value.trim();
    const stock = document.querySelectorAll('.stock-product');
    const stockproveedor = document.getElementById('stockproveedor-product').value.trim();
    const descripcion = document.getElementById('descripcion-product').value.trim();
    const grupo = document.getElementById('grupo-product').value.trim();
    const marca = document.getElementById('marca-product').value.trim();
    const estado = document.getElementById('estado-product').value.trim();
    const garantia = document.getElementById('garantia-product').value.trim();
    const proveedor = document.getElementById('proveedor-product').value.trim();
    const imgone = document.getElementById('imgone-product').files;
    const imgtwo = document.getElementById('imgtwo-product').files.length;
    const imgtree = document.getElementById('imgtree-product').files.length;
    const imgfour = document.getElementById('imgfour-product').files.length;

    const lblgrupo = document.getElementById('grupo-label');
    const lblmarca = document.getElementById('marca-label');
    const lblestado = document.getElementById('estado-label');
    const lblgarantia = document.getElementById('garantia-label');
    const lblproveedor = document.getElementById('proveedor-label');

    if (name == '') {
        isValid = false;
    }

    if (ganancia == '') {
        isValid = false;
    }

    if (precio == '') {
        isValid = false;
    }

    if (upc == '') {
        isValid = false;
    } else if (upc.length > 13) {
        isValid = false;
        upcError.textContent = 'El UPC/EAN no acepta más de 13 digitos';
    } else {
        upcError.textContent = '';
    }

    if (modelo == '') {
        isValid = false;
    }

    if (partnumber == '') {
        isValid = false;
    }

    stock.forEach(function (x) {
        if (x.value == '') {
            isValid = false;
        }
    });

    if (stockproveedor == '') {
        isValid = false;
    }
    if (descripcion == '') {
        isValid = false;
    }

    if (grupo == '') {
        isValid = false;
        lblgrupo.classList.add('text-danger');
    } else {
        lblgrupo.classList.remove('text-danger');

        const inputCod = document.getElementById('codigo-product');
        if (inputCod) {
            const codeVal = inputCod.value.trim();

            // Verificar si el grupo ya tiene productos en base a los códigos existentes
            let findGroup = false;
            for (const [codigoProducto, idGrupo] of Object.entries(codigos)) {
                if (idGrupo == grupo) {
                    findGroup = true;
                    break;
                }
            }

            if (findGroup) {
                const codeRegex = /^[A-Z0-9]{10}$/;
                if (!codeRegex.test(codeVal)) {
                    isValid = false;
                }
            } else {
                const codeRegex = /^[A-Z0-9]{6}$/;
                if (!codeRegex.test(codeVal)) {
                    isValid = false;
                }
            }
        } else {
            isValid = false;
        }
    }

    if (marca == '') {
        isValid = false;
        lblmarca.classList.add('text-danger');
    } else {
        lblmarca.classList.remove('text-danger');
    }

    if (estado == '') {
        isValid = false;
        lblestado.classList.add('text-danger');
    } else {
        lblestado.classList.remove('text-danger');
    }

    if (garantia == '') {
        isValid = false;
        lblgarantia.classList.add('text-danger');
    } else {
        lblgarantia.classList.remove('text-danger');
    }

    if (proveedor == '') {
        isValid = false;
        lblproveedor.classList.add('text-danger');
    } else {
        lblproveedor.classList.remove('text-danger');
    }

    if (imgone == 0) {
        isValid = false;
    }

    if (imgtwo == 0) {
        isValid = false;
    }

    if (imgtree == 0) {
        isValid = false;
    }

    if (imgfour == 0) {
        isValid = false;
    }

    return isValid;
}

function disableButton() {
    let btnRegistrar = document.getElementById('btnRegistrar');
    if (validateForm()) {
        btnRegistrar.classList.remove('disabled');
    } else {
        btnRegistrar.classList.add('disabled');
    }
}

function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
    validateForm
}

function checkException(check, id) {
    let inputText = document.getElementById(id);

    if (check.checked) {
        inputText.value = 0;
        inputText.readOnly = true;
    } else {
        inputText.value = "";
        inputText.readOnly = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Ejecutar disableButton() al cargar la página para establecer el estado inicial del botón
    disableButton();

    // Calcular IGV inicial si hay precio
    let priceInput = document.getElementById('precio-product');
    if (priceInput && priceInput.value > 0) {
        let igvInput = document.getElementById('precio-product-igv');
        if (igvInput) {
            igvInput.value = (priceInput.value * 1.18).toFixed(2);
        }
    }

    // Agregar listeners a los campos para validar al cambiar
    document.getElementById('name-product').addEventListener('input', disableButton);
    document.getElementById('precio-product').addEventListener('input', disableButton);
    document.getElementById('upc-product').addEventListener('input', disableButton);
    document.getElementById('modelo-product').addEventListener('input', disableButton);
    document.getElementById('partnumber-product').addEventListener('input', disableButton);
    document.getElementById('stockproveedor-product').addEventListener('input', disableButton);
    document.getElementById('descripcion-product').addEventListener('input', disableButton);
    const selectGrup = document.getElementById('grupo-product');
    if (selectGrup) {
        selectGrup.addEventListener('input', disableButton);
        selectGrup.addEventListener('change', actualizarGrupoCodigoPreview);
    }
    const inputCod = document.getElementById('codigo-product');
    if (inputCod) {
        inputCod.addEventListener('input', function () {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            actualizarGrupoCodigoPreview();
            disableButton();
        });
    }
    const formCreate = document.getElementById('form-create');
    if (formCreate) {
        formCreate.addEventListener('submit', function (event) {
            if (!validarCodigos()) {
                event.preventDefault();
            }
        });
    }

    actualizarGrupoCodigoPreview();

    document.getElementById('marca-product').addEventListener('input', disableButton);
    document.getElementById('estado-product').addEventListener('input', disableButton);
    document.getElementById('garantia-product').addEventListener('input', disableButton);
    document.getElementById('proveedor-product').addEventListener('input', disableButton);
    document.getElementById('imgone-product').addEventListener('input', disableButton);
    document.getElementById('imgtwo-product').addEventListener('input', disableButton);
    document.getElementById('imgtree-product').addEventListener('input', disableButton);
    document.getElementById('imgfour-product').addEventListener('input', disableButton);
    document.querySelectorAll('.stock-product').forEach(function (x) {
        x.addEventListener('input', disableButton);
    });

    var textarea = document.getElementById('descripcion-product');
    if (textarea) {
        autoResize(textarea);
    }

});

// 👇 NUEVO BLOQUE PARA EL TIPO DE CAMBIO 👇
const TC_SUNAT = parseFloat(window.APP_DATA.tc); // Tasa de cambio SUNAT
const TC_FIJO = parseFloat(window.APP_DATA.tasaFija); // Tasa de cambio fija interna

function getPrecioEnDolares() {
    const divTotal = document.getElementById('div-total-price');
    const selectTipoPrecio = document.getElementById('select-tipoprecio');
    if (!divTotal || divTotal.children.length === 0) return null;

    const primerPrecioTotal = divTotal.querySelector('input[type="number"]');
    if (!primerPrecioTotal || !primerPrecioTotal.value) return null;

    let precioEnDolares = parseFloat(primerPrecioTotal.value);
    const moneda = selectTipoPrecio ? selectTipoPrecio.value : 'DOLAR';

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

    let tasaFijaUsar = TC_FIJO;
    if (tcFijoPersonalizado && tcFijoPersonalizado.value && parseFloat(tcFijoPersonalizado.value) > 0) {
        tasaFijaUsar = parseFloat(tcFijoPersonalizado.value);
    }

    if (precioSunatInput) {
        precioSunatInput.value = (precioEnDolares * TC_SUNAT).toFixed(2);
    }

    if (precioFijoInput) {
        precioFijoInput.value = (precioEnDolares * tasaFijaUsar).toFixed(2);
    }

    // La lógica de "Precio Web Directo" fue eliminada según requerimiento
}

document.addEventListener('DOMContentLoaded', function () {
    const handlePrecioTotalBlur = function() {
        let precioWeb = parseFloat(this.value) || 0;
        let tcUsar = TC_SUNAT;
        if (this.id === 'precio-total-fijo') {
            tcUsar = TC_FIJO;
            const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');
            if (tcFijoPersonalizado && tcFijoPersonalizado.value && parseFloat(tcFijoPersonalizado.value) > 0) {
                tcUsar = parseFloat(tcFijoPersonalizado.value);
            }
        }
        
        let precioVentaUsd = precioWeb / tcUsar;
        let costoBase = window.APP_DATA.lastCalculado || 0;
        let ganancia = precioVentaUsd - costoBase;
        
        document.getElementById('precio-product-ganancia').value = ganancia.toFixed(2);
        calcPrices();
    };

    const precioSunat = document.getElementById('precio-total-sunat');
    if (precioSunat) precioSunat.addEventListener('blur', handlePrecioTotalBlur);

    const precioFijo = document.getElementById('precio-total-fijo');
    if (precioFijo) precioFijo.addEventListener('blur', handlePrecioTotalBlur);
});

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
        if (labelSunat) labelSunat.innerHTML = 'Precio Total en Soles (TC SUNAT: ' + window.APP_DATA.tc + ') <span class="badge bg-success">En uso</span>';
        if (labelFijo) labelFijo.innerHTML = `Precio Total en Soles / Tasa Fija (${tasaFijaUsar}) <span class="badge bg-secondary">Referencia</span>`;
        if (precioSunatInput) {
            precioSunatInput.classList.add('border-success');
            precioSunatInput.classList.remove('border-warning');
        }
        if (precioFijoInput) {
            precioFijoInput.classList.remove('border-warning');
        }
    } else {
        if (labelSunat) labelSunat.innerHTML = 'Precio Total en Soles (TC SUNAT: ' + window.APP_DATA.tc + ') <span class="badge bg-secondary">Referencia</span>';
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

document.addEventListener('calcPricesCompleted', function () {
    actualizarPrecioTotalFijo();
});

const usarTcFijo = document.getElementById('usar_tc_fijo');
const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');

if (usarTcFijo) {
    usarTcFijo.addEventListener('change', toggleTipoCambio);
    toggleTipoCambio();
}

if (tcFijoPersonalizado) {
    tcFijoPersonalizado.addEventListener('input', toggleTipoCambio);
}

const quickImgInput = document.getElementById('quick-img');
const quickImgContainer = document.getElementById('quick-img-drag-container');
const quickImgPreview = document.getElementById('quick-img-preview');
const quickImgIcon = document.getElementById('quick-img-icon');
const quickImgLabel = document.getElementById('quick-img-label');

if (quickImgContainer && quickImgInput) {
    // Resaltar área al arrastrar
    ['dragenter', 'dragover'].forEach(eventName => {
        quickImgContainer.addEventListener(eventName, (e) => {
            e.preventDefault();
            quickImgContainer.classList.add('border-primary', 'bg-light-primary');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        quickImgContainer.addEventListener(eventName, (e) => {
            e.preventDefault();
            quickImgContainer.classList.remove('border-primary', 'bg-light-primary');
        }, false);
    });

    quickImgContainer.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length) {
            quickImgInput.files = files;
            handleQuickImgSelect(files[0]);
        }
    }, false);

    quickImgInput.addEventListener('change', (e) => {
        if (quickImgInput.files.length) {
            handleQuickImgSelect(quickImgInput.files[0]);
        }
    });
}

function handleQuickImgSelect(file) {
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            if (quickImgPreview) {
                quickImgPreview.src = e.target.result;
                quickImgPreview.classList.remove('d-none');
            }
            if (quickImgIcon) quickImgIcon.classList.add('d-none');
            if (quickImgLabel) quickImgLabel.textContent = file.name;
        };
        reader.readAsDataURL(file);
    }
}

// AJAX Form Submit para el Modal de Grupo
const formQuickGrupo = document.getElementById('form-quick-grupo');
if (formQuickGrupo) {
    formQuickGrupo.addEventListener('submit', function (e) {
        e.preventDefault();

        const btnSave = document.getElementById('btn-save-quick-grupo');
        const spinner = document.getElementById('quick-grupo-spinner');
        const btnText = document.getElementById('quick-grupo-btn-text');
        const alertEl = document.getElementById('quick-grupo-alert');

        if (alertEl) {
            alertEl.classList.add('d-none');
            alertEl.textContent = '';
        }

        if (btnSave) btnSave.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (btnText) btnText.innerHTML = 'Guardando...';

        const formData = new FormData(formQuickGrupo);

        fetch('/producto/creargrupo-rapido', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (btnSave) btnSave.disabled = false;
                if (spinner) spinner.classList.add('d-none');
                if (btnText) btnText.innerHTML = 'Guardar Grupo <i class="bi bi-check2-all"></i>';

                if (data.success) {
                    const selectGrup = document.getElementById('grupo-product');
                    if (selectGrup) {
                        const opt = document.createElement('option');
                        opt.value = data.grupo.idGrupoProducto;
                        opt.textContent = data.grupo.nombreGrupo;
                        opt.setAttribute('data-categoria', data.grupo.nombreCategoria);
                        selectGrup.appendChild(opt);
                        selectGrup.value = data.grupo.idGrupoProducto;

                        actualizarGrupoCodigoPreview();
                    }

                    const modalEl = document.getElementById('quickGrupoModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();

                    formQuickGrupo.reset();
                    if (quickImgPreview) {
                        quickImgPreview.src = '';
                        quickImgPreview.classList.add('d-none');
                    }
                    if (quickImgIcon) quickImgIcon.classList.remove('d-none');
                    if (quickImgLabel) quickImgLabel.textContent = 'Arrastra o haz clic para subir imagen';

                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'El grupo se ha creado correctamente y se ha seleccionado automáticamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Ocurrió un error inesperado.');
                }
            })
            .catch(error => {
                if (btnSave) btnSave.disabled = false;
                if (spinner) spinner.classList.add('d-none');
                if (btnText) btnText.innerHTML = 'Guardar Grupo <i class="bi bi-check2-all"></i>';

                const errMsg = error.message || 'Error al procesar la solicitud.';
                if (alertEl) {
                    alertEl.textContent = errMsg;
                    alertEl.classList.remove('d-none');
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: errMsg,
                        icon: 'error',
                        confirmButtonText: 'Cerrar'
                    });
                }
            });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const categoriaSelect = document.getElementById('categoria-product');
    const grupoSelect = document.getElementById('grupo-product');
    if (!categoriaSelect || !grupoSelect) return;

    const originalGrupoOptions = Array.from(grupoSelect.options);

    function filterGrupos() {
        const selectedCategoria = categoriaSelect.value;
        const currentSelectedGrupo = grupoSelect.value;

        // Limpiar opciones actuales
        grupoSelect.innerHTML = '';

        // Filtrar y agregar
        originalGrupoOptions.forEach(option => {
            const optionCategoria = option.getAttribute('data-categoria');
            if (selectedCategoria === "" || option.value === "" || optionCategoria === selectedCategoria) {
                grupoSelect.appendChild(option);
            }
        });

        // Mantener la selección si aún es válida, sino, seleccionar el primero
        if (Array.from(grupoSelect.options).some(opt => opt.value === currentSelectedGrupo)) {
            grupoSelect.value = currentSelectedGrupo;
        } else if (grupoSelect.options.length > 0) {
            grupoSelect.selectedIndex = 0;
        }
    }

    categoriaSelect.addEventListener('change', filterGrupos);

    // Autoseleccionar la categoría si hay un grupo ya seleccionado
    const selectedGrupoOpt = originalGrupoOptions.find(opt => opt.selected && opt.value !== "");
    if (selectedGrupoOpt) {
        const cat = selectedGrupoOpt.getAttribute('data-categoria');
        if (cat) {
            categoriaSelect.value = cat;
        }
    }

    // Disparar el filtro inicialmente
    filterGrupos();
});