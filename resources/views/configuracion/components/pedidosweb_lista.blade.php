<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check text-primary"></i> Pedidos Web</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Lista de pedidos realizados a través de la pasarela y checkout manual web.</p>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>N° Pedido</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Pasarela</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pedidos as $pedido)
                    <tr>
                        <td class="fw-bold">#{{ $pedido->idPedidoWeb }}</td>
                        <td>
                            @if($pedido->cliente)
                                {{ $pedido->cliente->nombre }} {{ $pedido->cliente->apellidos }}
                                <br><small class="text-muted">{{ $pedido->cliente->email }}</small>
                            @else
                                <span class="text-muted">Desconocido</span>
                            @endif
                        </td>
                        <td>{{ $pedido->created_at ? $pedido->created_at->format('d/m/Y H:i') : '-' }}</td>
                        <td class="fw-bold">S/ {{ number_format($pedido->total, 2) }}</td>
                        <td>
                            @if($pedido->pasarela == 'MANUAL')
                                <span class="badge bg-secondary">Manual / WhatsApp</span>
                            @else
                                <span class="badge bg-info text-dark">{{ $pedido->pasarela }}</span>
                            @endif
                        </td>
                        <td>
                            <select class="form-select form-select-sm border-0 fw-bold 
                                @if(strtoupper($pedido->estado) == 'PENDIENTE') text-warning bg-light 
                                @elseif(strtoupper($pedido->estado) == 'PAGADO') text-success bg-light 
                                @elseif(strtoupper($pedido->estado) == 'DESPACHADO') text-primary bg-light 
                                @else text-secondary bg-light @endif" 
                                onchange="updatePedidoWebEstado({{ $pedido->idPedidoWeb }}, this)"
                                data-original-value="{{ $pedido->estado }}">
                                
                                <option value="PENDIENTE" @if(strtoupper($pedido->estado) == 'PENDIENTE') selected @endif>PENDIENTE</option>
                                <option value="PAGADO" @if(strtoupper($pedido->estado) == 'PAGADO') selected @endif>PAGADO</option>
                                <option value="DESPACHADO" @if(strtoupper($pedido->estado) == 'DESPACHADO') selected @endif>DESPACHADO</option>
                                <option value="RECHAZADO" @if(strtoupper($pedido->estado) == 'RECHAZADO') selected @endif>RECHAZADO</option>
                                <option value="CANCELADO" @if(strtoupper($pedido->estado) == 'CANCELADO') selected @endif>CANCELADO</option>
                            </select>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalDetallePedidoWeb{{$pedido->idPedidoWeb}}">
                                <i class="bi bi-eye"></i> Ver
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No hay pedidos web registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($pedidos as $pedido)
<!-- Modal Detalle Pedido Web -->
<div class="modal fade" id="modalDetallePedidoWeb{{$pedido->idPedidoWeb}}" tabindex="-1" aria-labelledby="modalDetalleLabel{{$pedido->idPedidoWeb}}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalDetalleLabel{{$pedido->idPedidoWeb}}">
                    Detalle del Pedido #{{ $pedido->idPedidoWeb }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Cliente:</p>
                        <p class="fw-bold mb-0">
                            @if($pedido->cliente)
                                {{ $pedido->cliente->nombre }} {{ $pedido->cliente->apellidos }}<br>
                                DNI/RUC: {{ $pedido->cliente->documento }}<br>
                                Teléfono: {{ $pedido->cliente->telefono }}
                            @else
                                Sin datos
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1 text-muted">Fecha del Pedido:</p>
                        <p class="fw-bold mb-0">{{ $pedido->created_at ? $pedido->created_at->format('d/m/Y H:i') : '' }}</p>
                        <p class="mb-1 text-muted mt-2">Transacción / Referencia:</p>
                        <p class="fw-bold mb-0">{{ $pedido->codigoTransaccion ?? 'N/A' }}</p>
                    </div>
                </div>

                <h6 class="fw-bold mt-4 mb-3 border-bottom pb-2">Productos</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">P. Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pedido->detalles as $detalle)
                            <tr>
                                <td>
                                    {{ $detalle->producto->nombreProducto ?? 'Producto desconocido' }}
                                    @if($detalle->producto && $detalle->producto->modelo)
                                        <br><small class="text-muted">Modelo: {{ $detalle->producto->modelo }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $detalle->cantidad }}</td>
                                <td class="text-end">S/ {{ number_format($detalle->precio, 2) }}</td>
                                <td class="text-end fw-bold">S/ {{ number_format($detalle->precio * $detalle->cantidad, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">TOTAL</th>
                                <th class="text-end fs-5 text-primary">S/ {{ number_format($pedido->total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endforeach
