function searchPublicacion(inputElement) {
    let query = inputElement.value;

    function handleClickOutside(event) {
        let suggestions = document.getElementById('suggestions-sku');
        if (!suggestions.contains(event.target) && event.target !== inputElement) {
            suggestions.innerHTML = ''; // Limpiar sugerencias si se hace clic fuera del input
            hiddenBody.style.display = 'none';
        }
    }

    // Agregar el manejador de clics al documento
    document.removeEventListener('click', handleClickOutside);
    document.addEventListener('click', handleClickOutside);

    if (query.length > 2) { // Comenzar la búsqueda después de 3 caracteres
        document.getElementById('hidden-publicacion-sku').value = "";
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/searchpublicacion?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions-sku');
                hiddenBody.style.display = 'block';
                inputElement.style.zIndex = '1000';
                suggestions.innerHTML = '';

                // AUTO-SELECT si hay una coincidencia exacta (ideal para escáneres)
                let exactMatch = data.find(item => item.sku.toUpperCase() === query.toUpperCase());
                if (exactMatch) {
                    document.getElementById('hidden-publicacion-sku').value = exactMatch.idPublicacion;
                    document.getElementById('hidden-publicacion-precio').value = exactMatch.precio;
                    validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
                    validateIconSku.classList.add('bi-check-circle', 'text-success');
                    applySkuToUnassignedItems(exactMatch.idPublicacion, exactMatch.sku, exactMatch.precio);
                    validateSubmit();
                } else {
                    document.getElementById('hidden-publicacion-sku').value = "";
                    validateIconSku.classList.add('bi-exclamation-circle', 'text-danger');
                    validateIconSku.classList.remove('bi-check-circle', 'text-success');
                }

                data.forEach(item => {
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'pe-0');
                    li.classList.add('hover-sistema-uno', 'text-truncate');
                    li.style.cursor = "pointer";

                    let divRow = document.createElement('div');
                    divRow.classList.add('row', 'w-100');

                    let colSerie = document.createElement('div');
                    colSerie.classList.add('col-md-8');
                    colSerie.textContent = item.sku;

                    let colAlmacen = document.createElement('div');
                    colAlmacen.classList.add('col-md-4', 'text-end');
                    colAlmacen.textContent = item.fechaPublicacion;

                    let colProducto = document.createElement('div');
                    colProducto.classList.add('col-md-12');
                    let smallProducto = document.createElement('em');
                    smallProducto.textContent = item.titulo;
                    smallProducto.style.fontSize = '12px';
                    colProducto.appendChild(smallProducto);

                    if (item.modelo) {
                        let spanModelo = document.createElement('span');
                        spanModelo.classList.add('ms-2', 'badge', 'bg-light', 'text-dark', 'border');
                        spanModelo.textContent = item.modelo;
                        colProducto.appendChild(spanModelo);
                    }

                    divRow.appendChild(colSerie);
                    divRow.appendChild(colAlmacen);
                    divRow.appendChild(colProducto);
                    li.appendChild(divRow);

                    li.addEventListener('click', function () {
                        inputElement.value = item.sku;
                        document.getElementById('hidden-publicacion-sku').value = item
                            .idPublicacion;
                        hiddenBody.style.display = 'none';
                        inputElement.style.zIndex = '1';
                        suggestions.innerHTML = ''; // Limpiar sugerencias después de seleccionar una
                        validateIconSku.classList.remove('bi-exclamation-circle', 'text-danger');
                        validateIconSku.classList.add('bi-check-circle', 'text-success');
                        document.getElementById('hidden-publicacion-precio').value = item.precio;
                        applySkuToUnassignedItems(item.idPublicacion, item.sku, item.precio);
                        validateSubmit();
                    });

                    suggestions.appendChild(li);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions-sku').innerHTML = ''; // Limpiar si hay menos de 3 caracteres
        document.getElementById('hidden-publicacion-sku').value = "";
        document.getElementById('hidden-publicacion-precio').value = "";
        validateIconSku.classList.add('bi-exclamation-circle', 'text-danger');
        validateIconSku.classList.remove('bi-check-circle', 'text-success');
        hiddenBody.style.display = 'none';
        inputElement.style.zIndex = '1';
    }
}

function applySkuToUnassignedItems(skuId, skuText, skuPrecio, forceAll = false) {
    let hiddenSkus = document.querySelectorAll('.hidden-sku-item');
    let textDisplays = document.querySelectorAll('.sku-text-display');
    let precioInputs = document.querySelectorAll('.input-precio-item');

    hiddenSkus.forEach((hiddenInput, index) => {
        // Solo aplicar si se fuerza, o si el item no tiene un SKU asignado previamente
        let isUnassigned = !hiddenInput.value || hiddenInput.value === 'NULO' || hiddenInput.value === '';

        if (forceAll || isUnassigned) {
            hiddenInput.value = skuId;
            textDisplays[index].innerHTML = '<small><i class="bi bi-tag-fill"></i> SKU Vinculado: <strong>' + skuText + '</strong></small>';
            textDisplays[index].classList.remove('text-danger');
            textDisplays[index].classList.add('text-success');

            if (skuPrecio !== undefined && skuPrecio !== null && skuPrecio !== '' && skuPrecio !== 'null' && !isNaN(parseFloat(skuPrecio))) {
                precioInputs[index].value = skuPrecio;
            } else {
                precioInputs[index].value = precioInputs[index].dataset.precioSoles || '';
            }
        }
    });
    calculateTotalVenta();
}
