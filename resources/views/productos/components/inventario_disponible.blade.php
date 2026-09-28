        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="mb-2 col-6">
                <h3>Inventario disponible</h3>
            </div>
            <div class="mb-2 col-6 text-end">
                <button type="button" class="btn btn-info btn-sm text-white fw-bold shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalUbicacion-{{$producto->idProducto}}">
                    <i class="bi bi-geo-alt-fill"></i> Ver Ubicación Exacta
                </button>
                <button type="button" class="btn btn-info text-light btn-edit">Editar <i class="bi bi-pencil"></i></button>
                @php
                $ingresoEdit = "";
                @endphp
                @foreach ($user->Accesos as $access)
                @if($access->idVista == 10)
                @php
                $ingresoEdit = "input-edit";
                @endphp
                @endif
                @endforeach
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">Stock Minimo:</label>
                <input name="stockminimo" value="{{$producto->stockMin}}" type="number" class="form-control input-edit" disabled>
            </div>
            @foreach($almacenes as $almacen)
            @php
            $inventario = $producto->Inventario
            ->where('idAlmacen', $almacen->idAlmacen)
            ->first();
            @endphp
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">
                    Stock {{ $almacen->descripcion }}:
                </label>

                <input
                    name="stock[{{ $almacen->idAlmacen }}]"
                    value="{{ $inventario ? $inventario->stock : 0 }}"
                    type="number"
                    class="form-control {{ $ingresoEdit }}"
                    disabled>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <label class="form-label" title="Ubicación exacta en {{ $almacen->descripcion }}">
                    Ubicación {{ $almacen->descripcion }}:
                </label>
                @php
                $ubicacionesExactas = \App\Models\Inventario\UbicacionEstante::where('idAlmacen', $almacen->idAlmacen)
                ->where('estado', 1)
                ->orderBy('nombre_rack')
                ->orderBy('fila_estante')
                ->get()
                ->groupBy('nombre_rack');

                $currentRackName = ($inventario && $inventario->UbicacionExacta && $inventario->UbicacionExacta->idAlmacen == $almacen->idAlmacen) ? $inventario->UbicacionExacta->nombre_rack : '';
                @endphp
                <div class="d-flex gap-2">
                    <select class="form-select input-edit rack-selector" style="min-width:0;flex:1" data-target="fila-select-{{$almacen->idAlmacen}}" disabled onchange="updateFilas(this)">
                        <option value="">— Estante —</option>
                        @foreach($ubicacionesExactas as $rackName => $filas)
                        <option value="{{ $rackName }}" {{ $currentRackName == $rackName ? 'selected' : '' }}>
                            {{ $rackName }}
                        </option>
                        @endforeach
                    </select>

                    <select name="idUbicacionExacta[{{ $almacen->idAlmacen }}]" id="fila-select-{{$almacen->idAlmacen}}" class="form-select input-edit fila-selector" style="min-width:0;flex:1" disabled>
                        <option value="">— Fila —</option>
                        @foreach($ubicacionesExactas as $rackName => $filas)
                        @foreach($filas as $ue)
                        <option value="{{ $ue->idUbicacionExacta }}" data-rack="{{ $rackName }}"
                            {{ ($inventario && $inventario->idUbicacionExacta == $ue->idUbicacionExacta) ? 'selected' : '' }}
                            class="{{ $currentRackName != $rackName ? 'd-none' : '' }}">
                            Fila {{ $ue->fila_estante }}
                        </option>
                        @endforeach
                        @endforeach
                    </select>
                </div>

                @php
                $racksEspecificos = \App\Models\Inventario\RegistroProducto::whereHas('DetalleComprobante', function($q) use ($producto) {
                $q->where('idProducto', $producto->idProducto);
                })
                ->where('idAlmacen', $almacen->idAlmacen)
                ->whereNotIn('estado', ['ENTREGADO', 'INVALIDO'])
                ->whereNotNull('ubicacion_especifica')
                ->whereHas('UbicacionExacta', function($q) use ($almacen) {
                $q->where('idAlmacen', $almacen->idAlmacen);
                })
                ->with('UbicacionExacta')
                ->get()
                ->pluck('UbicacionExacta.nombre_completo')
                ->unique()
                ->filter()
                ->toArray();
                @endphp
                @if(count($racksEspecificos) > 0)
                <small class="text-muted d-block mt-1" style="font-size: 0.75rem; line-height: 1.2;">
                    <i class="bi bi-geo-alt-fill text-danger"></i> Series en: <strong>{{ implode(', ', $racksEspecificos) }}</strong>
                </small>
                @endif
            </div>
            @endforeach
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label">Stock Proveedor:</label>
                <input name="stockproveedor" value="{{ $producto->Inventario_Proveedor ? $producto->Inventario_Proveedor->stock : 0 }}" type="number" class="form-control input-edit" disabled>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label for="grupo-product" id="proveedor-label" class="form-label">Proveedor:</label>
                <select name="proveedor" id="proveedor-product" class="form-select input-edit" disabled>
                    <option value="">Seleccione Proveedor</option>
                    @foreach($proveedor as $pro)
                    <option value="{{ $pro['idProveedor'] }}"
                        {{ ($producto->Inventario_Proveedor && $producto->Inventario_Proveedor->idProveedor == $pro['idProveedor']) ? 'selected' : '' }}>
                        {{ $pro['nombreProveedor'] }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
