<div class="lista_producto">
    @if(!$productos->isEmpty())
    <div class="row">
        <div class="col-12" style="overflow-x: hidden;overflow-y:auto;height: 70vh">
            <ul class="list-group ">
                <li class="list-group-item d-flex bg-sistema-uno text-light"
                    style="position:sticky; top: 0;z-index:800">
                    <div class="row w-100 h-100 align-items-center">
                        <div class="col-12 col-md-8 col-lg-6 text-center">
                            <h6>Producto</h6>
                        </div>
                        <div class="d-none d-lg-block col-lg-1 text-center">
                            <h6><a style="cursor:pointer" onclick="changePriceList()"><i
                                        class="bi bi-caret-down-fill d-none d-md-inline"></i>Precio</a></h6>
                        </div>
                        <div class="d-none d-md-block col-md-4 col-lg-4 text-center">
                            <h6>Stock</h6>
                        </div>
                        <div class="d-none d-lg-block col-lg-1 text-center">
                            <h6>Proveedor</h6>
                        </div>
                    </div>
                </li>
                @foreach($productos as $pro)
                <li
                    class="list-group-item justify-content-between align-items-center li-item-product-{{$pro->estadoProductoWeb}} li-item-product-all">
                    <div class="row w-100 ">
                        <div class="col-2 col-lg-1 text-center" style="position:relative;cursor:pointer">
                            <img onmouseover="mostrarImg({{ $pro->idProducto }})"
                                onmouseout="ocultarImg({{ $pro->idProducto }})"
                                src="{{ asset('storage/'.$pro->imagenProducto1) }}" alt="Tooltip Imagen"
                                style="width:100%" class="rounded-3">
                            <div class="border border-secondary rounded-3 justify-content-top"
                                style="width: 200px;position: absolute;z-index: 900;top:0;left:100%;display:none"
                                id="img-{{$pro->idProducto}}">
                                <img src="{{ asset('storage/'.$pro->imagenProducto1) }}" alt="Tooltip Imagen"
                                    style="width:100%" class="rounded-3">
                            </div>
                        </div>
                        <div class="col-10 col-md-6 col-lg-5">
                            <div class="row h-100">
                                <div class="col-12" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Cod: {{$pro->codigoProducto}}">
                                    <a class="link-sistema fw-bold"
                                        href="{{route('producto',[encrypt($pro->idProducto)])}}">
                                        <small>{{$pro->nombreProducto}}</small>
                                    </a>
                                </div>
                                <div class="col-9 d-flex flex-column justify-content-end text-start pb-2">
                                    <small class="text-secondary">{{$pro->modelo}}</small>
                                </div>
                                <div class="col-3 d-flex flex-column justify-content-end text-end pb-2">
                                    <small class="text-secondary">{{$pro->MarcaProducto->nombreMarca}}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1 d-none d-lg-block text-center">
                            <small data-value="{{$pro->precioDolar}}"
                                class="price-list-product">${{$pro->precioDolar}}</small>
                        </div>
                        <div class="d-none d-md-block col-md-4">
                            <div class="row text-center">

                                @foreach($almacenes as $almacen)
                                    @php
                                        $inventario = $pro->Inventario
                                            ->where('idAlmacen', $almacen->idAlmacen)
                                            ->first();

                                        $stock = $inventario ? $inventario->stock : 0;
                                    @endphp

                                    <div class="col-6 {{ $stock < $pro->stockMin ? 'text-danger' : '' }}">
                                        <small>{{ $almacen->descripcion }}</small>
                                        <br>
                                        <small>{{ $stock }}</small>
                                    </div>
                                @endforeach

                            </div>
                            <div class="row mt-2 text-center gx-1">
                                <div class="col-4">
                                    <button type="button" class="btn btn-sm btn-outline-info btn-ver-ubicacion w-100" style="font-size: 0.70rem; padding: 0.25rem 0.1rem;" data-id="{{ $pro->idProducto }}" title="Ver Ubicación">
                                        <i class="bi bi-geo-alt"></i> Ubic.
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button type="button" class="btn btn-sm btn-outline-warning btn-ver-historial-precios w-100" style="font-size: 0.70rem; padding: 0.25rem 0.1rem;" data-id="{{ $pro->idProducto }}" title="Historial de Compra">
                                        <i class="bi bi-graph-up-arrow"></i> Comp.
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100" style="font-size: 0.70rem; padding: 0.25rem 0.1rem;" onclick="verHistorialPrecioTienda({{ $pro->idProducto }})" title="Historial Precio Tienda">
                                        <i class="bi bi-shop"></i> Tiend.
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="d-none d-lg-block col-md-1 text-center">
                            <small>{{$pro->Inventario_Proveedor->Preveedor->nombreProveedor}}</small>
                            <br>
                            <small>{{$pro->Inventario_Proveedor->stock}}</small>
                        </div>

                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    <br>

    @else
    <div class="row align-items-center" style="height:50vh">
        <x-aviso_no_encontrado :mensaje="''" />
    </div>
    @endif
    <x-paginacion :justify="'end'" :coleccion="$productos" :container="$container"/>
    <script>
        window.APP_DATA = window.APP_DATA || {};
        window.APP_DATA.tc = {{ $tc ?? '0' }};
    </script>
    <script src="{{ asset('js/list-products-scripts.js') }}?v=1.00"></script>
</div>
