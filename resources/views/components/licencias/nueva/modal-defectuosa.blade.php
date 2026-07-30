<div class="modal fade" id="modalLicenciaDefectuosa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formLicenciaDefectuosa" method="POST">
            @csrf
            <input type="hidden" name="nuevo_estado" value="DEFECTUOSA">

            <div class="modal-content defective-modal">
                <div class="modal-header defective-header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Licencia Defectuosa</h5>
                            <small class="opacity-75">Control de Calidad - Inventario</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body defective-body">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-key-fill me-2"></i>
                            Clave de Activación <span class="text-danger">*</span>
                        </label>
                        <div class="input-container">
                            <input type="text"
                                   name="clave_key"
                                   class="form-control defective-input"
                                   placeholder="Ingrese la clave de activación"
                                   >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-receipt me-2"></i>
                            Orden de Compra
                        </label>
                        <div class="input-container">
                            <input type="text"
                                   name="orden"
                                   class="form-control defective-input readonly-input"
                                   placeholder="No asignada"
                                   readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-ticket-perforated me-2"></i>
                            Número de Ticket
                        </label>
                        <div class="input-container">
                            <input type="text"
                                   name="numero_ticket"
                                   class="form-control defective-input"
                                   placeholder="Ej: TK-2024-001">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-building me-2"></i>
                            Proveedor
                        </label>
                        <div class="input-container">
                            <input type="text"
                                   name="razSocialProveedor"
                                   class="form-control defective-input readonly-input"
                                   placeholder="Sin proveedor asignado"
                                   readonly>
                        </div>
                    </div>
                </div>

                <div class="modal-footer defective-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning defective-btn">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Marcar como Defectuosa
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
