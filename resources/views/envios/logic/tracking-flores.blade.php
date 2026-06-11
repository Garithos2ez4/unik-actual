<!-- Modal Tracking Flores -->
<div class="modal fade" id="trackingFloresModal" tabindex="-1" aria-labelledby="trackingFloresModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title fw-bold" id="trackingFloresModalLabel"><i class="bi bi-geo-alt-fill me-2"></i>Tracking Flores</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="text-center mb-3">
                    <h6 class="text-muted mb-0">Guía: <span id="trackingGuia" class="fw-bold text-dark"></span></h6>
                </div>
                
                <div id="trackingLoading" class="text-center py-4">
                    <div class="spinner-border text-info" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted small">Consultando servidores de Flores...</p>
                </div>

                <div id="trackingError" class="alert alert-danger d-none text-center" role="alert">
                </div>

                <div id="trackingTimeline" class="d-none">
                    <ul class="list-group list-group-flush shadow-sm rounded border-0" id="trackingList">
                    </ul>
                </div>
            </div>
            <div class="modal-footer bg-white border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function rastrearFlores(idEnvio, guia) {
        document.getElementById('trackingGuia').innerText = guia;
        document.getElementById('trackingLoading').classList.remove('d-none');
        document.getElementById('trackingError').classList.add('d-none');
        document.getElementById('trackingTimeline').classList.add('d-none');
        
        const modal = new bootstrap.Modal(document.getElementById('trackingFloresModal'));
        modal.show();

        fetch(`/envios-provincias/tracking-flores/${idEnvio}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('trackingLoading').classList.add('d-none');
                
                if (data.success) {
                    const list = document.getElementById('trackingList');
                    list.innerHTML = '';
                    
                    if (data.data && data.data.length > 0) {
                        data.data.forEach((item, index) => {
                            const isLast = index === data.data.length - 1;
                            const li = document.createElement('li');
                            li.className = 'list-group-item border-0 p-3 mb-2 bg-white rounded shadow-sm';
                            li.innerHTML = `
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-bold ${isLast ? 'text-info' : 'text-dark'}">${item.Estado || 'Estado registrado'}</h6>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i>${item.Fech_programacion ? item.Fech_programacion + ' ' + (item.Hora_embarca || '') : '-'}</small>
                                </div>
                                <p class="mb-1 text-muted small"><i class="bi bi-geo-alt me-1"></i>Destino: ${item.Destino || '-'}</p>
                                <p class="mb-0 text-muted small"><i class="bi bi-box me-1"></i>Detalle: ${item.Descripcion || '-'} (${item.Cantidad || '1'} bultos)</p>
                            `;
                            list.appendChild(li);
                        });
                        document.getElementById('trackingTimeline').classList.remove('d-none');
                    } else {
                        document.getElementById('trackingError').innerText = 'No se encontraron movimientos para esta guía. Verifica que sea correcta o intenta más tarde.';
                        document.getElementById('trackingError').classList.remove('d-none');
                    }
                } else {
                    document.getElementById('trackingError').innerText = data.message || 'Error al obtener el tracking';
                    document.getElementById('trackingError').classList.remove('d-none');
                }
            })
            .catch(error => {
                document.getElementById('trackingLoading').classList.add('d-none');
                document.getElementById('trackingError').innerText = 'Error de conexión con el servidor.';
                document.getElementById('trackingError').classList.remove('d-none');
                console.error('Error tracking flores:', error);
            });
    }
</script>
