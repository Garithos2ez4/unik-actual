<!-- Modal Historial Precio Tienda -->
<div class="modal fade" id="modalHistorialPrecioTienda" tabindex="-1" aria-labelledby="modalHistorialPrecioTiendaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalHistorialPrecioTiendaLabel">
                    <i class="bi bi-clock-history"></i> Historial de Precio Tienda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="bodyHistorialPrecioTienda">
                <div class="text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verHistorialPrecioTienda(idProducto) {
    let modalElement = document.getElementById('modalHistorialPrecioTienda');
    let modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    let body = document.getElementById('bodyHistorialPrecioTienda');
    body.innerHTML = '<div class="text-center"><div class="spinner-border text-success" role="status"></div></div>';
    modal.show();

    fetch('/productos/historial-precio-tienda/' + idProducto)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                body.innerHTML = '<p class="text-center text-muted">No hay cambios registrados aún.</p>';
                return;
            }
            let html = '<table class="table table-sm table-striped">';
            html += '<thead><tr><th>Fecha</th><th>Precio Anterior</th><th>Precio Nuevo</th><th>Usuario</th></tr></thead><tbody>';
            data.forEach(item => {
                html += '<tr>';
                html += '<td>' + item.fecha + '</td>';
                html += '<td class="text-danger">S/. ' + parseFloat(item.precioAnterior).toFixed(2) + '</td>';
                html += '<td class="text-success">S/. ' + parseFloat(item.precioNuevo).toFixed(2) + '</td>';
                html += '<td>' + (item.usuario || '-') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            body.innerHTML = html;
        })
        .catch(() => {
            body.innerHTML = '<p class="text-center text-danger">Error al cargar el historial.</p>';
        });
}
</script>
