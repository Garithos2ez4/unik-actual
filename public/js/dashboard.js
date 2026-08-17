function loadProducts() {
    let loader = document.getElementById('loader-list-products');
    let divContainer = document.getElementById('dashboard-content');
    let url =  document.getElementById('dashboardurl').value;
    const fullUrl = `${url}?query=1`;

    fetch(fullUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            divContainer.innerHTML = data;
        })
        .catch(error => console.error('Error al cargar los productos:', error));
}

window.onload = function() {
    loadProducts();
    setInterval(loadProducts, 600000); // 10 minutos (600,000 ms) en lugar de 60 segundos para no saturar el servidor
}

window.modalsQueue = [];
window.processModalQueue = function() {
    if (window.modalsQueue.length > 0) {
        var modalId = window.modalsQueue.shift();
        var modalEl = document.getElementById(modalId);
        if (modalEl) {
            var modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            modalEl.addEventListener('hidden.bs.modal', function () {
                // Esperar un poquito antes de abrir el siguiente para que la animación termine
                setTimeout(window.processModalQueue, 400);
            }, { once: true });
            modal.show();
        } else {
            window.processModalQueue();
        }
    }
};

document.addEventListener("DOMContentLoaded", function() {
    // Iniciar la cola después de un breve delay
    setTimeout(window.processModalQueue, 500);
});
