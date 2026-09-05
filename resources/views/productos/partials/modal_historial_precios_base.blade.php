<!-- Modal Base para Historial de Precios Dinámico -->
<div class="modal fade" id="modalHistorialPreciosDinamico" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-graph-up-arrow"></i> Historial de Precios de Compra</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalHistorialPreciosBody">
        <div class="text-center py-4">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="mt-2 text-muted">Cargando historial...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-ver-historial-precios');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();

            const idProducto = btn.getAttribute('data-id');
            const modalBody = document.getElementById('modalHistorialPreciosBody');

            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="mt-2 text-muted">Cargando historial...</p>
                </div>`;

            if (typeof bootstrap !== 'undefined') {
                const modalElement = document.getElementById('modalHistorialPreciosDinamico');
                const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modalInstance.show();
            } else if (typeof $ !== 'undefined') {
                $('#modalHistorialPreciosDinamico').modal('show');
            }

            fetch(`/producto/${idProducto}/historial-precios-html`)
                .then(response => response.json())
                .then(data => {
                    modalBody.innerHTML = data.html;
                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger m-3">Error al cargar el historial de precios.</div>`;
                    console.error('Error cargando historial:', error);
                });
        }
    });
})();

window.loadHistorialYear = function(year, idProducto) {
    const modalBody = document.getElementById('modalHistorialPreciosBody');
    if(!modalBody) return;
    
    modalBody.style.opacity = '0.5';
    
    fetch(`/producto/${idProducto}/historial-precios-html?year=${year}`)
        .then(response => response.json())
        .then(data => {
            modalBody.innerHTML = data.html;
            modalBody.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.style.opacity = '1';
        });
};
</script>
