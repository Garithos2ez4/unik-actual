<p class="mb-3 text-secondary">
    <strong>Producto:</strong> {{ $producto->modelo }} &mdash; <span class="text-dark">{{ $producto->nombreProducto }}</span>
</p>

@if(count($historial) === 0)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No se encontraron registros de compra para este producto.
    </div>
@else
<div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
    <table class="table table-bordered table-striped table-hover text-center align-middle" style="font-size: 0.88em;">
        <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
            <tr>
                <th><i class="bi bi-calendar3"></i> Fecha</th>
                <th><i class="bi bi-receipt"></i> Comprobante</th>
                <th><i class="bi bi-shop"></i> Proveedor</th>
                <th><i class="bi bi-currency-exchange"></i> Moneda</th>
                <th><i class="bi bi-tag"></i> Precio Unit.</th>
                <th><i class="bi bi-arrow-left-right"></i> TC del día</th>
                <th><i class="bi bi-cash-coin"></i> Equiv. S/</th>
                <th><i class="bi bi-bag-check"></i> Total Compra</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $row)
            @php
                $tc = $row->tasa_cambio ?? null;
                $esDolar = $row->moneda === 'DOLAR';
                $precioSoles = ($esDolar && $tc) ? round($row->precioUnitario * $tc, 2) : null;
            @endphp
            <tr>
                <td class="fw-bold text-primary">
                    {{ \Carbon\Carbon::parse($row->fechaRegistro)->format('d/m/Y') }}
                </td>
                <td>
                    <span class="badge bg-secondary">{{ $row->numeroComprobante }}</span>
                </td>
                <td>{{ $row->nombreProveedor ?? '-' }}</td>
                <td>
                    @if($esDolar)
                        <span class="badge bg-success">USD</span>
                    @else
                        <span class="badge bg-info text-dark">{{ $row->moneda }}</span>
                    @endif
                </td>
                <td class="fw-bold">
                    @if($esDolar)
                        ${{ number_format($row->precioUnitario, 2) }}
                    @else
                        S/ {{ number_format($row->precioUnitario, 2) }}
                    @endif
                </td>
                <td>
                    @if($tc)
                        <span class="badge bg-warning text-dark">S/ {{ number_format($tc, 3) }}</span>
                    @else
                        <span class="text-muted fst-italic" title="Sin registro de TC para esta fecha">—</span>
                    @endif
                </td>
                <td>
                    @if($precioSoles)
                        <span class="fw-bold text-success">S/ {{ number_format($precioSoles, 2) }}</span>
                    @elseif(!$esDolar)
                        <span class="text-muted">—</span>
                    @else
                        <span class="text-muted fst-italic">Sin TC</span>
                    @endif
                </td>
                <td class="fw-bold text-dark">
                    @if($esDolar)
                        ${{ number_format($row->totalCompra, 2) }}
                    @else
                        S/ {{ number_format($row->totalCompra, 2) }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="text-muted mt-2 mb-0" style="font-size:0.8em;">
    <i class="bi bi-info-circle"></i>
    El TC del día se toma de la tabla <strong>historial_tipo_cambio</strong> por fecha exacta.
    Las filas con "—" en la columna TC corresponden a fechas anteriores al inicio del registro (solo ~1 mes disponible actualmente).
</p>
@endif
