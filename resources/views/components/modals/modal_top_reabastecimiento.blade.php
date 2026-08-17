<div class="modal fade" id="modalTopReabastecimiento" tabindex="-1" aria-labelledby="modalTopReabastecimientoLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-danger shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold mb-0" id="modalTopReabastecimientoLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ¡Alerta de Reabastecimiento Urgente!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <p class="mb-3 text-dark">
                    Los siguientes productos están en el <strong>Top 10 de más vendidos</strong> pero tienen stock crítico en Tienda (≤ 2). Tienen stock disponible en otros almacenes.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover bg-white mb-0">
                        <thead>
                            <tr class="table-danger">
                                <th>Cód. / Modelo</th>
                                <th>Producto</th>
                                <th class="text-center">Total Ventas</th>
                                <th class="text-center">Stock Tienda</th>
                                <th>Traer de Almacén</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alertas as $prod)
                            <tr>
                                <td>
                                    <strong>{{ $prod->codigoProducto }}</strong><br>
                                    <small class="text-muted">{{ $prod->modelo }}</small>
                                </td>
                                <td>{{ $prod->nombreProducto }}</td>
                                <td class="text-center fw-bold text-success">{{ $prod->total_ventas }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $prod->stock_tienda == 0 ? 'bg-danger' : 'bg-warning text-dark' }} fs-6">
                                        {{ $prod->stock_tienda }}
                                    </span>
                                </td>
                                <td>
                                    @if($prod->stock_alm2 > 0) <span class="badge bg-secondary">{{ optional($almacenes->firstWhere('idAlmacen', 2))->descripcion ?? 'Almacen 2' }} ({{ $prod->stock_alm2 }})</span> @endif
                                    @if($prod->stock_alm3 > 0) <span class="badge bg-secondary">{{ optional($almacenes->firstWhere('idAlmacen', 3))->descripcion ?? 'Almacen 3' }} ({{ $prod->stock_alm3 }})</span> @endif
                                    @if($prod->stock_alm4 > 0) <span class="badge bg-secondary">{{ optional($almacenes->firstWhere('idAlmacen', 4))->descripcion ?? 'Almacen 4' }} ({{ $prod->stock_alm4 }})</span> @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Verificar si la alerta ya se mostró en esta sesión para no molestar en cada recarga
        if (!sessionStorage.getItem('alertaReabastecimientoMostrada')) {
            if (typeof window.modalsQueue !== 'undefined') {
                window.modalsQueue.push('modalTopReabastecimiento');
            } else {
                var myModal = new bootstrap.Modal(document.getElementById('modalTopReabastecimiento'));
                myModal.show();
            }
            sessionStorage.setItem('alertaReabastecimientoMostrada', 'true');
        }
    });
</script>
