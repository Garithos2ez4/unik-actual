<div class="modal fade" id="modalAlertasPrecio" tabindex="-1" aria-labelledby="modalAlertasPrecioLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalAlertasPrecioLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ¡Alerta de Precios Competitivos en Falabella!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="alert alert-warning border-warning shadow-sm">
                    <strong>Atención:</strong> El bot ha detectado que tus precios en Falabella tienen una diferencia de 5% o más respecto a la competencia. ¡Toma acción para no perder ventas o margen!
                </div>

                <div class="table-responsive bg-white rounded shadow-sm">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Modelo</th>
                                <th>Mi Precio (S/)</th>
                                <th>Mejor Competidor</th>
                                <th>Precio Competencia (S/)</th>
                                <th>Sugerencia</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alertas as $alerta)
                            <tr id="alerta-row-{{ $alerta->id }}">
                                <td class="fw-bold">{{ $alerta->modelo }}</td>
                                <td>
                                    <span class="badge bg-primary fs-6">S/ {{ number_format($alerta->mi_precio, 2) }}</span>
                                </td>
                                <td>
                                    <span class="text-secondary fw-semibold">{{ $alerta->competidor }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-success fs-6">S/ {{ number_format($alerta->precio_competidor, 2) }}</span>
                                </td>
                                <td>
                                    @if($alerta->sugerencia == 'BAJAR')
                                    <span class="text-danger fw-bold"><i class="bi bi-arrow-down-circle-fill"></i> ¡Estás caro!</span>
                                    <small class="d-block text-muted">{{ number_format($alerta->diferencia_porcentaje, 1) }}% más caro</small>
                                    @else
                                    <span class="text-warning fw-bold"><i class="bi bi-arrow-up-circle-fill"></i> ¡Estás muy barato!</span>
                                    <small class="d-block text-muted">{{ number_format($alerta->diferencia_porcentaje, 1) }}% más barato</small>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary btn-ignorar-alerta" data-id="{{ $alerta->id }}">
                                        <i class="bi bi-eye-slash"></i> Ignorar
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                <a href="{{ route('dashboard.analitica.falabella') }}" class="btn btn-primary px-4">Ir a Analytics Falabella</a>
            </div>
        </div>
    </div>
</div>
@if($alertas->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!sessionStorage.getItem('modalAlertasFalabellaShown')) {
            if (typeof window.modalsQueue !== 'undefined') {
                window.modalsQueue.push('modalAlertasPrecio');
            } else {
                var modalAlertas = new bootstrap.Modal(document.getElementById('modalAlertasPrecio'));
                modalAlertas.show();
            }
            sessionStorage.setItem('modalAlertasFalabellaShown', 'true');
        } else {
            // Inicializar el modal sin mostrarlo, por si lo llaman desde otro botón luego
            var modalAlertas = new bootstrap.Modal(document.getElementById('modalAlertasPrecio'));
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.querySelectorAll('.btn-ignorar-alerta').forEach(btn => {
            btn.addEventListener('click', function() {
                const alertaId = this.getAttribute('data-id');
                const row = document.getElementById('alerta-row-' + alertaId);

                // Mostrar estado de carga
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                this.disabled = true;

                fetch(`/dashboard/alertas/${alertaId}/ignorar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            row.style.transition = "all 0.5s ease";
                            row.style.opacity = "0";
                            row.style.transform = "translateX(50px)";
                            setTimeout(() => {
                                row.remove();
                                // Si ya no quedan filas, cerrar modal
                                const tbody = document.querySelector('#modalAlertasPrecio tbody');
                                if (tbody && tbody.children.length === 0) {
                                    modalAlertas.hide();
                                }
                            }, 500);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.innerHTML = '<i class="bi bi-eye-slash"></i> Ignorar';
                        this.disabled = false;
                    });
            });
        });
    });
</script>
@endif