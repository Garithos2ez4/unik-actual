<!-- Modal Detalle de Devolución -->
<div class="modal fade" id="modalDetalleDevolucion" aria-hidden="true" aria-labelledby="modalDetalleDevolucionLabel" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalDetalleDevolucionLabel"><i class="bi bi-info-circle me-2"></i> Detalle de Devolución</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 bg-light rounded border">
                        <small class="text-secondary fw-bold text-uppercase d-block mb-1">Producto</small>
                        <strong id="detDevProducto" class="fs-5 text-dark"></strong>
                        <div class="text-muted mt-1" style="font-size: 0.85rem;">SKU: <span id="detDevSku"></span></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <small class="text-secondary fw-bold text-uppercase d-block mb-1">N° Orden</small>
                                <span id="detDevOrden" class="fw-bold fs-6"></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <small class="text-secondary fw-bold text-uppercase d-block mb-1">Fecha de Devolución</small>
                                <span id="detDevFecha" class="fw-bold fs-6"></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border border-danger">
                        <small class="text-danger fw-bold text-uppercase d-block mb-1"><i class="bi bi-question-circle"></i> Razón / Estado</small>
                        <span id="detDevRazon" class="fw-bold text-dark text-uppercase"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" data-bs-target="#modalDevolucionesHoy" data-bs-toggle="modal">Volver a la lista</button>
            </div>
        </div>
    </div>
</div>
