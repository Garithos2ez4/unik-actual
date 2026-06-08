<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById('tienda-data-container');
        
        // Obtenemos los filtros de la URL (si existen)
        const params = new URLSearchParams(window.location.search);
        const endpoint = `{{ route('dashboard.analitica.tienda.data') }}?${params.toString()}`;

        fetch(endpoint)
            .then(response => {
                if (!response.ok) throw new Error('Error en la petición');
                return response.text();
            })
            .then(html => {
                container.innerHTML = html;
                const scripts = container.querySelectorAll("script");
                scripts.forEach(oldScript => {
                    const newScript = document.createElement("script");
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            })
            .catch(error => {
                console.error('Error cargando datos de tienda:', error);
                container.innerHTML = `
                    <div class="alert alert-danger text-center mt-4">
                        <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                        Ocurrió un error al cargar las métricas. Por favor, recarga la página.
                    </div>`;
            });
    });
</script>
