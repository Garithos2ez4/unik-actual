@if(!$registros->isEmpty())
<div class="row">
    <div class="col-md-12" style="overflow-x: hidden;overflow-y:auto;height: 60vh">
        <ul class="list-group">
            <li class="list-group-item bg-sistema-uno text-light" style="position: sticky;top:0;z-index:800">
                <div class="row text-center">
                    <div class="col-3 col-md-2 text-start">
                        <small>Producto</small>
                    </div>
                    <div class="col-lg-2 d-none d-lg-block">
                        <small>Comprobante</small>
                    </div>
                    <div class="col-lg-1 d-none d-lg-block">
                        <small>Usuario</small>
                    </div>
                    <div class="col-5 col-lg-3">
                        <small>Nro Serie</small>
                    </div>
                    <div class="col-md-2 col-lg-1 d-none d-md-block">
                        <small>Precio</small>
                    </div>
                    <div class="col-lg-1 d-none d-lg-block">
                        <small>Adquisicion</small>
                    </div>
                    <div class="col-lg-1 d-none d-lg-block">
                        <small>Estado</small>
                    </div>
                    <div class="col-4 col-md-2 col-lg-1 text-end">
                        <small>Registro</small>
                    </div>
                </div>
            </li>
            @foreach($registros as $registro)
            @php
                $producto = $registro->RegistroProducto->DetalleComprobante->Producto;
                $esPack = $producto->packHijos->isNotEmpty();
                $state = $registro->RegistroProducto->estado;
            @endphp
            <li
                class="list-group-item list-ingreso-{{$registro->idUser}} list-ingreso-all {{$state == 'INVALIDO' ? 'text-decoration-line-through text-danger' : ''}}">
                <div class="row text-center">
                    <div class="col-3 col-md-2 text-start">
                        <small data-bs-toggle="tooltip" data-bs-placement="top"
                            title="{{$producto->nombreProducto}}">
                            <a class="decoration-link"
                                href="{{route('producto',[encrypt($producto->idProducto)])}}">{{$producto->codigoProducto}}</a>
                            @if($esPack)
                                <span class="badge bg-purple text-white" style="font-size: 0.6rem;" data-bs-toggle="tooltip" title="Este producto es un Pack divisible">PACK</span>
                            @endif
                        </small>
                    </div>
                    <div class="col-lg-2 d-none d-lg-block">
                        <small data-bs-toggle="tooltip" data-bs-placement="top"
                            title="{{$registro->RegistroProducto->DetalleComprobante->Comprobante->Preveedor->nombreProveedor}}">
                            <a class="decoration-link"
                                href="{{route('documento',[encrypt($registro->RegistroProducto->DetalleComprobante->Comprobante->idComprobante),0])}}">{{$registro->RegistroProducto->DetalleComprobante->Comprobante->numeroComprobante}}</a>
                        </small>
                    </div>
                    <div class="col-lg-1 d-none d-lg-block">
                        <small>{{$registro->Usuario->user}}</small>
                    </div>
                    <div class="col-5 col-lg-3">
                        <small>
                            <a class="decoration-link" href="javascript:void(0)"
                                onclick="dataModalDetalle({{ json_encode($registro) }})">
                                {{$registro->RegistroProducto->numeroSerie}}
                            </a>
                            @if($esPack && $state == 'NUEVO' && !Str::startsWith($registro->RegistroProducto->numeroSerie, 'UNK-'))
                                <button type="button" class="btn btn-sm btn-outline-purple ms-1 py-0 px-1 btn-dividir-pack"
                                    data-idregistro="{{$registro->RegistroProducto->idRegistro}}"
                                    data-nombreproducto="{{$producto->nombreProducto}}"
                                    data-serie="{{$registro->RegistroProducto->numeroSerie}}"
                                    data-bs-toggle="tooltip" title="Dividir Pack">
                                    <i class="bi bi-scissors"></i>
                                </button>
                            @endif
                        </small>
                    </div>
                    <div class="col-md-2 col-lg-1 d-none d-md-block">
                        <small>{{$registro->RegistroProducto->DetalleComprobante->Comprobante->moneda == 'DOLAR' ? '$ '
                            : 'S/. '}}{{number_format($registro->RegistroProducto->DetalleComprobante->precioUnitario,
                            2)}}</small>
                    </div>
                    <div class="col-md-1 d-none d-lg-block">
                        <small>{{$registro->RegistroProducto->DetalleComprobante->Comprobante->adquisicion}}</small>
                    </div>
                    <div class="col-lg-1 d-none d-lg-block {{$state == 'NUEVO' ? 'text-sistema-uno' : (
                                                            $state == 'ENTREGADO' ? 'text-green' : (
                                                            $state == 'DEVOLUCION' ? 'text-warning' : (
                                                            $state == 'GARANTIA' ? 'text-marron' : (
                                                            $state == 'ABIERTO' ? 'text-purple' : (
                                                            $state == 'DEFECTUOSO' ? 'text-red' : (
                                                            $state == 'DIVIDIDO' ? 'text-info' : (
                                                            $state == 'REUNIDO' ? 'text-secondary' : '')))))))}}">

                        <small>
                            {{ $state}}
                        </small>
                    </div>
                    <div class="col-4 col-md-2 col-lg-1 text-end">
                        <small>{{$registro->fechaIngreso->format('d/m/Y')}}</small>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
</div>
<br>
@else
<div class="row align-items-center" style="height:80vh">
    <x-aviso_no_encontrado :mensaje="''" />
</div>
@endif
<x-paginacion :justify="'end'" :coleccion="$registros" :container="$container"/>