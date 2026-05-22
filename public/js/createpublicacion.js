function validateData() {
    let titulo = document.getElementById('titulo-public').value;
    let sku = document.getElementById('sku-public').value;
    let producto = document.getElementById('search').value;
    let cuenta = document.getElementById('cuenta-public').value;
    let fecha = document.getElementById('fecha-public').value;
    let precio = document.getElementById('precio-public').value;
    let disabled = false;

    if (titulo == '') {
        disabled = true;
    }

    if (sku == '') {
        disabled = true;
    }

    if (producto == '') {
        disabled = true;
    }

    if (cuenta == '') {
        disabled = true;
    }

    if (fecha == '') {
        disabled = true;
    }

    if (precio == '') {
        disabled = true;
    }

    return disabled;
}

function dissableButton() {
    let btnSave = document.getElementById('btnSave');
    if (validateData()) {
        btnSave.classList.add('disabled');
    } else {
        btnSave.classList.remove('disabled');
    }

}

document.addEventListener('DOMContentLoaded', function () {
    dissableButton();
});

document.getElementById('titulo-public').addEventListener('input', dissableButton);
document.getElementById('sku-public').addEventListener('input', dissableButton);
document.getElementById('cuenta-public').addEventListener('input', dissableButton);
document.getElementById('fecha-public').addEventListener('input', dissableButton);
document.getElementById('precio-public').addEventListener('input', dissableButton);
document.getElementById('search').addEventListener('input', dissableButton);

document.getElementById('search').addEventListener('input', function () {
    let query = this.value;

    if (query.length > 2) { // Comenzar la búsqueda después de 3 caracteres
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/productos/searchmodelproduct?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions');
                let inputElement = document.getElementById('search');
                suggestions.innerHTML = '';

                // Handle click outside to close the suggestions
                function handleClickOutside(event) {
                    if (!suggestions.contains(event.target) && event.target !== inputElement) {
                        suggestions.innerHTML = '';
                        document.removeEventListener('click', handleClickOutside);
                    }
                }
                document.addEventListener('click', handleClickOutside);

                data.slice(0, 10).forEach(item => {
                    let li = document.createElement('li');
                    li.textContent = item.modelo;
                    li.classList.add('list-group-item');
                    li.classList.add('hover-sistema-uno');
                    li.style.cursor = "pointer";

                    li.addEventListener('click', function () {
                        inputElement.value = this.textContent;
                        document.getElementById('hidden-product').value = item.idProducto;
                        suggestions.innerHTML = ''; // Limpiar sugerencias después de seleccionar una
                    });

                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions').innerHTML = ''; // Limpiar si hay menos de 3 caracteres
    }
});

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
                        dissableButton();
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