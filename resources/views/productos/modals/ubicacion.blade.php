<div class="modal fade" id="modalUbicacion-{{$producto->idProducto}}" tabindex="-1" aria-labelledby="modalUbicacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUbicacionLabel"><i class="bi bi-geo-alt-fill"></i> Ubicación Exacta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
                            if($inventario->idUbicacionExacta) {
                            $ubiExacta = $inventario->UbicacionExacta;
                            if($ubiExacta && $ubiExacta->idAlmacen == $almacen->idAlmacen) {
                            $rackGeneralNombre = $ubiExacta->nombre_completo;
                            }
                            }

                            // 2. Traer solo las series que cuentan como stock real en este almacén
                            $seriesAlmacen = \App\Models\RegistroProducto::whereHas('DetalleComprobante', function($q) use ($producto) {
                            $q->where('idProducto', $producto->idProducto);
                            })
                            ->where('idAlmacen', $almacen->idAlmacen)
                            ->whereIn('estado', ['NUEVO', 'ABIERTO', 'DEVOLUCION'])
                            ->with('UbicacionExacta')
                            ->get();

                            // 3. Agrupar y contar las cantidades por estante
                            $distribucion = [];

                            if($seriesAlmacen->count() > 0) {
                            foreach($seriesAlmacen as $serie) {
                            // Si la serie tiene una ubicación específica en este mismo almacén, usamos esa, sino usamos la general
                            $nombreRack = ($serie->UbicacionExacta && $serie->UbicacionExacta->idAlmacen == $almacen->idAlmacen) ? $serie->UbicacionExacta->nombre_completo : $rackGeneralNombre;
                            $fotoRack = null;

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
                @php
                // Obtener series disponibles para este producto
                $seriesDisponibles = \App\Models\RegistroProducto::with(['UbicacionExacta', 'Almacen'])
                ->where('estado', '!=', 'ENTREGADO')
                ->where('estado', '!=', 'INVALIDO')
                ->whereHas('DetalleComprobante', function ($q) use ($producto) {
                $q->where('idProducto', $producto->idProducto);
                })
                ->get();
                @endphp

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
                                    {{-- Si tiene ubicación específica asignada --}}
                                    @if($serie->UbicacionExacta)
                                    <span class="badge bg-info text-dark">{{ $serie->UbicacionExacta->nombre_completo }}</span>
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>