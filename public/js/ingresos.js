document.getElementById('search').addEventListener('input', function () {
    let query = this.value;
    let hiddenBody = document.getElementById('hidden-body');
    if (query.length > 2) { // Comenzar la b��squeda despu��s de 3 caracteres
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/ingresos/searchingresos?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions');
                hiddenBody.style.display = 'block';
                suggestions.innerHTML = '';

                data.forEach(item => {
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'hover-sistema-uno');
                    li.style.cursor = "pointer";

                    let divRow = document.createElement('div');
                    divRow.classList.add('row');

                    let divColProduct = document.createElement('div');
                    divColProduct.classList.add('d-none', 'd-md-block', 'col-md-3', 'text-start');
                    let smallProduct = document.createElement('small');
                    smallProduct.textContent = item.Proveedor.nombreProveedor;
                    divColProduct.appendChild(smallProduct);

                    let divColSerial = document.createElement('div');
                    divColSerial.classList.add('col-12', 'col-md-6');
                    let smallSerial = document.createElement('small');
                    smallSerial.textContent = item.numeroSerie;
                    divColSerial.appendChild(smallSerial);

                    let divColDate = document.createElement('div');
                    divColDate.classList.add('col-6', 'col-md-2');
                    let smallDate = document.createElement('small');
                    smallDate.textContent = item.fechaIngresoPerso;
                    divColDate.appendChild(smallDate);

                    let divColState = document.createElement('div');
                    divColState.classList.add('col-6', 'col-md-1');
                    let smallState = document.createElement('small');
                    smallState.textContent = item.Registro.estado;
                    // Color segun estado
                    if(item.Registro.estado == 'DEVOLUCION') smallState.classList.add('text-warning', 'fw-bold');
                    if(item.Registro.estado == 'NUEVO') smallState.classList.add('text-sistema-uno');
                    if(item.Registro.estado == 'ENTREGADO') smallState.classList.add('text-success');
                    
                    divColState.appendChild(smallState);

                    li.addEventListener('click', function () {
                        document.getElementById('search').value = item.numeroSerie;
                        suggestions.innerHTML = '';
                        let fechaMovimiento = new Date(item.Registro.fechaMovimiento);
                        dataModalDetalle(item);
                    });

                    divRow.appendChild(divColProduct);
                    divRow.appendChild(divColSerial);
                    divRow.appendChild(divColDate);
                    divRow.appendChild(divColState);
                    li.appendChild(divRow);
                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions').innerHTML = ''; // Limpiar si hay menos de 3 caracteres
        hiddenBody.style.display = 'none';
    }
});

function dataModalDetalle(json) {
    let myModal = new bootstrap.Modal(document.getElementById('detalleModal'));
    let titleProduct = document.getElementById('titleproduct-modal-detail');
    let serialNumber = document.getElementById('serialnumber-modal-detail');
    let state = document.getElementById('state-modal-detail');
    let user = document.getElementById('user-modal-detail');
    let date = document.getElementById('date-modal-detail');
    let observacion = document.getElementById('obs-modal-detail');
    let proveedor = document.getElementById('proveedor-modal-detail');
    let hidden = document.getElementById('idregistro-modal-detail');
    let ubicacion = document.getElementById('almacen-modal-detail');

    titleProduct.textContent = json.registro_producto.detalle_comprobante.producto.nombreProducto;
    serialNumber.textContent = json.registro_producto.numeroSerie;
    stateJson = json.registro_producto.estado.trim().toUpperCase();
    
    let fecha = new Date(json.registro_producto.fechaMovimiento);
    let day = fecha.getDate().toString().padStart(2, '0');
    let month = (fecha.getMonth() + 1).toString().padStart(2, '0');
    let year = fecha.getFullYear();
    
    proveedor.textContent = json.registro_producto.detalle_comprobante.comprobante.preveedor.nombreProveedor;
    user.textContent = json.usuario.user;
    date.textContent = `${day}/${month}/${year}`;
    observacion.value = json.registro_producto.observacion;
    hidden.value = json.registro_producto.idRegistro;
    ubicacion.value = json.registro_producto.idAlmacen;

    // Normalizar para quitar acentos para comparar mejor
    let normalizedState = stateJson.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    
    // Buscar la opcin que coincida con el valor normalizado
    Array.from(state.options).forEach(option => {
        let normalizedOption = option.value.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        if (normalizedOption === normalizedState) {
            state.value = option.value;
        }
    });

    state.dataset.originalState = state.value;
    document.getElementById('check-fallo-entrega').checked = false; // Resetear checkbox
    if (stateJson === 'DEVOLUCION') {
        state.disabled = false;
    } else if (stateJson === 'ENTREGADO' || stateJson === 'GARANTIA') {
        state.disabled = true;
    } else {
        state.disabled = false;
    }
    myModal.show();
}

function hideSuggestions(event) {
    let suggestions = document.getElementById('suggestions');
    let hiddenBody = document.getElementById('hidden-body');
    if (!suggestions.contains(event.target) && event.target.id !== 'search') {
        suggestions.innerHTML = ''; // Oculta las sugerencias
        hiddenBody.style.display = 'none';
    }
}

// A�0�9adir manejador de eventos para el clic en el documento
document.addEventListener('click', hideSuggestions);

function validateForm() {
    let proveedor = document.getElementById('proveedor-select').value;
    let documento = document.getElementById('documento-select').value;
    let numeroDocumento = document.getElementById('documento-number').value;
    let disabled = false;

    if (proveedor == '') {
        disabled = true;
    }

    if (documento == '') {
        disabled = true;
    }

    if (numeroDocumento == '') {
        disabled = true;
    }

    return disabled;
}

function disableButton() {
    let btnSave = document.getElementById('btn-save');
    btnSave.disabled = validateForm();
}


document.getElementById('proveedor-select').addEventListener('change', disableButton);
document.getElementById('documento-select').addEventListener('change', disableButton);
document.getElementById('documento-number').addEventListener('input', disableButton);

document.addEventListener('DOMContentLoaded', function () {
    disableButton();
});

document.getElementById('month').addEventListener('change', function () {
    let selectedMonth = this.value;
    let url = "/ingresos/" + selectedMonth;

    if (selectedMonth == "") {
        alert('Fecha no valida.');
    } else {
        window.location.href = url;
    }

});

document.getElementById('month').addEventListener('keydown', function (event) {
    // Evita que la acci贸n de borrado ocurra si se presiona Backspace o Delete
    if (event.key === 'Backspace' || event.key === 'Delete') {
        event.preventDefault();
    }
});

// Validacin para el cambio de estado Devolucin -> Nuevo
document.querySelector('form[action$="updateregistro"]').addEventListener('submit', function (e) {
    let stateSelect = document.getElementById('state-modal-detail');
    let observacion = document.getElementById('obs-modal-detail');
    let originalState = stateSelect.dataset.originalState;

    if (originalState === 'DEVOLUCION' && stateSelect.value === 'NUEVO') {
        if (observacion.value.trim() === "") {
            e.preventDefault();
            Swal.fire({
                title: "Atencin",
                text: "Debes llenar obligatoriamente las observaciones para pasar una devolucin a estado Nuevo.",
                icon: "warning",
                confirmButtonText: "Entendido",
                confirmButtonColor: "#00b1b9"
            });
        }
    }
});

// Manejador para el checkbox de fallo de entrega
document.getElementById("check-fallo-entrega").addEventListener("change", function() {
    let obs = document.getElementById("obs-modal-detail");
    if (this.checked) {
        if (obs.value.trim() !== "") {
            obs.value += " - Fallo de entrega";
        } else {
            obs.value = "Fallo de entrega";
        }
    } else {
        obs.value = obs.value.replace(" - Fallo de entrega", "").replace("Fallo de entrega", "").trim();
    }
});
