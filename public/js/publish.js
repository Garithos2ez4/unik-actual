let searchInput = document.getElementById('search');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        let query = this.value;
        let hiddenBody = document.getElementById('hidden-body');
        if (query.length > 2) { // Comenzar la bsqueda despus de 3 caracteres
            let xhr = new XMLHttpRequest();
            xhr.open('GET', `/searchpublicacion?query=${query}`, true);
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
                        divColProduct.classList.add('col-12', 'col-md-12', 'text-start');
                        let smallProduct = document.createElement('small');
                        smallProduct.textContent = item.titulo;
                        divColProduct.appendChild(smallProduct);

                        let divColSerial = document.createElement('div');
                        divColSerial.classList.add('col-6', 'col-md-6', 'text-secondary');
                        let smallSerial = document.createElement('small');
                        smallSerial.textContent = item.sku;
                        divColSerial.appendChild(smallSerial);

                        let divColDate = document.createElement('div');
                        divColDate.classList.add('col-6', 'col-md-6', 'text-secondary', 'text-end');
                        let smallDate = document.createElement('small');
                        smallDate.textContent = item.fechaPublicacion;
                        divColDate.appendChild(smallDate);

                        li.addEventListener('click', function () {
                            document.getElementById('search').value = item.sku;
                            suggestions.innerHTML = '';
                            ShareId(item.idPublicacion, item.titulo, item.precio, item.estado);
                        });

                        divRow.appendChild(divColProduct);
                        divRow.appendChild(divColSerial);
                        divRow.appendChild(divColDate);
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
}

function hideSuggestions(event) {
    let suggestions = document.getElementById('suggestions');
    let hiddenBody = document.getElementById('hidden-body');
    if (suggestions && hiddenBody) {
        if (!suggestions.contains(event.target) && (!event.target || event.target.id !== 'search')) {
            suggestions.innerHTML = ''; // Oculta las sugerencias
            hiddenBody.style.display = 'none';
        }
    }
}

document.addEventListener('click', hideSuggestions);

function dataModalDetalle(publicacion, sku, estado, usuario, fecha) {
    let modalEl = document.getElementById('detalleModal');
    if (!modalEl) return;
    let myModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    let title = document.getElementById('titlepublicacion-modal-detail');
    let skuModal = document.getElementById('sku-modal-detail');
    let state = document.getElementById('state-modal-detail');
    let user = document.getElementById('user-modal-detail');
    let date = document.getElementById('date-modal-detail');

    if (title) title.textContent = publicacion ?? '';
    if (skuModal) skuModal.textContent = sku ?? '';
    if (state) state.textContent = estado == 1 ? 'Activo' : (estado == 0 ? 'Inactivo' : 'Borrado');
    if (user) user.textContent = usuario ?? '';
    if (date) date.textContent = fecha ?? '';

    myModal.show();
}

function ShareId(id, title, price, estado) {
    let inputId = document.getElementById('hidden-id');
    let inputTextTitle = document.getElementById('title-text');
    let inputNumberPrice = document.getElementById('price-number');
    let selectEstado = document.getElementById('estado-select');
    let modalEl = document.getElementById('estadoModal');
    if (!modalEl) return;

    let modalEstado = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, {
        keyboard: false
    });

    if (inputId) inputId.value = id ?? '';
    if (inputTextTitle) inputTextTitle.value = title ?? '';
    if (inputNumberPrice) inputNumberPrice.value = price ?? '';
    if (selectEstado) selectEstado.value = estado ?? '1';

    modalEstado.show();
}

function editarPublicacionDesdeElem(elem) {
    if (!elem) return;
    let id = elem.getAttribute('data-id');
    let title = elem.getAttribute('data-title');
    let price = elem.getAttribute('data-price');
    let estado = elem.getAttribute('data-estado');
    ShareId(id, title, price, estado);
}

let monthInput = document.getElementById('month');
if (monthInput) {
    monthInput.addEventListener('change', function() {
        let selectedMonth = this.value;
        let url = "/registro-publicaciones/" + selectedMonth;
        
        if (selectedMonth == "") {
            alert('Fecha no valida.');
        } else {
            window.location.href = url;
        }
    });

    monthInput.addEventListener('keydown', function(event) {
        // Evita que la acción de borrado ocurra si se presiona Backspace o Delete
        if (event.key === 'Backspace' || event.key === 'Delete') {
            event.preventDefault();
        }
    });
}