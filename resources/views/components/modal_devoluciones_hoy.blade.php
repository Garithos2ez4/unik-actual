<!-- Modal de Devoluciones de Hoy -->
<div class="modal fade" id="modalDevolucionesHoy" tabindex="-1" aria-labelledby="modalDevolucionesHoyLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalDevolucionesHoyLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ¡Atención! Tienes Devoluciones Nuevas Hoy
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <p class="mb-3">Se han registrado <strong>{{ $devoluciones->count() }}</strong> devoluciones el día de hoy. Por favor, revisa los detalles:</p>
                <div class="table-responsive bg-white rounded shadow-sm border">
                    @php
                    $reasonMap = [
                    'NS_SHOW_AUTOMATED' => 'Punto de entrega',
                    'CUSTOMER_REGRET' => 'Arrepentimiento de compra',
                    'FULFILLMENT_ISSUE' => 'Problema de Fulfillment / Inventario',
                    'PRICE_ISSUE' => 'Problema de precio',
                    'ORDER_INFORMATION_CHANGE' => 'Cambio de información de datos Vitales del Pedido',
                    'NOT_DELIVERED_AFTER_RETRIES' => 'Entrega fallida',
                    'CANCELLED_BY_CUSTOMER' => 'Cancelado por el cliente'
                    ];

                    $statMap = [
                    'canceled' => 'Cancelada / Devolución',
                    'returned' => 'Devuelta',
                    'return_waiting_for_approval' => 'Esperando aprobación',
                    'return_shipped_by_customer' => 'En camino',
                    'return_delivered_to_seller' => 'Recibida por vendedor',
                    ];
                    @endphp
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">N° Orden</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($devoluciones as $index => $devolucion)
                            @php
                            // FalabellaOrder tiene relacion items(). Usamos el primer item como representacion si hay mas de uno.
                            $item = $devolucion->items->first();

                            // Extraer razón
                            $apiReason = $item ? ($item->payload['Reason'] ?? '') : '';
                            $mappedReason = $reasonMap[$apiReason] ?? ($apiReason ?: 'Devolución general');

                            // Extraer estado
                            $realStatus = $devolucion->status;
                            if (empty($realStatus) || $realStatus === '-') {
                            $realStatus = $item ? $item->status : '-';
                            }
                            $mappedStatus = $statMap[$realStatus] ?? $realStatus;
                            @endphp
                            <tr>
                                <td>
                                    @if($item)
                                    <span class="fw-bold d-block text-truncate" style="max-width: 300px;" title="{{ $item->name }}">{{ $item->name }}</span>
                                    <small class="text-secondary">SKU: {{ $item->seller_sku }}</small>
                                    @else
                                    <span class="text-muted">Sin items registrados</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $devolucion->order_number ?? $devolucion->order_id }}</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary btn-ver-detalle-devolucion"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetalleDevolucion"
                                        data-producto="{{ $item ? $item->name : 'N/A' }}"
                                        data-sku="{{ $item ? $item->seller_sku : 'N/A' }}"
                                        data-orden="{{ $devolucion->order_number ?? $devolucion->order_id }}"
                                        data-razon="{{ $mappedReason }}"
                                        data-estado="{{ $mappedStatus }}"
                                        data-fecha="{{ $devolucion->updated_at_falabella ? \Carbon\Carbon::parse($devolucion->updated_at_falabella)->format('d/m/Y H:i') : '' }}">
                                        <i class="bi bi-eye"></i> Ver detalle
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <a href="{{ route('plataformas.falabella.devoluciones') }}" class="btn btn-danger">
                    Ir a Gestión de Devoluciones
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Incluimos el otro archivo blade para el modal de detalles -->
@include('components.modal_detalle_devolucion')

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Obtenemos un identificador único de las devoluciones actuales
        var currentReturnsIds = "{{ implode(',', $devoluciones->pluck('order_id')->toArray()) }}";
        var lastSeenReturns = localStorage.getItem('last_seen_returns_falabella');
        
        // Si no hemos visto este set exacto de devoluciones, mostramos el modal
        if (lastSeenReturns !== currentReturnsIds) {
            var myModal = new bootstrap.Modal(document.getElementById('modalDevolucionesHoy'), {
                keyboard: false
            });
            myModal.show();
            
            // Guardamos el nuevo set para no volver a mostrarlo hasta que haya cambios
            localStorage.setItem('last_seen_returns_falabella', currentReturnsIds);
        }

        // Lógica para llenar el modal secundario con los datos
        var btnVerDetalles = document.querySelectorAll('.btn-ver-detalle-devolucion');
        btnVerDetalles.forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('detDevProducto').textContent = this.getAttribute('data-producto');
                document.getElementById('detDevSku').textContent = this.getAttribute('data-sku');
                document.getElementById('detDevOrden').textContent = this.getAttribute('data-orden');
                document.getElementById('detDevRazon').textContent = this.getAttribute('data-razon');
                document.getElementById('detDevFecha').textContent = this.getAttribute('data-fecha');

                // Ocultamos el primer modal momentaneamente (opcional) para evitar overlays problemáticos,
                // o Bootstrap 5 maneja múltiples modales relativamente bien.
            });
        });
    });
</script>