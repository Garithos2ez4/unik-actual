<p class="mb-3 text-secondary"><strong>Producto:</strong> {{ $producto->modelo }}</p>

<h6 class="fw-bold mt-2"><i class="bi bi-boxes"></i> Ubicación General (Stock)</h6>
<div class="table-responsive">
    <table class="table table-bordered table-striped text-center align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Almacén</th>
                <th>Desglose de Ubicaciones</th>
                <th>Stock Total</th>
            </tr>
        </thead>
        <tbody>
            @php $hayStock = false; @endphp

            @foreach($almacenes as $almacen)
            @php
            $inventario = $producto->Inventario->where('idAlmacen', $almacen->idAlmacen)->first();
            @endphp

            @if($inventario && $inventario->stock > 0)
            @php
            $hayStock = true;

            // 1. Identificar la ubicación general por defecto
            $rackGeneralNombre = 'Sin asignar';
            $rackGeneralFoto = null;
            if($inventario->ubicacion_fisica) {
            $ubiModel = $almacen->Ubicaciones->where('idUbicacion', $inventario->ubicacion_fisica)->first();
            if($ubiModel) {
            $rackGeneralNombre = $ubiModel->nombre;
            if($ubiModel->foto) $rackGeneralFoto = $ubiModel->foto_url;
            }
            }

            // 2. Traer todas las series disponibles de este almacén
            $seriesAlmacen = \App\Models\RegistroProducto::whereHas('DetalleComprobante', function($q) use ($producto) {
            $q->where('idProducto', $producto->idProducto);
            })
            ->where('idAlmacen', $almacen->idAlmacen)
            ->whereNotIn('estado', ['ENTREGADO', 'INVALIDO'])
            ->with('UbicacionAlmacen')
            ->get();

            // 3. Agrupar y contar las cantidades por estante
            $distribucion = [];

            if($seriesAlmacen->count() > 0) {
            foreach($seriesAlmacen as $serie) {
            // Si la serie tiene ubicación propia la usa, sino hereda la general
            $nombreRack = $serie->UbicacionAlmacen ? $serie->UbicacionAlmacen->nombre : $rackGeneralNombre;
            $fotoRack = $serie->UbicacionAlmacen ? ($serie->UbicacionAlmacen->foto ? $serie->UbicacionAlmacen->foto_url : null) : $rackGeneralFoto;

            if(!isset($distribucion[$nombreRack])) {
            $distribucion[$nombreRack] = ['cantidad' => 0, 'foto' => $fotoRack];
            }
            $distribucion[$nombreRack]['cantidad']++;
            }
            } else {
            // Si es un producto que se vende a granel (sin series), todo va al rack general
            $distribucion[$rackGeneralNombre] = [
            'cantidad' => $inventario->stock,
            'foto' => $rackGeneralFoto
            ];
            }
            @endphp

            <tr>
                <td class="fw-bold align-middle">{{ $almacen->descripcion }}</td>
                <td class="text-start">
                    <div class="d-flex flex-column gap-2 py-1">
                        @foreach($distribucion as $nombreRack => $data)
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1">
                            <div>
                                <span class="badge {{ $nombreRack == 'Sin asignar' ? 'bg-secondary' : 'bg-primary' }}" style="font-size:13px;">
                                    {{ $nombreRack }}
                                </span>
                                @if($data['foto'])
                                <a href="{{ $data['foto'] }}" target="_blank" title="Ver foto física del rack" class="ms-1">
                                    <i class="bi bi-image text-info"></i>
                                </a>
                                @endif
                            </div>
                            <span class="fw-bold text-dark small">{{ $data['cantidad'] }} und.</span>
                        </div>
                        @endforeach
                    </div>
                </td>
                <td class="align-middle">
                    <span class="badge bg-success" style="font-size: 16px;">{{ $inventario->stock }}</span>
                </td>
            </tr>
            @endif
            @endforeach

            @if($producto->Inventario_Proveedor && $producto->Inventario_Proveedor->stock > 0)
            @php $hayStock = true; @endphp
            <tr>
                <td class="fw-bold text-secondary align-middle">{{ $producto->Inventario_Proveedor->Preveedor->nombreProveedor }}</td>
                <td class="text-secondary align-middle">
                    <span class="badge bg-secondary">En almacén de proveedor</span>
                </td>
                <td class="align-middle">
                    <span class="badge bg-secondary" style="font-size: 16px;">{{ $producto->Inventario_Proveedor->stock }}</span>
                </td>
            </tr>
            @endif

            @if(!$hayStock)
            <tr>
                <td colspan="3" class="text-danger py-3">No hay stock disponible para este producto.</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

@if($seriesDisponibles->count() > 0)
<h6 class="fw-bold"><i class="bi bi-upc-scan"></i> Ubicación por Número de Serie</h6>
<div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
    <table class="table table-bordered table-striped text-center align-middle" style="font-size: 0.9em;">
        <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
            <tr>
                <th>Almacén</th>
                <th>Nro. Serie</th>
                <th>Ubicación Específica</th>
            </tr>
        </thead>
        <tbody>
            @foreach($seriesDisponibles as $serie)
            <tr>
                <td>{{ $serie->Almacen ? $serie->Almacen->descripcion : '-' }}</td>
                <td class="fw-bold text-primary">{{ $serie->numeroSerie }}</td>
                <td>
                    @if($serie->UbicacionAlmacen)
                    <span class="badge bg-info text-dark">{{ $serie->UbicacionAlmacen->nombre }}</span>
                    @else
                    <span class="text-muted fst-italic">Sin asignar</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif