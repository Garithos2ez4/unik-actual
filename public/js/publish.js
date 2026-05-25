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
    if (!suggestions.contains(event.target) && event.target.id !== 'search') {
        suggestions.innerHTML = ''; // Oculta las sugerencias
        hiddenBody.style.display = 'none';
    }
}

document.addEventListener('click', hideSuggestions);

function dataModalDetalle(publicacion, sku, estado, usuario, fecha) {
    let myModal = new bootstrap.Modal(document.getElementById('detalleModal'));
    let title = document.getElementById('titlepublicacion-modal-detail');
    let skuModal = document.getElementById('sku-modal-detail');
    let state = document.getElementById('state-modal-detail');
    let user = document.getElementById('user-modal-detail');
    let date = document.getElementById('date-modal-detail');

    title.textContent = publicacion;
    skuModal.textContent = sku;
    state.textContent = estado == 1 ? 'Activo' : (estado == 0 ? 'Inactivo' : 'Borrado');
    user.textContent = usuario;
    date.textContent = fecha;

    myModal.show();
}

function ShareId(id, title, price, estado) {
    let inputId = document.getElementById('hidden-id');
    let inputTextTitle = document.getElementById('title-text');
    let inputNumberPrice = document.getElementById('price-number');
    let selectEstado = document.getElementById('estado-select');

    let modalEstado = new bootstrap.Modal(document.getElementById('estadoModal'), {
        keyboard: false
    });

    inputId.value = id;
    inputTextTitle.value = title;
    inputNumberPrice.value = price;
    selectEstado.value = estado;
    modalEstado.show();
}

document.getElementById('month').addEventListener('change', function() {
    let selectedMonth = this.value;
    let url = "/registro-publicaciones/" + selectedMonth;
    
    if(selectedMonth == ""){
        alert('Fecha no valida.');
    }else{
        window.location.href = url;
    }
    
});

document.getElementById('month').addEventListener('keydown', function(event) {
    // Evita que la acci贸n de borrado ocurra si se presiona Backspace o Delete
    if (event.key === 'Backspace' || event.key === 'Delete') {
        event.preventDefault();
    }
});