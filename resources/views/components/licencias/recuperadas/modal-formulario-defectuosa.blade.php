<link rel="stylesheet" href="{{ asset(path:'css/licencias/modal-recuperadas.css') }}">
<div class="modal fade" id="modalLicenciaDefectuosa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formLicenciaDefectuosa" method="POST">
            @csrf
            <input type="hidden" name="idRecuperada" id="inputIdRecuperadaDefectuosa">
            <input type="hidden" name="nuevo_estado" value="DEFECTUOSA">
            <input type="hidden" name="serial_recuperada" id="inputSerialRecuperadaDefectuosa">

            <div class="modal-content defectuosa-modal">
                <div class="modal-header defectuosa-header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Marcar Licencia como Defectuosa</h5>
                            <small class="opacity-75">Registrar licencia con problemas</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body defectuosa-body">
                    <div class="alert-warning-box">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div>
                            <strong>Atención:</strong> Esta acción marcará la licencia como defectuosa y será retirada del inventario disponible.
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-key-fill me-2"></i>
                            Información de la Licencia
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-container">
                                    <label class="input-label">
                                        <i class="bi bi-shield-lock me-1"></i>
                                        Clave de Activación
                                    </label>
                                    <input type="text" name="clave_key" class="form-input" readonly required>
                                    <div class="input-icon">
                                        <i class="bi bi-lock-fill"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-container">
                                    <label class="input-label">
                                        <i class="bi bi-cart-check me-1"></i>
                                        Orden de Compra
                                    </label>
                                    <input type="text" name="orden" class="form-input" readonly>
                                    <div class="input-icon">
                                        <i class="bi bi-lock-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-building me-2"></i>
                            Información del Proveedor
                        </h6>
                        <div class="input-container">
                            <label class="input-label">
                                <i class="bi bi-person-badge me-1"></i>
                                Proveedor
                            </label>
                            <input type="text" name="proveedor" class="form-input" readonly>
                            <div class="input-icon">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-ticket-detailed me-2"></i>
                            Información del Incidente
                        </h6>
                        <div class="input-container">
                            <label class="input-label required">
                                <i class="bi bi-hash me-1"></i>
                                Número de Ticket
                            </label>
                            <input type="text" name="numero_ticket" class="form-input">
                            <div class="input-icon">
                                <i class="bi bi-asterisk"></i>
                            </div>
                            <small class="input-hint">Ticket de soporte</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer defectuosa-footer">
                    <button type="button" class="btn-modal btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-modal btn-defectuosa">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Marcar como Defectuosa
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
