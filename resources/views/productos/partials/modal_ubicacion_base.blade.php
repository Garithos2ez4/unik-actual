<!-- Modal Base para Ubicación Dinámica -->
<div class="modal fade" id="modalUbicacionDinamico" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-geo-alt-fill"></i> Ubicación Exacta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalUbicacionBody">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Buscando en almacenes...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Asegurarnos de que el script se ejecute
(function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-ver-ubicacion');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            
            const idProducto = btn.getAttribute('data-id');
            const modalBody = document.getElementById('modalUbicacionBody');
            
            // HTML de Carga
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Buscando en almacenes...</p>
                </div>`;
            
            // Mostrar Modal (soportando tanto si Bootstrap global está disponible como jQuery)
            if (typeof bootstrap !== 'undefined') {
                const modalElement = document.getElementById('modalUbicacionDinamico');
                const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modalInstance.show();
            } else if (typeof $ !== 'undefined') {
                $('#modalUbicacionDinamico').modal('show');
            }
            
            // Petición AJAX
            fetch(`/producto/${idProducto}/ubicacion-html`)
                .then(response => response.json())
                .then(data => {
                    modalBody.innerHTML = data.html;
                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger m-3">Error al cargar la información de ubicación.</div>`;
                    console.error('Error cargando ubicación:', error);
                });
        }
    });
})();
</script>
