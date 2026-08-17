@if(!$egresos->isEmpty())
<div class="row">
    <div class="col-12">
        <ul class="list-group" style="position: relative;overflow-x:hidden;overflow-y:auto;height:70vh">
            <li class="list-group-item bg-sistema-uno text-light" style="position:sticky;top:0;z-index:800">
                <div class="row text-center">
                    <div class="col-2 col-md-2 text-start">
                        <small>Plataforma</small>
                    </div>
                    <div class="col-md-1 d-none d-lg-block">
                        <small>Nro Orden</small>
                    </div>
                    @php
                    $usuariosConEgresos = $egresos->map(function($e) { return $e->Usuario; })->unique('idUser');
                    @endphp
                    <div class="col-md-1 d-none d-lg-block p-0">
                        <select class="form-select form-select-sm border-0 bg-transparent text-light text-center" onchange="filterEgresosByUser(this.value)" style="box-shadow:none; cursor:pointer">
                            <option value="all" class="text-dark">Usuario</option>
                            @foreach($usuariosConEgresos as $u)
                            <option value="{{ $u->idUser }}" class="text-dark">{{ $u->user }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-none d-md-block">
                        <small>SKU</small>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        <small>Serial Number</small>
                    </div>
                    <div class="col-2 col-md-1">
                        <small>Costo</small>
                    </div>
                    <div class="col-md-1 d-none d-lg-block">
                        <small>Estado</small>
                    </div>
                    <div class="col-2 col-lg-1">
                        <small>Pedido</small>
                    </div>
                    <div class="col-2 col-lg-1">
                        <small>Despacho</small>
                    </div>
                </div>
            </li>
            @foreach ($egresos as $egreso)
            <li class="list-group-item row-egreso" data-user="{{ $egreso->idUser }}">
                <div class="row text-center">
                    <div class="col-2 col-md-2 text-start">
                        <small>
                            @if($egreso->Publicacion && $egreso->Publicacion->CuentasPlataforma && $egreso->Publicacion->CuentasPlataforma->Plataforma)
                                <img src="{{ asset('storage/'.$egreso->Publicacion->CuentasPlataforma->Plataforma->imagenPlataforma) }}" alt="Plataforma" style="max-height: 25px" class="rounded-1" title="{{ $egreso->Publicacion->CuentasPlataforma->Plataforma->nombrePlataforma }}">
                            @elseif($egreso->DetalleVenta && $egreso->DetalleVenta->Venta && $egreso->DetalleVenta->Venta->canal)
                                <span class="badge bg-primary">{{ $egreso->DetalleVenta->Venta->canal }}</span>
                            @else
                                <span class="badge bg-secondary">Tienda / Otro</span>
                            @endif
                        </small>
                    </div>
                    <div class="col-md-1 d-none d-lg-block text-break">
                        <small>{{ is_null($egreso->numeroOrden) ? 'No aplica' : $egreso->numeroOrden }}</small>
                    </div>
                    <div class="col-md-1 d-none d-lg-block">
                        <small>{{ $egreso->Usuario->user }}</small>
                    </div>
                    <div class="col-md-2 d-none d-md-block text-break">
                        <small>{{ is_null($egreso->Publicacion) || is_null($egreso->Publicacion->sku) ? 'No aplica' : $egreso->Publicacion->sku }}</small>
                    </div>
                    <div class="col-4 col-md-3 col-lg-2">
                        @php
                        $devolucion = $egreso->Devoluciones->first();

                        // Fallback para registros antiguos (antes de crear la tabla devoluciones)
                        // Si este no es el ultimo egreso del producto, asumimos que fue devuelto.
                        $esDevueltoLegado = false;
                        if (!$devolucion) {
                        $ultimoEgresoId = $egreso->RegistroProducto->Egresos->max('idEgreso');
                        if ($ultimoEgresoId > $egreso->idEgreso) {
                        $esDevueltoLegado = true;
                        }
                        }

                        $state = $devolucion ? $devolucion->tipo : ($esDevueltoLegado ? 'DEVOLUCION' : $egreso->RegistroProducto->estado);
                        $observacionFinal = $devolucion ? $devolucion->motivo : $egreso->RegistroProducto->observacion;

                        $detalleVenta = $egreso->DetalleVenta;
                        $fallbackPrecio = 0;
                        if ($detalleVenta && $detalleVenta->precioVenta > 0) {
                        $fallbackPrecio = $detalleVenta->precioVenta;
                        } elseif ($detalleVenta && $detalleVenta->Venta && $detalleVenta->Venta->totalVenta > 0) {
                        $fallbackPrecio = $detalleVenta->Venta->totalVenta;
                        } elseif ($egreso->Publicacion && $egreso->Publicacion->precioPublicacion > 0) {
                        $fallbackPrecio = $egreso->Publicacion->precioPublicacion;
                        } else {
                        $producto = $egreso->RegistroProducto->DetalleComprobante->Producto ?? null;
                        if ($producto && isset($producto->precioDolar) && $producto->precioDolar > 0) {
                        $tasaCambio = \App\Models\Precios\Calculadora::first()?->tasaCambio ?? 1;
                        $fallbackPrecio = $producto->precioDolar * $tasaCambio;
                        }
                        }

                        $productoObj = $egreso->RegistroProducto->DetalleComprobante->Producto ?? null;
                        $idGrupo = $productoObj->idGrupo ?? 0;
                        $nombreProdUpper = strtoupper($productoObj->nombreProducto ?? '');
                        $isLaptopOrAio = in_array($idGrupo, [1, 2, 3, 10]) || str_contains($nombreProdUpper, 'LAPTOP') || str_contains($nombreProdUpper, 'AIO') || str_contains($nombreProdUpper, 'ALL IN ONE');

                        $egresoJson = [
                        'idEgreso' => $egreso->idEgreso,
                        'nombreProducto' => $productoObj->nombreProducto ?? '',
                        'numeroSerie' => $egreso->RegistroProducto->numeroSerie,
                        'estado' => $state,
                        'fechaCompra' => $egreso->fechaCompra,
                        'fechaDespacho' => $egreso->fechaDespacho,
                        'fechaMovimiento' => $devolucion ? ($devolucion->fechaDevolucion ?? $egreso->RegistroProducto->fechaMovimiento) : $egreso->RegistroProducto->fechaMovimiento,
                        'usuario' => $egreso->Usuario->user,
                        'observacion' => $observacionFinal,
                        'cuenta' => $egreso->Publicacion ? $egreso->Publicacion->CuentasPlataforma->nombreCuenta : null,
                        'sku' => $egreso->Publicacion ? $egreso->Publicacion->sku : null,
                        'numeroOrden' => $egreso->numeroOrden,
                        'imagenPublicacion' => $egreso->Publicacion ? asset('storage/'.$egreso->Publicacion->CuentasPlataforma->Plataforma->imagenPlataforma) : null,
                        'precioVenta' => $fallbackPrecio,
                        'precioCosto' => $egreso->RegistroProducto->DetalleComprobante->precioCompra ?? 0,
                        'hasDetalleVenta' => $detalleVenta !== null,
                        'isLaptopOrAio' => $isLaptopOrAio
                        ];
                        @endphp
                        <a href="javascript:void(0)"
                            onclick='viewModalEgreso(@json($egresoJson))'
                            class="decoration-link">
                            <small>{{ $egreso->RegistroProducto->numeroSerie }}</small>
                        </a>
                    </div>
                    <div class="col-2 col-md-1">
                        <small>S/ {{ number_format($fallbackPrecio, 2) }}</small>
                    </div>
                    <div class="col-md-1 d-none d-lg-block {{$state == 'NUEVO' ? 'text-sistema-uno' : (
                                                                    $state == 'ENTREGADO' ? 'text-green' : (
                                                                    $state == 'DEVOLUCION' ? 'text-warning' : (
                                                                    $state == 'GARANTIA' ? 'text-marron' : (
                                                                    $state == 'ABIERTO' ? 'text-purple' : (
                                                                    $state == 'DEFECTUOSO' ? 'text-red' : '')))))}}">
                        <small>
                            {{ $state }}
                        </small>
                    </div>
                    <div class="col-2 col-lg-1">
                        <small>{{ $egreso->fechaCompra->format('d/m/y') }}</small>
                    </div>
                    <div class="col-2 col-lg-1">
                        <small>{{ $egreso->fechaDespacho->format('d/m/y') }}</small>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>

</div>
@else
<div class="row align-items-center" style="height:65vh">
    <x-aviso_no_encontrado :mensaje="''" />
</div>
@endif