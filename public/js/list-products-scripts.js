let clickPrices = false;

function initToggleStatus() {
    if (window._toggleStatusListenerAttached) return;
    window._toggleStatusListenerAttached = true;

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('toggle-status-btn')) {
            let btn = e.target;
            let idProducto = btn.dataset.id;
            let isChecked = btn.checked;
            let label = document.querySelector('.toggle-status-label-' + idProducto);

            const url = (window.APP_DATA && window.APP_DATA.toggleStatusUrl)
                ? window.APP_DATA.toggleStatusUrl
                : '/productos/toggle-status';

            const metaCsrf = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = (window.APP_DATA && window.APP_DATA.csrfToken)
                ? window.APP_DATA.csrfToken
                : (metaCsrf ? metaCsrf.getAttribute('content') : '');

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    idProducto: idProducto,
                    status: isChecked
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (label) {
                            if (data.estado === 'DISPONIBLE') {
                                label.textContent = 'Disponible';
                            } else if (data.estado === 'AGOTADO') {
                                label.textContent = 'Agotado';
                            } else {
                                label.textContent = 'Descontinuado';
                            }
                        }
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
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToggleStatus);
} else {
    initToggleStatus();
}


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


