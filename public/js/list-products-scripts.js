let clickPrices = false;

document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('toggle-status-btn')) {
            let btn = e.target;
            let idProducto = btn.dataset.id;
            let isChecked = btn.checked;
            let label = document.querySelector('.toggle-status-label-' + idProducto);

            fetch(window.APP_DATA.toggleStatusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.APP_DATA.csrfToken
                },
                body: JSON.stringify({
                    idProducto: idProducto,
                    status: isChecked
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (label) label.textContent = data.estado === 'DISPONIBLE' ? 'Disponible' : 'Descontinuado';
                    } else {
                        alert(data.message || 'Error al actualizar el estado');
                        btn.checked = !isChecked;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al actualizar el estado.');
                    btn.checked = !isChecked;
                });
        }
    });
});


function changePriceList() {
    let prices = document.querySelectorAll('.price-list-product');
    if (clickPrices) {
        prices.forEach(function (x) {
            let preciesito = x.dataset.value;
            x.textContent = '$' + preciesito;
        });
        clickPrices = false;
    } else {
        prices.forEach(function (x) {
            let preciesito = x.dataset.value;
            x.textContent = 'S/.' + (preciesito * window.APP_DATA.tc).toFixed(2);
        });
        clickPrices = true;
    }

}

function mostrarImg(id) {
    var imgDiv = document.getElementById('img-' + id);
    imgDiv.style.display = 'block';
}

function ocultarImg(id) {
    var imgDiv = document.getElementById('img-' + id);
    imgDiv.style.display = 'none';
}


