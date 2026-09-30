<!-- MODAL DE CONFIRMACIÓN PARA CERRAR VENTANA -->
<div class="modal fade" id="modalConfirmarCerrar" tabindex="-1" aria-labelledby="modalConfirmarCerrarLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white py-3 rounded-top-4">
                <h5 class="modal-title fs-5 fw-bold" id="modalConfirmarCerrarLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmar Cierre
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle" style="width: 70px; height: 70px;">
                        <i class="bi bi-x-octagon-fill" style="font-size: 2.2rem;"></i>
                    </span>
                </div>
                <h5 class="fw-bold mb-2">¿Quieres confirmar cerrar esta ventana?</h5>
                <p class="text-muted mb-0">Si cierras esta ventana, los productos seleccionados y cualquier cambio no registrado se perderán.</p>
            </div>
            <div class="modal-footer bg-light border-0 py-3 rounded-bottom-4 justify-content-between">
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-return-left me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-danger px-4 fw-semibold" id="btn-ejecutar-cerrar-ventana">
                    <i class="bi bi-x-circle me-1"></i> Sí, cerrar ventana
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnCerrar = document.getElementById('btn-ejecutar-cerrar-ventana');
        if (btnCerrar) {
            btnCerrar.addEventListener('click', function () {
                window.isClosingConfirmed = true;
                // Intento cerrar la pestaña/ventana actual
                window.close();
                // Si el navegador bloquea window.close() por no ser un popup directo:
                setTimeout(function () {
                    window.location.href = "{{ route('egresos', [date('Y-m')]) }}";
                }, 200);
            });
        }
    });
</script>
