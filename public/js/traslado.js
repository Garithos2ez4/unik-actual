let hiddenBody = document.getElementById('hidden-body');
let suggestionUlProducto = document.getElementById('suggestions-producto');

const contadorProductos = {
    productosAgregados: 0,
    actualizarContador: function() {
        document.getElementById('contador-productos').textContent = `Series Agregadas: ${this.productosAgregados}`;
    },
    agregarProducto: function() {
        this.productosAgregados++;
        this.actualizarContador();
    },
    eliminarProducto: function() {
        if (this.productosAgregados > 0) {
            this.productosAgregados--;
            this.actualizarContador();
        }
    }
};

// 1. Buscador de Productos
const inputBuscarProducto = document.getElementById('search-producto');
if(inputBuscarProducto) {
    inputBuscarProducto.addEventListener('input', function () {
        let query = this.value;
        if (query.length > 2) {
            fetch(`/traslado/search-producto-ajax?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    suggestionUlProducto.innerHTML = '';
                    if(data.length > 0) {
                        hiddenBody.style.display = 'block';
                        data.forEach(item => {
                            let li = createLi(['list-group-item', 'hover-sistema-uno'], null);
                            li.style.cursor = 'pointer';
                            li.innerHTML = `
                                <strong>${item.modelo}</strong><br>
                                <small class="text-secondary">${item.marca_producto ? item.marca_producto.nombreMarca : ''} | ${item.codigoProducto}</small>
                            `;
                            li.onclick = () => {
                                suggestionUlProducto.innerHTML = '';
                                hiddenBody.style.display = 'none';
                                inputBuscarProducto.value = '';
                                cargarSeriesDeProducto(item);
                            };
                            suggestionUlProducto.appendChild(li);
                        });
                    } else {
                        hiddenBody.style.display = 'none';
                    }
                });
        } else {
            suggestionUlProducto.innerHTML = '';
            hiddenBody.style.display = 'none';
        }
    });
}

let seriesTemporalesProducto = [];

function cargarSeriesDeProducto(item) {
    let idProducto = item.idProducto;
    let modelo = item.modelo;
    
    fetch(`/traslado/series-disponibles?idProducto=${idProducto}`)
        .then(response => response.json())
        .then(data => {
            if(data.length === 0) {
                alertBootstrap('No hay series disponibles para este producto.', 'warning');
                return;
            }
            // Filtrar series que ya están agregadas para no contarlas en el stock disponible
            let seriesNuevas = data.filter(serie => !validateDuplicity(serie.numeroSerie));
            
            if(seriesNuevas.length === 0) {
                alertBootstrap('Todas las series disponibles de este producto ya están en la lista.', 'info');
                return;
            }

            // Guardar temporalmente
            seriesTemporalesProducto = seriesNuevas;

            // Mostrar el modal
            document.getElementById('modal-cantidad-producto-nombre').textContent = modelo;
            document.getElementById('modal-cantidad-disponible').textContent = seriesNuevas.length;
            
            let inputCantidad = document.getElementById('input-cantidad-trasladar');
            inputCantidad.max = seriesNuevas.length;
            inputCantidad.value = 1;

            let modalEl = document.getElementById('modalCantidadSeries');
            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        });
}

function confirmarCantidadSeries() {
    let inputCantidad = document.getElementById('input-cantidad-trasladar');
    let cantidadRequerida = parseInt(inputCantidad.value);
    let cantidadMaxima = parseInt(inputCantidad.max);

    if (isNaN(cantidadRequerida) || cantidadRequerida < 1 || cantidadRequerida > cantidadMaxima) {
        alertBootstrap('Por favor, ingresa una cantidad válida entre 1 y ' + cantidadMaxima, 'warning');
        return;
    }

    // Recortar el array a la cantidad solicitada
    let seriesATrasladar = seriesTemporalesProducto.slice(0, cantidadRequerida);
    
    let agregadas = 0;
    seriesATrasladar.forEach(serie => {
        if(addProductoSerial(serie)) agregadas++;
    });

    if(agregadas > 0) {
        alertBootstrap(`Se agregaron ${agregadas} series del producto a la lista.`, 'success');
    }

    // Cerrar modal
    let modalEl = document.getElementById('modalCantidadSeries');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if(modal) modal.hide();
    
    // Limpiar temporal
    seriesTemporalesProducto = [];
}

// 2. Procesar Pegado Masivo
function procesarPegadoMasivo() {
    let textarea = document.getElementById('textarea-series-masivas');
    let text = textarea.value.trim();
    if(!text) return;

    let seriesArr = text.split(/[\n,]+/).map(s => s.trim()).filter(s => s !== '');
    
    // Ocultar modal
    let modalEl = document.getElementById('modalPegadoMasivo');
    let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.hide();
    
    if(seriesArr.length === 0) return;

    fetch('/traslado/search-multiple-series', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ series: seriesArr })
    })
    .then(response => response.json())
    .then(data => {
        if(data.length === 0) {
            alertBootstrap('No se encontró ninguna de las series pegadas en estado disponible.', 'warning');
            return;
        }
        let agregadas = 0;
        data.forEach(serie => {
            if(addProductoSerial(serie)) agregadas++;
        });
        if(agregadas > 0) alertBootstrap(`Se agregaron ${agregadas} series de las solicitadas.`, 'success');
        textarea.value = '';
    });
}

// 3. Destino Global
function aplicarDestinoGlobal() {
    let selectGlobal = document.getElementById('destino-global');
    let value = selectGlobal.value;
    if(!value) return;

    let selectsIndividuales = document.querySelectorAll('.item-traslado select[name^="traslado"]');
    selectsIndividuales.forEach(select => {
        let origenAlmacenId = select.getAttribute('data-origen-id');
        if(origenAlmacenId != value) {
            select.value = value;
        } else {
            select.value = ''; // Si el destino global es igual al origen, dejar vacío
        }
    });
    habilitarBotonReubicar();
}

function addProductoSerial(object) {
    let serieNumber = object.numeroSerie;
    let modelo = object.detalle_comprobante && object.detalle_comprobante.producto ? object.detalle_comprobante.producto.modelo : '-';
    let codigo = object.detalle_comprobante && object.detalle_comprobante.producto ? object.detalle_comprobante.producto.codigoProducto : '';
    let proveedor = object.detalle_comprobante && object.detalle_comprobante.comprobante && object.detalle_comprobante.comprobante.preveedor ? object.detalle_comprobante.comprobante.preveedor.nombreProveedor : '';
    let origen = object.almacen ? object.almacen.descripcion : '-';
    let origenId = object.idAlmacen;

    if (validateDuplicity(serieNumber)) {
        return false; // Omitir duplicados silenciosamente en modo masivo
    }

    contadorProductos.agregarProducto();

    let ulTraslado = document.getElementById('lista-traslado');

    let itemTraslado = createLi(['list-group-item', 'item-traslado'], null);
    itemTraslado.setAttribute('data-serie', serieNumber);

    let divRow = createDiv(['row', 'text-center'], null);

    let divColProducto = createDiv(['col-10', 'col-md-4', 'text-start'], null);
    divColProducto.innerHTML = "<strong>" + modelo + "</strong><br class='d-none d-md-inline'><small class='text-secondary d-none d-md-inline'>" + codigo + "</small>";

    let divColLinkDelete = createDiv(['col-2', 'd-md-none', 'text-end'], null);
    let linkDelete = createLink(['text-danger'], null, '<i class="bi bi-x-lg"></i>', 'javascript:void(0)', [() => {
        itemTraslado.remove();
        contadorProductos.eliminarProducto();
        validateProductos();
    }]);
    divColLinkDelete.appendChild(linkDelete);

    let divColSerie = createDiv(['col-md-2', 'text-start', 'text-md-center'], null);
    divColSerie.innerHTML = '<small class="fw-bold text-primary">' + serieNumber + '</small><br class="d-none d-md-inline"><small class="text-secondary d-none d-md-inline">' + proveedor + '</small>';

    let divColEstado = createDiv(['col-md-1', 'd-none', 'd-md-block'], null);
    divColEstado.innerHTML = '<small>' + object.estado + '</small>';

    let divColOrigen = createDiv(['col-6', 'col-md-2'], null);
    divColOrigen.innerHTML = '<small class="form-label d-md-none">Origen</small>' + "<select class='form-select form-select-sm' disabled><option selected>" + origen + "</option></select>";

    let divColDestino = createDiv(['col-6', 'col-md-2'], null);
    let selectDestino = document.createElement('select');
    selectDestino.name = 'traslado[' + object.idRegistro + ']';
    selectDestino.classList.add('form-select', 'form-select-sm');
    selectDestino.setAttribute('data-origen-id', origenId);
    let defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = '-elige un destino-';
    selectDestino.appendChild(defaultOption);
    
    // almacenes viene de la variable window.almacenes definida en blade
    if(typeof almacenes !== 'undefined') {
        almacenes.forEach(almacen => {
            if (origenId != almacen.idAlmacen) {
                let optionDestino = document.createElement('option');
                optionDestino.value = almacen.idAlmacen;
                optionDestino.textContent = almacen.descripcion;
                selectDestino.appendChild(optionDestino);
            }
        });
    }
    
    // Aplicar destino global si ya está seleccionado
    let selectGlobal = document.getElementById('destino-global');
    if(selectGlobal && selectGlobal.value && selectGlobal.value != origenId) {
        selectDestino.value = selectGlobal.value;
    }

    divColDestino.innerHTML = '<small class="form-label d-md-none">Destino</small>';
    divColDestino.appendChild(selectDestino);

    let divColBtnDelete = createDiv(['col-md-1', 'text-start', 'text-md-center', 'd-none', 'd-md-block'], null);
    let btnDelete = createButton(['btn', 'btn-danger', 'btn-sm'], null, '<i class="bi bi-trash-fill"></i>', 'button', [() => {
        itemTraslado.remove();
        contadorProductos.eliminarProducto();
        validateProductos();
    }]);
    divColBtnDelete.appendChild(btnDelete);

    divRow.appendChild(divColProducto);
    divRow.appendChild(divColLinkDelete);
    divRow.appendChild(divColSerie);
    divRow.appendChild(divColEstado);
    divRow.appendChild(divColOrigen);
    divRow.appendChild(divColDestino);
    divRow.appendChild(divColBtnDelete);
    itemTraslado.appendChild(divRow);
    ulTraslado.appendChild(itemTraslado);

    validateProductos();
    return true;
}

function habilitarBotonReubicar() {
    const btnReubicar = document.getElementById('btn-reubicar-submit');
    const selectsDestino = document.querySelectorAll('.item-traslado select[name^="traslado"]');
    
    let habilitado = true;
    if(selectsDestino.length === 0) habilitado = false;
    selectsDestino.forEach(select => {
        if (select.value == '') {
            habilitado = false;
        }
    });
    if(btnReubicar) btnReubicar.disabled = !habilitado;
}

document.addEventListener('change', function (event) {
    if (event.target.matches('.item-traslado select[name^="traslado"]')) {
        habilitarBotonReubicar();
    }
});

function mostrarModalConfirmacion(event) {
    event.preventDefault();
    const modal = document.getElementById('modalConfirmacion');
    const fondo = document.getElementById('hidden-body');
    if(modal) modal.style.display = "block";
    if(fondo) fondo.style.display = "block";
}

function confirmarReubicacion(event) {
    event.preventDefault();
    document.querySelector('form').submit();
}

document.addEventListener('DOMContentLoaded', function () {
    const btnReubicar = document.getElementById('btn-reubicar');
    if (btnReubicar) btnReubicar.addEventListener('click', mostrarModalConfirmacion);

    const btnConfirmar = document.getElementById('btn-confirmar');
    if (btnConfirmar) btnConfirmar.addEventListener('click', confirmarReubicacion);

    const btnCancelar = document.getElementById('btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function () {
            const modal = document.getElementById('modalConfirmacion');
            const fondo = document.getElementById('hidden-body');
            if(modal) modal.style.display = "none";
            if(fondo) fondo.style.display = "none";
        });
    }
});

function validateProductos() {
    let itemsProductos = document.querySelectorAll('.item-traslado');
    let ulTraslado = document.getElementById('lista-traslado');
    let avisoVacio = document.getElementById('aviso-vacio');
    let btnReubicar = document.getElementById('btn-reubicar');

    if (itemsProductos.length > 0) {
        if(ulTraslado) {
            ulTraslado.style.visibility = 'visible';
            ulTraslado.style.height = '70vh';
        }
        if(avisoVacio) avisoVacio.style.display = 'none';
        if(btnReubicar) btnReubicar.style.display = 'block';
        habilitarBotonReubicar(); 
    } else {
        if(ulTraslado) {
            ulTraslado.style.visibility = 'hidden';
            ulTraslado.style.height = '0';
        }
        if(avisoVacio) avisoVacio.style.display = 'block';
        if(btnReubicar) btnReubicar.style.display = 'none';
    }
}

function validateDuplicity(serial) {
    let itemsProductos = document.querySelectorAll('.item-traslado');
    for (let i = 0; i < itemsProductos.length; i++) {
        if (itemsProductos[i].dataset.serie == serial) {
            return true;
        }
    }
    return false;
}

document.addEventListener('click', function(event) {
    let suggestions = document.getElementById('suggestions-producto');
    let hiddenBody = document.getElementById('hidden-body');
    if (suggestions && !suggestions.contains(event.target) && event.target.id !== 'search-producto') {
        suggestions.innerHTML = '';
        if(hiddenBody) hiddenBody.style.display = 'none';
    }
});
