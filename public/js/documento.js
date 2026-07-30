var inputsModalProduct = document.querySelectorAll('.input-modal-product');

var index = 1;
var totalComprobante = 0;
var idIndexProduct = null;
var importIdProducto = 0;

function sendIdToDelete(id) {
    let inputDelete = document.getElementById('input-delete-ingreso');
    inputDelete.value = id;
}

function validateDisabledRegistro() {
    let btnModalProduct = document.getElementById('btnIngreso');
    let disabledBtn = false;

    inputsModalProduct.forEach(function (x) {
        if (x.value == '') {
            disabledBtn = true;
        }
    });

    btnModalProduct.disabled = disabledBtn;
}

function searchProduct(inputElement) {
    let query = inputElement.value;

    function handleClickOutside(event) {
        let suggestions = document.getElementById('suggestions-product');
        if (!suggestions.contains(event.target) && event.target !== inputElement) {
            suggestions.innerHTML = ''; // Limpiar sugerencias si se hace clic fuera del input
        }
    }

    // Agregar el manejador de clics al documento
    document.addEventListener('click', handleClickOutside);

    if (query.length > 2) { // Comenzar la búsqueda después de 3 caracteres
        document.getElementById('modal-hidden-product').value = "";
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/productos/searchmodelproduct?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions-product');
                suggestions.innerHTML = '';

                data.forEach(item => {
                    let li = document.createElement('li');
                    li.textContent = item.modelo;
                    li.classList.add('list-group-item');
                    li.classList.add('hover-sistema-uno');
                    li.style.cursor = "pointer";

                    li.addEventListener('click', function () {
                        inputElement.value = this.textContent;
                        document.getElementById('modal-hidden-product').value = item.modelo;
                        document.getElementById('modal-hidden-product').dataset.id = item.idProducto;
                        document.getElementById('modal-hidden-product').dataset.cod = item.codigoProducto;
                        validateDisabledRegistro();
                        suggestions.innerHTML = ''; // Limpiar sugerencias después de seleccionar una
                    });

                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions-product').innerHTML = ''; // Limpiar si hay menos de 3 caracteres
        document.getElementById('modal-hidden-product').value = "";
    }
}



function excelButton(id, idProducto) {
    let inputFile = document.getElementById('excel-file');
    inputFile.accept = '.xls,.xlsx';
    inputFile.click();
    updateIdProducto(idProducto);
    setIdList(id);
}

function createItemList(id, serial, idProducto) {
    let headerLi = document.getElementById('header-list-product-' + id);

    cont = document.querySelectorAll('.item-list-product-' + id).length;

    let liItem = createLi(['list-group-item', 'item-list-product-' + id], null);

    let divRow = createDiv(['row'], null);

    let divColSerialNumber = createDiv(['col-6', 'col-md-4', 'col-lg-3'], null);
    let divInputGroup = createDiv(['input-group', 'input-group-sm'], null);
    let inputSerialNumber = createInput(['form-control', 'form-control-sm', 'input-serial', 'input-serial-' + id], null, 'text', serial ? serial : null, 'detalle[' + id + '][ingreso][' + cont + '][serialnumber]');
    inputSerialNumber.placeholder = "Serial number..";
    inputSerialNumber.dataset.producto = idProducto;
    let divInputGroupText = createDiv(['input-group-text'], null);
    let checkSerialNumber = createInput(['form-check-input', 'check-serial-' + id], null, 'checkbox', null, null);
    checkSerialNumber.addEventListener('change', function () { disabledSerialNumber(this, inputSerialNumber); });
    divInputGroupText.appendChild(checkSerialNumber);
    divInputGroup.appendChild(inputSerialNumber);
    divInputGroup.appendChild(divInputGroupText);
    divColSerialNumber.appendChild(divInputGroup);

    let divColEstado = createDiv(['col-4', 'col-md-2'], null);
    let selectEstado = document.createElement('select');
    selectEstado.classList.add('form-select', 'form-select-sm');
    selectEstado.name = 'detalle[' + id + '][ingreso][' + cont + '][estado]';
    arrayEstados.forEach(function (x) {
        let optionEstado = document.createElement('option');
        optionEstado.textContent = x['name'];
        optionEstado.value = x['value'];
        selectEstado.appendChild(optionEstado);
    });
    selectEstado.value = 'NUEVO';
    divColEstado.appendChild(selectEstado);

    let divColRack = createDiv(['d-none', 'd-md-block', 'col-md-3', 'col-lg-2', 'd-flex', 'gap-1'], null);
    
    let selectRack = document.createElement('select');
    selectRack.classList.add('form-select', 'form-select-sm', 'select-rack-item');
    let optionsRackHtml = '<option value="">Rack...</option>';
    
    let selectFila = document.createElement('select');
    selectFila.classList.add('form-select', 'form-select-sm', 'select-ubicacion-item');
    selectFila.name = 'detalle[' + id + '][ingreso][' + cont + '][idUbicacion]';
    selectFila.innerHTML = '<option value="">Fila...</option>';
    selectFila.addEventListener('change', function() {
        updateBtnAdd();
    });

    let currentAlmacenId = document.getElementById('select-almacen').value;
    if(window.APP_DATA && window.APP_DATA.almacenes) {
        let almacen = window.APP_DATA.almacenes.find(a => a.idAlmacen == currentAlmacenId);
        if(almacen && almacen.ubicaciones) {
            almacen.ubicaciones.forEach(ubi => {
                optionsRackHtml += `<option value="${ubi.nombre}">${ubi.nombre}</option>`;
            });
        }
    }
    selectRack.innerHTML = optionsRackHtml;

    selectRack.addEventListener('change', function() {
        let selectedRackName = this.value;
        let optionsFilaHtml = '<option value="">Fila...</option>';
        if (selectedRackName && window.APP_DATA && window.APP_DATA.almacenes) {
            let almacen = window.APP_DATA.almacenes.find(a => a.idAlmacen == document.getElementById('select-almacen').value);
            if (almacen && almacen.filas) {
                let rackFilas = almacen.filas.filter(f => f.nombre_rack == selectedRackName);
                if (rackFilas.length > 0) {
                    rackFilas.forEach(f => {
                        optionsFilaHtml += `<option value="${f.idUbicacionExacta}">Fila ${f.fila_estante}</option>`;
                    });
                }
            }
        }
        selectFila.innerHTML = optionsFilaHtml;
        updateBtnAdd();
    });

    divColRack.appendChild(selectRack);
    divColRack.appendChild(selectFila);

    let divColObservacion = createDiv(['d-none', 'd-md-block', 'col-md-3', 'col-lg-4'], null);
    let inputObservacion = createInput(['form-control', 'form-control-sm'], null, 'text', null, 'detalle[' + id + '][ingreso][' + cont + '][observacion]');
    inputObservacion.placeholder = "Observaciones...";
    divColObservacion.appendChild(inputObservacion);

    let divColDelete = createDiv(['col-2', 'col-md-1', 'text-end'], null);
    let btnDelete = createButton(['btn', 'btn-danger', 'btn-sm'], null, '<i class="bi bi-x-lg"></i>', 'button', [() => deleteItem(liItem),
    () => countProducts(id)
    ]);
    divColDelete.appendChild(btnDelete);

    divRow.appendChild(divColSerialNumber);
    divRow.appendChild(divColEstado);
    divRow.appendChild(divColRack);
    divRow.appendChild(divColObservacion);
    divRow.appendChild(divColDelete);
    liItem.appendChild(divRow);
    headerLi.after(liItem);
    
    // (Removido TomSelect para selectRack para evitar bug visual)
    
    updateBtnAdd();
}

function deleteItem(input) {
    input.remove();
    updateBtnAdd();
}

function deleteList(id) {
    let items = document.querySelectorAll('.item-list-product-' + id);
    items.forEach(function (x) {
        x.remove();
    });
    updateBtnAdd();
}

function validateRegistro() {
    let divForm = document.getElementById('ul-ingreso');
    let inputsForm = divForm.querySelectorAll('.input-serial');
    let selectsForm = divForm.querySelectorAll('select');
    let selectAdquisicion = document.getElementById('select-adquisicion');
    let labelAdquisicion = document.getElementById('select-label-adquisicion');
    let selectAlmacen = document.getElementById('select-almacen');
    let selectMoneda = document.getElementById('select-moneda');
    let btnRegistro = document.getElementById('btnSubmit');
    let disabledBtn = false;

    inputsForm.forEach(function (x) {
        if (x.value == '' && !x.classList.contains('input-not-required')) {
            disabledBtn = true;
        }
    });

    selectsForm.forEach(function (x) {
        if (x.value == '' && !x.classList.contains('select-ubicacion-item') && !x.classList.contains('select-rack-item')) {
            disabledBtn = true;
        }
    });
    
    // Validar que si seleccionó un Rack, DEBE seleccionar una Fila
    let racks = document.querySelectorAll('.select-rack-item');
    let filas = document.querySelectorAll('.select-ubicacion-item');
    racks.forEach((rack, index) => {
        if (rack.value !== '' && filas[index] && filas[index].value === '') {
            disabledBtn = true;
        }
    });

    // Validar que cada producto agregado tenga una cantidad mayor a 0
    let cantidadInputs = divForm.querySelectorAll('[id^="header-cantidad-product-"]');
    if (cantidadInputs.length < 1) {
        disabledBtn = true;
    }
    cantidadInputs.forEach(function (cantInput) {
        let val = parseInt(cantInput.value || cantInput.textContent || '0');
        if (isNaN(val) || val <= 0) {
            disabledBtn = true;
        }
    });

    if (inputsForm.length < 1) {
        disabledBtn = true;
    }

    if (selectAdquisicion.value == '') {
        disabledBtn = true;
    }

    if (selectAlmacen.value == '') {
        disabledBtn = true;
    }

    if (selectMoneda.value == '') {
        disabledBtn = true;
    }

    btnRegistro.disabled = disabledBtn;
}

function changeLabel(input, id) {
    let label = document.getElementById(id);
    if (input.value == '') {
        label.classList.add('text-danger');
        label.classList.remove('text-dark');
        input.classList.add('border-danger');
    } else {
        label.classList.remove('text-danger');
        label.classList.add('text-dark');
        input.classList.remove('border-danger');
    }
}

function updateValidate() {
    let divForm = document.getElementById('ul-ingreso');
    let inputsForm = divForm.querySelectorAll('.input-serial');
    let selectsForm = divForm.querySelectorAll('select');
    let cantidadInputs = divForm.querySelectorAll('[id^="header-cantidad-product-"]');
    let selectAdquisicion = document.getElementById('select-adquisicion');
    let selectAlmacen = document.getElementById('select-almacen');
    let selectMoneda = document.getElementById('select-moneda');

    inputsForm.forEach(function (x) {
        x.addEventListener('input', validateRegistro);
    });
    selectsForm.forEach(function (x) {
        x.addEventListener('change', validateRegistro);
    });
    cantidadInputs.forEach(function (x) {
        x.addEventListener('input', validateRegistro);
        x.addEventListener('change', validateRegistro);
    });
    selectAdquisicion.addEventListener('change', validateRegistro);
    selectAlmacen.addEventListener('change', validateRegistro);
    selectMoneda.addEventListener('change', validateRegistro);
}

function updateBtnAdd() {
    updateValidate();
    validateRegistro();
}

function countProducts(id) {
    let importeTotal = 0;
    let productos = document.querySelectorAll('[id^="header-preciototal-product-"]');

    let items = document.querySelectorAll('.item-list-product-' + id);
    let cant = document.getElementById('header-cantidad-product-' + id);

    let precioUni = document.getElementById('header-preciounitario-product-' + id);
    let precioTot = document.getElementById('header-preciototal-product-' + id);
    let hiddenPrecioTot = document.getElementById('header-hidden-preciototal-' + id);

    if (cant.tagName === 'INPUT') {
        cant.value = items.length;
    } else {
        cant.textContent = items.length;
    }
    precioTot.textContent = items.length * precioUni.dataset.price;
    precioTot.dataset.total = items.length * precioUni.dataset.price;
    hiddenPrecioTot.value = items.length * precioUni.dataset.price;

    productos.forEach(function (x) {
        importeTotal += parseFloat(x.dataset.total);
    });
    totalComprobante = importeTotal.toFixed(2);
    updateTotalProductos();
}

function updateTotalProductos() {
    let inputImporteTotal = document.getElementById('importe-total-comprobante');
    let inputDescuento = document.getElementById('importe-descuento-comprobante');
    inputImporteTotal.value = totalComprobante - inputDescuento.value;
}

function disabledSerialNumber(check, input) {
    if (check.checked) {
        input.readOnly = true;
        input.value = 0;
    } else {
        input.readOnly = false;
        input.value = '';
    }
    updateBtnAdd();
}

function toggleAllSerials(id, isChecked) {
    let checks = document.querySelectorAll('.check-serial-' + id);
    checks.forEach(function (check) {
        if (check.checked !== isChecked) {
            check.checked = isChecked;
            let input = check.closest('.input-group').querySelector('.input-serial');
            disabledSerialNumber(check, input);
        }
    });
}

document.getElementById('btnIngreso')?.addEventListener('click', createProductList);
document.getElementById('btnIngreso')?.addEventListener('click', updateBtnAdd);
document.getElementById('modal-input-price')?.addEventListener('input', validateDisabledRegistro);
document.getElementById('modal-input-product')?.addEventListener('input', validateDisabledRegistro);
document.getElementById('modal-select-medida')?.addEventListener('change', validateDisabledRegistro);
document.getElementById('importe-descuento-comprobante')?.addEventListener('blur', updateTotalProductos);

document.addEventListener('DOMContentLoaded', function () {
    validateDisabledRegistro();
    validateRegistro();
});

var listData = '';
function readExcel(input) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
            const data = event.target.result;
            const workbook = XLSX.read(data, { type: "binary" });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet);
            const key = jsonData[0] ? Object.keys(jsonData[0])[0] : null;
            const values = Object.values(jsonData);
            if (key === 'SERIES') {
                values.forEach(function (x) {
                    if (x.SERIES == 'nulo') {
                        createItemList(listData, '0', importIdProducto);
                    } else {
                        createItemList(listData, x.SERIES, importIdProducto);
                    }
                    countProducts(listData);
                    invalidarChecks(listData);
                    updateBtnAdd();
                });
                listData = '';
            } else {
                alert('Plantilla incorrecta, porfavor usa la plantilla ubicada en la parte inferior.');
                listData = '';
            }
            input.value = '';
        };
        reader.readAsBinaryString(file);
    }
    input.value = '';
}

function setIdList(id) {
    listData = id;
}

function invalidarChecks(id) {
    let inputsText = document.querySelectorAll('.input-serial-' + id);
    let checks = document.querySelectorAll('.check-serial-' + id);

    inputsText.forEach(function (x) {
        x.readOnly = true;
    });

    checks.forEach(function (x) {
        x.checked = true;
    });
}

function generatePlantilla() {
    const headers = ["SERIES", "!!Solo coloca valores en la columna de series(si no tiene serie coloca la palabra 'nulo')!!"];

    // Crear una fila de datos vacíos para las cabeceras
    const data = [headers]; // Solo cabeceras, sin datos

    // Crear la hoja de trabajo a partir de las cabeceras
    const ws = XLSX.utils.aoa_to_sheet(data);

    // Crear un libro de trabajo y agregar la hoja
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Plantilla");

    // Escribir el archivo Excel y permitir su descarga
    XLSX.writeFile(wb, "plantilla_series.xlsx");
}


function confirmForm() {
    let divForm = document.getElementById('ul-ingreso');
    let cantidadInputs = divForm.querySelectorAll('[id^="header-cantidad-product-"]');
    let hasZeroQuantity = false;

    cantidadInputs.forEach(function (cantInput) {
        let val = parseInt(cantInput.value || cantInput.textContent || '0');
        if (isNaN(val) || val <= 0) {
            hasZeroQuantity = true;
        }
    });

    if (hasZeroQuantity) {
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad requerida',
            text: 'Todos los productos seleccionados deben tener una cantidad mayor a 0 para registrar.',
            confirmButtonText: 'Aceptar',
            customClass: {
                confirmButton: 'btn-warning'
            }
        });
        return;
    }

    Swal.fire({
        title: '!!No podras modificar el documento despues!!',
        text: '¿Estás seguro de que deseas continuar?',
        icon: 'warning',
        iconColor: '#00b1b9',
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn-primary',
            cancelButton: 'btn btn-danger'
        },
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            validateSeriesForm();
        }
    });
}

function sendFormInsertIngreso() {
    let formDocumento = document.getElementById('form-create-doc');

    if (formDocumento) {
        console.log(validateCantXProduct());
        if (validateCantXProduct() == false) {
            Swal.fire({
                icon: 'warning',
                title: 'Producto sin serie',
                text: 'verífica que ningún producto este vacío.',
                confirmButtonText: 'Aceptar',
                customClass: {
                    confirmButton: 'btn-success',
                },
            });
            return;
        }

        formDocumento.submit();
    }
}

function validateCantXProduct() {
    let detallesProducto = document.querySelectorAll('.list-group-item-dark');
    let valid = true;
    detallesProducto.forEach(function (x) {
        let cantElement = x.querySelector('[id*="header-cantidad-product"]');
        let cant = parseInt(cantElement.textContent.trim());
        if (cant < 1) {
            valid = false;
        }
        console.log('item : ' + cant);
    });

    return valid;
}

function validateSeriesForm() {
    let serialWhitProduct = [];
    let serialsForm = document.querySelectorAll('.input-serial');

    serialsForm.forEach(function (x) {
        serialWhitProduct.push({
            serie: x.value,
            producto: x.dataset.producto
        });
    });

    let jsonData = JSON.stringify({ data: serialWhitProduct });

    let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/documento/validateseries', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: jsonData
    })
        .then(response => {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error('Error al registrar.');
            }
        })
        .then(data => {
            console.log(data);
            if (data.valid == true) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Series ya registradas',
                    text: data.series,
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        confirmButton: 'btn-success',
                    },
                });
            } else {
                sendFormInsertIngreso();
            }
        })
        .catch(error => {
            console.error('error: ' + error);
        });
}

function logFormData(formData) {
    // Crear un iterador sobre los datos del FormData
    for (let [key, value] of formData.entries()) {
        console.log(key + ": " + value); // Imprime el nombre del campo y su valor
    }
}

function deleteForm(idComprobante) {
    let formDelete = document.getElementById('form-deletecomprobante');
    let hiddenDelete = document.getElementById('hidden-form-deletecomprobante');

    Swal.fire({
        title: '¿Seguro de eliminar este documento?',
        text: '!Esta acción no se podra revertir !',
        icon: 'warning',
        iconColor: '#00b1b9',
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            hiddenDelete.value = idComprobante;
            formDelete.submit();
        }
    });
}

function updateIdProducto(idp) {
    importIdProducto = idp
}

function updateIdIndexProducto(idx) {
    idIndexProduct = idx;
}

function btnScanUpdateValuesImport(index, producto) {
    updateIdIndexProducto(index);
    updateIdProducto(producto);
}

document.getElementById('btn-list-scan-codes').addEventListener('click', function (event) {
    getSerials().forEach(function (x) {
        createItemList(idIndexProduct, x, importIdProducto);
        countProducts(idIndexProduct);
        updateBtnAdd();
    });
});

function promptMassiveSeries(id, idProducto) {
    Swal.fire({
        title: 'Ingreso Masivo de Series',
        html: '<div class="text-start mb-2"><small class="text-muted">Pega la lista de series separadas por comas (,) o saltos de línea.</small></div>' +
              '<textarea id="swal-textarea-series" class="form-control" rows="8" placeholder="Ejemplo:\nSERIE123\nSERIE456\nSERIE789"></textarea>',
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#6f42c1',
        preConfirm: () => {
            const textarea = document.getElementById('swal-textarea-series');
            return textarea ? textarea.value : '';
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Dividir por comas o saltos de línea, limpiar espacios vacíos
            let rawSeries = result.value.split(/[\n,]+/);
            let series = rawSeries.map(s => s.trim()).filter(s => s.length > 0);
            
            if (series.length > 0) {
                series.forEach(serial => {
                    createItemList(id, serial, idProducto);
                });
                countProducts(id);
                updateBtnAdd();
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Series agregadas!',
                    text: `Se agregaron ${series.length} series con éxito.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }
    });
}

function adjustGenericSeries(id, targetCount, idProducto) {
    let items = document.querySelectorAll('.item-list-product-' + id);
    let currentCount = items.length;

    if (targetCount > currentCount) {
        // Añadir items genéricos
        let diff = targetCount - currentCount;
        for (let i = 0; i < diff; i++) {
            createItemList(id, '0', idProducto); 
        }
        invalidarChecks(id);
    } else if (targetCount < currentCount) {
        // Eliminar items desde el final
        let diff = currentCount - targetCount;
        for (let i = 0; i < diff; i++) {
            items[items.length - 1 - i].remove();
        }
    }
    
    countProducts(id);
    updateBtnAdd();
}

document.getElementById('select-almacen')?.addEventListener('change', function() {
    let currentAlmacenId = this.value;
    let optionsRackHtml = '<option value="">Rack...</option>';
    if(window.APP_DATA && window.APP_DATA.almacenes) {
        let almacen = window.APP_DATA.almacenes.find(a => a.idAlmacen == currentAlmacenId);
        if(almacen && almacen.ubicaciones) {
            almacen.ubicaciones.forEach(ubi => {
                optionsRackHtml += `<option value="${ubi.nombre}">${ubi.nombre}</option>`;
            });
        }
    }
    
    document.querySelectorAll('.select-rack-item').forEach(s => {
        s.innerHTML = optionsRackHtml;
    });
    
    document.querySelectorAll('.select-ubicacion-item').forEach(s => {
        s.innerHTML = '<option value="">Fila...</option>';
    });
});
