<link rel="stylesheet" href="{{ asset(path:'css/licencias/modal-recuperadas.css') }}">
<div class="modal fade" id="modalUsarLicencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formUsarLicencia" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="idRecuperada" id="inputIdRecuperadaUsada">
            <input type="hidden" name="nuevo_estado" value="USADA">
            <input type="hidden" name="serial_recuperada" id="inputSerialRecuperada">

            <div class="modal-content usar-modal">
                <div class="modal-header usar-header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Marcar Licencia como Usada</h5>
                            <small class="opacity-75">Asignar licencia recuperada a equipo</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body usar-body">
                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-key-fill me-2"></i>
                            Información de la Licencia
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
                                        <i class="bi bi-shield-lock me-1"></i>
                                        Clave de Activación
                                    </label>
                                    <input type="text" name="clave_key" class="form-input" readonly>
                                    <div class="input-icon">
                                        <i class="bi bi-lock-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-pc-display-horizontal me-2"></i>
                            Datos del Equipo
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-container">
                                    <label class="input-label required">
                                        <i class="bi bi-pc me-1"></i>
                                        Nombre del Equipo
                                    </label>
                                    <input type="text" name="equipo" class="form-input">
                                    <div class="input-icon">
                                        <i class="bi bi-asterisk"></i>
                                    </div>
                                    <small class="input-hint">Ej: PC-OFICINA-01</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-container">
                                    <label class="input-label required">
                                        <i class="bi bi-tag me-1"></i>
                                        Tipo de Equipo
                                    </label>
                                    <input type="text" name="tipo_equipo" class="form-input" >
                                    <div class="input-icon">
                                        <i class="bi bi-asterisk"></i>
                                    </div>
                                    <small class="input-hint">Ej: Desktop, Laptop, Server</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="input-container">
                                    <label class="input-label required">
                                        <i class="bi bi-fingerprint me-1"></i>
                                        Serial del Equipo
                                    </label>
                                    <input type="text" name="serial_equipo" class="form-input" >
                                    <div class="input-icon">
                                        <i class="bi bi-asterisk"></i>
                                    </div>
                                    <small class="input-hint">Identificador único del equipo</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-card-text me-2"></i>
                            Información Adicional
                        </h6>
                        <div class="input-container">
                            <label class="input-label">
                                <i class="bi bi-file-text me-1"></i>
                                Descripción / Notas
                            </label>
                            <textarea name="descripcion" class="form-textarea" rows="3"
                                placeholder="Agregar comentarios o notas adicionales..."></textarea>
                            <small class="input-hint">Opcional: Información adicional sobre la asignación</small>
                        </div>
                    </div>

                    <div class="info-section">
                        <h6 class="section-title">
                            <i class="bi bi-paperclip me-2"></i>
                            Archivo de Licencia
                        </h6>
                        <div class="file-upload-container">
                            <input type="file" name="archivo" id="archivoUsada" class="file-input"
                                accept=".rcf,.txt,.pdf">
                            <label for="archivoUsada" class="file-label">
                                <div class="file-icon">
                                    <i class="bi bi-cloud-upload"></i>
                                </div>
                                <div class="file-text">
                                    <span class="file-title">Seleccionar archivo</span>
                                    <span class="file-hint">Archivos .rcf, .txt, .pdf (Opcional)</span>
                                </div>
                            </label>
                            <div class="file-selected" id="fileSelectedUsada" style="display: none;">
                                <i class="bi bi-file-earmark-check me-2"></i>
                                <span class="file-name"></span>
                                <button type="button" class="file-remove">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer usar-footer">
                    <button type="button" class="btn-modal btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-modal btn-usar">
                        <i class="bi bi-check-circle me-2"></i>
                        Marcar como Usada
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
