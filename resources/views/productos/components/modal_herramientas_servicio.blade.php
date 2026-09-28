<!-- Modal Herramientas de Servicio -->
<div class="modal fade" id="modalServicios" tabindex="-1" aria-labelledby="modalServiciosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalServiciosLabel"><i class="bi bi-tools"></i> Administrar Herramientas de Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Aquí puedes marcar series específicas de este producto para que sean tratadas como herramientas de taller/servicio.
                    Las series marcadas aquí no descontarán inventario cuando se cobren servicios con ellas.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>N° Serie</th>
                                <th>Almacén</th>
                                <th>Estado Actual</th>
                                <th class="text-center">Herramienta de Servicio</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-series-servicios">
                            <tr>
                                <td colspan="4" class="text-center">Cargando series...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
