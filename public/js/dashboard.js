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
