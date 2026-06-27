var arrayEstados = window.APP_DATA.estados;
function openPdfInNewWindow() {
var url = "/generateSerialPdf/" + window.APP_DATA.idComprobante;
window.open(url, '', 'width=800,height=600,scrollbars=yes,location=no,toolbar=no,status=no');
}

function createProductList(){
let productInput = document.getElementById('modal-hidden-product');
let modalDiv = document.getElementById('registerModal');
let inputsModal = modalDiv.querySelectorAll('input');
let selectModalMedida = document.getElementById('modal-select-medida');
let inputModalPrice = document.getElementById('modal-input-price');

if (!productInput.value.trim()) {
return;
}

let ulIngreso = document.getElementById('ul-ingreso');

let liProduct = createLi(['list-group-item','list-group-item-dark'],'header-list-product-' + index);

let inputHiddenProduct = createInput(null,'header-hidden-product-' + index,'hidden',index,null);
inputHiddenProduct.dataset.medida = selectModalMedida.value;
inputHiddenProduct.dataset.price = inputModalPrice.value;
inputHiddenProduct.dataset.id = productInput.dataset.id;

let inputDetalleProducto = createInput(null,null,'hidden',productInput.dataset.id,'detalle['+index+'][producto]');
let inputDetalleMedida = createInput(null,null,'hidden',selectModalMedida.value,'detalle['+index+'][medida]');
let inputDetallePrecioUnitario = createInput(null,null,'hidden',inputModalPrice.value,'detalle['+index+'][preciounitario]');
let inputDetallePrecioTotal = createInput(null,'header-hidden-preciototal-' + index,'hidden',0,'detalle['+index+'][preciototal]');

let divRow = createDiv(['row'],null);

let divRowOptions = createDiv(['row','collapse'],'collapse-product-'+index);

let divColOptions = createDiv(['col-8','text-end','pe-0'],null);
let divColDelete = createDiv(['col-4','text-start'],null);

let divColCantidad = createDiv(['col-1','col-md-1','text-center'],null);
let h5Cantidad = document.createElement('input');
h5Cantidad.type = 'number';
h5Cantidad.id = 'header-cantidad-product-' + inputHiddenProduct.value;
h5Cantidad.value = '0';
h5Cantidad.min = '0';
h5Cantidad.classList.add('form-control', 'form-control-sm', 'text-center', 'p-1', 'mx-auto');
h5Cantidad.style.width = '60px';
h5Cantidad.addEventListener('change', function() {
    if(typeof adjustGenericSeries === 'function') {
        adjustGenericSeries(inputHiddenProduct.value, parseInt(this.value) || 0, productInput.dataset.id);
    }
});
// Checkbox para seleccionar todos los seriales de este producto
let divCheckAll = createDiv(['form-check','d-flex','justify-content-center','mb-0'],null);
let checkAll = document.createElement('input');
checkAll.type = 'checkbox';
checkAll.classList.add('form-check-input');
checkAll.style.cursor = 'pointer';
checkAll.title = 'Seleccionar todos (N/A)';
checkAll.addEventListener('change', function() {
toggleAllSerials(inputHiddenProduct.value, this.checked);
});
divCheckAll.appendChild(checkAll);

divColCantidad.appendChild(divCheckAll);
divColCantidad.appendChild(h5Cantidad);

let divColProduct = createDiv(['col-4','col-md-5','col-lg-3','d-flex','truncate'],null);
let h5Product = createH5(['h-100','text-uppercase'],null,productInput.value + "&nbsp;");
let smallProduct = document.createElement('small');
smallProduct.classList.add('d-none','d-md-block');
smallProduct.textContent = productInput.dataset.cod;
divColProduct.appendChild(h5Product);
divColProduct.appendChild(smallProduct);

let divColMedida = createDiv(['d-none','d-lg-block','col-lg-2','text-center'],null);
let pMedida = createParrafo(null,null,selectModalMedida.value);
divColMedida.appendChild(pMedida);

let divColPrecioUnitario = createDiv(['col-2','col-md-2','text-center'],null);
let pPrecioUnitario = createParrafo(null,'header-preciounitario-product-' + inputHiddenProduct.value,inputModalPrice.value);
pPrecioUnitario.dataset.price = inputModalPrice.value;
divColPrecioUnitario.appendChild(pPrecioUnitario);

let divColPrecioTotal = createDiv(['col-2','col-md-2','text-center'],null);
let h5TotalPrice = createH5(null,'header-preciototal-product-' + inputHiddenProduct.value,'0');
h5TotalPrice.dataset.total = '0';
divColPrecioTotal.appendChild(h5TotalPrice);

let divColButtons = createDiv(['col-3','col-md-2','pe-0','ps-0','text-end'],null);
let buttonAdd = createButton(['btn', 'btn-success', 'btn-sm'],
null,
'<i class="bi bi-plus-lg"></i>',
'button',
[() => createItemList(inputHiddenProduct.value, '',productInput.dataset.id),() => countProducts(inputHiddenProduct.value)]
);
let buttonDelete = createButton(['btn','btn-danger','me-2','btn-sm'],
null,
'<i class="bi bi-trash"></i> <span class="d-none d-md-inline">Eliminar</span>',
'button',
[() => deleteItem(liProduct),() => deleteList(inputHiddenProduct.value),() => countProducts(inputHiddenProduct.value)]
);
let buttonExcel = createButton(['btn','bg-success','ms-2','btn-sm','text-light'],
null,
'<i class="bi bi-filetype-xlsx"></i> <span class="d-none d-md-inline">Excel</span>',
'button',
[() => excelButton(inputHiddenProduct.value,productInput.dataset.id)]
);

divColDelete.appendChild(buttonDelete);
divColOptions.innerHTML = `<button class="btn btn-warning btn-sm" onclick="btnScanUpdateValuesImport('${inputHiddenProduct.value}','${productInput.dataset.id}')" type="button" data-bs-toggle="modal" data-bs-target="#typeScanModal"><i class="bi bi-qr-code"></i> <span class="d-none d-md-inline">Escanear</span></button>`;
divColOptions.appendChild(buttonExcel);
let buttonOption = document.createElement('button');
buttonOption.type = 'button';
buttonOption.dataset.bsToggle = 'collapse';
buttonOption.dataset.bsTarget = '#collapse-product-'+index;
buttonOption.ariaExpanded = 'false';
buttonOption.ariaControls = 'collapse-product-'+index;
buttonOption.classList.add('btn','btn-sm','btn-secondary','me-2');
buttonOption.innerHTML = '<i class="bi bi-three-dots-vertical"></i>';
let buttonAddMassive = createButton(['btn', 'btn-purple', 'btn-sm', 'me-2'],
null,
'<i class="bi bi-clipboard-plus-fill"></i>',
'button',
[() => promptMassiveSeries(inputHiddenProduct.value, productInput.dataset.id)]
);

divColButtons.appendChild(buttonOption);
divColButtons.appendChild(buttonAddMassive);
divColButtons.appendChild(buttonAdd);

divRow.appendChild(divColCantidad);
divRow.appendChild(divColProduct);
divRow.appendChild(divColMedida);
divRow.appendChild(divColPrecioUnitario);
divRow.appendChild(divColPrecioTotal);
divRow.appendChild(divColButtons);
divRowOptions.appendChild(divColDelete);
divRowOptions.appendChild(divColOptions);
liProduct.appendChild(inputDetalleProducto);
liProduct.appendChild(inputHiddenProduct);
liProduct.appendChild(inputDetalleMedida);
liProduct.appendChild(inputDetallePrecioUnitario);inputDetallePrecioTotal
liProduct.appendChild(inputDetallePrecioTotal);
liProduct.appendChild(divRow);
liProduct.appendChild(divRowOptions);
ulIngreso.appendChild(liProduct);

index++;

inputsModal.forEach(function(x){
x.value = "";
});
}