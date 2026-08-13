@if(isset($alertasReabastecimiento) && $alertasReabastecimiento->count() > 0)
<div class="row mb-3">
    <div class="col-12">
        <div class="card border-warning shadow-sm">
            <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center collapsed" data-bs-toggle="collapse" data-bs-target="#collapseAlertas" style="cursor: pointer;">
                <span><i class="bi bi-exclamation-triangle-fill me-2"></i> Alertas de Reabastecimiento en Tienda ({{ $alertasReabastecimiento->count() }})</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div id="collapseAlertas" class="collapse">
                <div class="card-body bg-light">
                    <p class="card-text mb-2 text-muted small">Los siguientes productos tienen 2 o menos unidades en Tienda (ID=1) y cuentan con stock disponible en otros almacenes.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover bg-white mb-0">
                            <thead>
                                <tr class="table-warning">
                                    <th>Cód. / Modelo</th>
                                    <th>Producto</th>
                                    <th class="text-center">Stock Tienda</th>
                                    <th>Traer de Almacén</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($alertasReabastecimiento as $prod)
                                <tr>
                                    <td>
                                        <strong>{{ $prod->codigoProducto }}</strong><br>
                                        <small class="text-muted">{{ $prod->modelo }}</small>
                                    </td>
                                    <td>{{ $prod->nombreProducto }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $prod->stock_tienda == 0 ? 'bg-danger' : 'bg-warning text-dark' }} fs-6">
                                            {{ $prod->stock_tienda }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($prod->stock_alm2 > 0) <span class="badge bg-secondary">{{ optional($almacenes->firstWhere('idAlmacen', 2))->descripcion ?? 'Alm 2' }} ({{ $prod->stock_alm2 }})</span> @endif
                                        @if($prod->stock_alm3 > 0) <span class="badge bg-secondary">{{ optional($almacenes->firstWhere('idAlmacen', 3))->descripcion ?? 'Alm 3' }} ({{ $prod->stock_alm3 }})</span> @endif
                                        @if($prod->stock_alm4 > 0) <span class="badge bg-secondary">{{ optional($almacenes->firstWhere('idAlmacen', 4))->descripcion ?? 'Alm 4' }} ({{ $prod->stock_alm4 }})</span> @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
