<link rel="stylesheet" href="{{ asset(path:'css/licencias/modal-defectuosas.css') }}">
<div class="modal fade" id="modalLicenciaRecuperada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formLicenciaRecuperada" method="POST">
            @csrf
            <input type="hidden" name="nuevo_estado" value="RECUPERADA">
            <input type="hidden" name="idProveedor">

            <div class="modal-content recuperada-modal">
                <div class="modal-header recuperada-header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="bi bi-arrow-clockwise"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Marcar Licencia como Recuperada</h5>
                            <small class="opacity-75">Registrar nueva licencia recuperada del proveedor</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body recuperada-body">
                    <div class="info-banner">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <div>
                            <strong>Información:</strong> Esta licencia reemplaza a una defectuosa previamente reportada.
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Licencia Defectuosa Original
                        </h6>
                        <div class="row g-3">
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
                            <div class="col-md-6">
                                <div class="input-container">
                                    <label class="input-label">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Serial Defectuosa
                                    </label>
                                    <input type="text" name="serial_defectuosa" class="form-input serial-defectuosa" readonly>
                                    <div class="input-icon">
                                        <i class="bi bi-lock-fill"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-container">
                                    <label class="input-label">
                                        <i class="bi bi-hash me-1"></i>
                                        Número de Ticket
                                    </label>
                                    <input type="text" name="numero_ticket" class="form-input" readonly>
                                    <div class="input-icon">
                                        <i class="bi bi-lock-fill"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-container">
                                    <label class="input-label">
                                        <i class="bi bi-building me-1"></i>
                                        Proveedor
                                    </label>
                                    <input type="text" name="razonSocialProveedor" class="form-input" readonly>
                                    <div class="input-icon">
                                        <i class="bi bi-lock-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-key-fill me-2"></i>
                            Nueva Licencia Recuperada
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="input-container">
                                    <label class="input-label required">
                                        <i class="bi bi-shield-check me-1"></i>
                                        Serial Recuperada
                                    </label>
                                    <input
                                        type="text"
                                        name="serial_recuperada"
                                        id="serialRecuperadaInput"
                                        class="form-input"
                                        required
                                        placeholder="Ej: XXXXX-XXXXX-XXXXX-XXXXX">
                                    <div class="input-icon">
                                        <i class="bi bi-asterisk"></i>
                                    </div>
                                    <small class="input-hint">Nuevo serial proporcionado por el proveedor</small>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="verification-status" id="verificationStatus" style="display: none;">
                        <div class="verification-spinner">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                <span class="visually-hidden">Verificando...</span>
                            </div>
                            <span>Verificando serial...</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer recuperada-footer">
                    <button type="button" class="btn-modal btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-modal btn-recuperada" id="btnSubmitRecuperada">
                        <i class="bi bi-check-circle me-2"></i>
                        Guardar Licencia
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
