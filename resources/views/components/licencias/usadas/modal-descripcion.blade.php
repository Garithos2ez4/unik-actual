<!-- Modal de Detalles de Licencia -->
<div class="modal fade" id="descripcionModal" tabindex="-1" aria-labelledby="descripcionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-improved">
            <!-- Header del Modal -->
            <div class="modal-header modal-header-improved">
                <h5 class="modal-title modal-title-improved" id="descripcionModalLabel">
                    <i class="bi bi-file-earmark-text"></i>
                    Detalles de Licencia
                </h5>
                <button type="button" class="btn-close btn-close-improved" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Body del Modal -->
            <div class="modal-body modal-body-improved">
                <div class="details-grid">
                    <!-- Clave de Activación -->
                    <div class="detail-card detail-card-clave">
                        <div class="detail-label">
                            <i class="bi bi-key-fill"></i>
                            Clave de Activación
                        </div>
                        <div class="detail-value detail-value-code" id="claveInfo">
                            ---
                        </div>
                    </div>

                    <!-- Equipo -->
                    <div class="detail-card detail-card-equipo">
                        <div class="detail-label">
                            <i class="bi bi-pc-display"></i>
                            Equipo
                        </div>
                        <div class="detail-value" id="equipoInfo">
                            ---
                        </div>
                    </div>

                    <!-- Proveedor -->
                    <div class="detail-card detail-card-proveedor">
                        <div class="detail-label">
                            <i class="bi bi-building"></i>
                            Proveedor
                        </div>
                        <div class="detail-value" id="proveedorInfo">
                            ---
                        </div>
                    </div>

                    <!-- Categoría -->
                    <div class="detail-card detail-card-categoria">
                        <div class="detail-label">
                            <i class="bi bi-tag"></i>
                            Categoría
                        </div>
                        <div class="detail-value" id="categoriaInfo">
                            ---
                        </div>
                    </div>

                    <!-- Descripción - Full Width -->
                    <div class="detail-card detail-card-full">
                        <div class="detail-label">
                            <i class="bi bi-file-text"></i>
                            Descripción
                        </div>
                        <div class="description-box" id="descripcionContenido">
                            Sin descripción disponible.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="modal-footer modal-footer-improved">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Variables de color azul acero */
    .modal-content-improved {
        border: 1px solid #d4dce3;
        border-radius: 14px;
        box-shadow: 0 10px 40px rgba(26, 58, 82, 0.15);
        background-color: #ffffff;
    }

    /* Header del Modal */
    .modal-header-improved {
        background-color: #1a3a52;
        border-bottom: none;
        padding: 1.75rem 2rem;
        border-radius: 14px 14px 0 0;
    }

    .modal-title-improved {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        letter-spacing: -0.3px;
    }

    .modal-title-improved i {
        font-size: 1.25rem;
        opacity: 0.9;
    }

    .btn-close-improved {
        filter: brightness(0) invert(1);
        opacity: 0.7;
    }

    .btn-close-improved:focus {
        opacity: 1;
    }

    /* Body del Modal */
    .modal-body-improved {
        padding: 2rem;
    }

    /* Grid de detalles */
    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .detail-card-full {
        grid-column: 1 / -1;
    }

    /* Card de detalles */
    .detail-card {
        background-color: #f4f6f8;
        border: 1px solid #d4dce3;
        border-radius: 12px;
        padding: 1.25rem;
        transition: all 0.25s ease;
    }

    .detail-card:hover {
        border-color: #2d5a7b;
        box-shadow: 0 4px 12px rgba(26, 58, 82, 0.08);
    }

    /* Label del detalle */
    .detail-label {
        font-weight: 700;
        color: #1a3a52;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-label i {
        font-size: 0.95rem;
    }

    /* Valor del detalle */
    .detail-value {
        color: #1a2332;
        font-size: 0.95rem;
        word-break: break-word;
        line-height: 1.5;
    }

    /* Código monoespaciado */
    .detail-value-code {
        background-color: #eceff2;
        padding: 0.75rem;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        border-left: 3px solid #2d5a7b;
    }

    /* Box de descripción */
    .description-box {
        background-color: #eceff2;
        border: 1px solid #d4dce3;
        border-radius: 10px;
        padding: 1.25rem;
        color: #1a2332;
        font-size: 0.95rem;
        line-height: 1.6;
        max-height: 300px;
        overflow-y: auto;
    }

    .description-box::-webkit-scrollbar {
        width: 6px;
    }

    .description-box::-webkit-scrollbar-track {
        background: #f4f6f8;
        border-radius: 10px;
    }

    .description-box::-webkit-scrollbar-thumb {
        background: #2d5a7b;
        border-radius: 10px;
    }

    .description-box::-webkit-scrollbar-thumb:hover {
        background: #1a3a52;
    }

    /* Footer del Modal */
    .modal-footer-improved {
        border-top: 1px solid #d4dce3;
        padding: 1.5rem 2rem;
        background-color: #f4f6f8;
        border-radius: 0 0 14px 14px;
    }

    .modal-footer-improved .btn {
        border-radius: 10px;
        font-weight: 700;
        padding: 0.75rem 1.5rem;
        transition: all 0.25s ease;
    }

    .modal-footer-improved .btn-secondary {
        background-color: #5a6f7d;
        border: none;
        color: white;
    }

    .modal-footer-improved .btn-secondary:hover {
        background-color: #2d5a7b;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(26, 58, 82, 0.2);
    }

    .modal-footer-improved .btn i {
        margin-right: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .details-grid {
            grid-template-columns: 1fr;
        }

        .detail-card-full {
            grid-column: 1;
        }

        .modal-body-improved {
            padding: 1.5rem;
        }

        .modal-header-improved {
            padding: 1.5rem;
        }

        .modal-footer-improved {
            padding: 1rem 1.5rem;
        }

        .modal-title-improved {
            font-size: 1rem;
        }
    }

    /* Animación de entrada */
    .modal.fade .modal-content {
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
