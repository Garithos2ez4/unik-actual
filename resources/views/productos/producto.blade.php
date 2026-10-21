@extends('layouts.app')

@section('title', 'Producto | '.$producto->codigoProducto)

@section('og_title', 'Titulo de Ejemplo para Open Graph')
@section('og_description', 'Descripcion de ejemplo que aparece en la vista previa.')
@section('og_image', 'https://www.tusitio.com/imagenes/ejemplo.jpg')
@section('og_url', url()->current())
@section('og_type', 'article')

@section('content')
<div class="container">
    <!-- Botón flotante para volver -->
    <div class="d-none d-lg-block" style="position: fixed; top: 90px; left:200px; z-index: 1000;">
        <a href="{{ route('buscarproducto') }}" class="btn btn-secondary shadow rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;" title="Volver al buscador">
            <i class="bi bi-arrow-left-circle" style="font-size: 1.5rem;"></i>
        </a>
    </div>

    <br>
    <form action="{{route('updateproduct',[encrypt($producto->idProducto)])}}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-10 col-lg-6 flex-column justify-content-center">
                <h3>
                    PRODUCTO: <span class="text-secondary">{{$producto->codigoProducto}}</span>
                </h3>
                <span class="text-muted">Mini Código: {{$producto->idProducto}}</span>
            </div>
            <div class="col-2 col-lg-6 text-end pt-2">
                @include('productos.components.btn_templates')
                <h5 class="d-inline"><a class="btn btn-secondary" href="{{route('details',[$producto->idProducto])}}"><i class="bi bi-layers"></i> <span class="d-none d-lg-inline">Especificaciones</span></a></h5>
            </div>
        </div>
        <br>
        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3">
            <div class="mb-2 col-6">
                <h3>Datos generales</h3>
            </div>
            <div class="mb-2 col-6 text-end">
                <button type="button" class="btn btn-secondary text-light me-2" onclick="confirmarCopia()">Copiar <i class="bi bi-copy"></i></button>
                <button type="button" class="btn btn-info text-light btn-edit">Editar <i class="bi bi-pencil"></i></button>
            </div>
            <div class="mb-3 col-12 col-lg-6">
                <label class="form-label">Titulo</label>
                <input type="text" name="titulo" class="form-control input-edit" value="{{$producto->nombreProducto}}" aria-describedby="basic-addon1" maxlength="200" disabled>
            </div>
            <div class="mb-3 col-6 col-lg-3">
                <label for="marca-product" class="form-label">Marca:</label>
                <select name="marca" id="marca-product" class="form-select input-edit" disabled>
                    @foreach($marcas as $marca)
                    <option value="{{ $marca['idMarca'] }}"
                        {{$producto->idMarca == $marca['idMarca'] ? 'selected' : ''}}>
                        {{ $marca['nombreMarca'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 col-6 col-lg-3">
                <label for="grupo-product" class="form-label">Grupo:</label>
                <select name="grupo" id="grupo-product" class="form-select " disabled>
                    @foreach($grupos as $grupo)
                    <option value="{{ $grupo['idGrupoProducto'] }}"
                        {{ $producto->idGrupo == $grupo['idGrupoProducto'] ? 'selected' : '' }}>
                        {{ $grupo['nombreGrupo'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 col-6 col-lg-2">
                <label for="estado-product" class="form-label">Estado:</label>
                <select name="estado" id="estado-product" class="form-select input-edit" disabled>
                    <option value="DISPONIBLE" {{ $producto->estadoProductoWeb == 'DISPONIBLE' ? 'selected' : '' }}>DISPONIBLE</option>
                    <option value="AGOTADO" {{ $producto->estadoProductoWeb == 'AGOTADO' ? 'selected' : '' }}>AGOTADO</option>
                    <option value="OFERTA" {{ $producto->estadoProductoWeb == 'OFERTA' ? 'selected' : '' }}>OFERTA</option>
                    <option value="LIQUIDACION" {{ $producto->estadoProductoWeb == 'LIQUIDACION' ? 'selected' : '' }}>LIQUIDACION</option>
                    <option value="EXCLUSIVO" {{ $producto->estadoProductoWeb == 'EXCLUSIVO' ? 'selected' : '' }}>EXCLUSIVO</option>
                    <option value="DESCONTINUADO" {{ $producto->estadoProductoWeb == 'DESCONTINUADO' ? 'selected' : '' }}>DESCONTINUADO</option>
                </select>
            </div>
            <div class="mb-3 col-6 col-lg-2">
                <label for="garantia-product" class="form-label">Garantia:</label>
                <select name="garantia" id="garantia-product" class="form-select input-edit" disabled>
                    <option value="No tiene" {{$producto->garantia == 'No tiene' ? 'selected' : ''}}>No tiene</option>
                    <option value="3 meses" {{$producto->garantia == '3 meses' ? 'selected' : ''}}>3 meses</option>
                    <option value="6 meses" {{$producto->garantia == '6 meses' ? 'selected' : ''}}>6 meses</option>
                    <option value="12 meses" {{$producto->garantia == '12 meses' ? 'selected' : ''}}>12 meses</option>
                    <option value="24 meses" {{$producto->garantia == '24 meses' ? 'selected' : ''}}>24 meses</option>
                    <option value="36 meses" {{$producto->garantia == '36 meses' ? 'selected' : ''}}>36 meses</option>
                </select>
            </div>
        </div>
        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="mb-2 col-6 col-lg-4">
                <h3>Precios:</h3>
            </div>
            <div class="mb-2 col-6 col-lg-8 text-end">
                <button type="button" class="btn btn-info text-light btn-edit">Editar <i class="bi bi-pencil"></i></button>
                <button type="submit" class="btn btn-success text-light btn-save-section ms-1" style="display: none;">Actualizar <i class="bi bi-floppy"></i></button>
            </div>
            <div class="col-6 col-md-6">
                <div class="row">
                    <h5>Precio producto</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-6 col-lg-4">
                        <label for="select-tipoprecio" class="form-label">Moneda:</label>
                        <select class="form-select input-edit" name="tipoprecio" id="select-tipoprecio" disabled>
                            <option value="DOLAR" selected>Dolares</option>
                            <option value="SOL">Soles</option>
                        </select>
                    </div>
                    <div class="col-md-8"></div>
                    <div class="mb-3 col-md-6">
                        <label for="precio-producto" class="form-label">Sin IGV:</label>
                        <input type="number" name="precio" value="{{number_format($producto->precioDolar, 2, '.', '')}}" id="precio-product" aria-label="Last name" class="form-control input-edit price-product" step="0.01" disabled>
                    </div>
                    <div class="col-md-6"></div>
                    <div class="mb-3 col-md-6">
                        <label for="precio-producto" class="form-label">Con IGV:</label>
                        <input type="number" value="{{number_format($producto->precioDolar * $igv, 2, '.', '')}}" id="precio-product-igv" class="form-control input-edit price-product" step="0.01" disabled>
                    </div>

                    <div class="col-12 mt-4">
                        <label class="form-label fw-bold">Tipo de Cambio:</label>

                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="usar_tc_fijo" value="0">
                            <input class="form-check-input input-edit"
                                type="checkbox"
                                name="usar_tc_fijo"
                                id="usar_tc_fijo"
                                value="1"
                                {{ old('usar_tc_fijo', $producto->usar_tc_fijo ?? 1) ? 'checked' : '' }}
                                disabled>

                            <label class="form-check-label" for="usar_tc_fijo">
                                Usar Tipo de Cambio Sunat
                            </label>
                        </div>

                        <div class="mt-3">
                            <label class="form-label text-primary" style="background-color: #2143de; color: white !important; padding: 2px 5px; border-radius: 3px;">Tasa de Cambio Personalizada (Opcional):</label>
                            <input type="number" name="tc_fijo" step="0.01" class="form-control input-edit mt-1" id="tc_fijo_personalizado" value="{{$producto->tc_fijo}}" placeholder="Ej: 3.80" disabled>
                            <small class="text-muted">Si se llena, esta tasa sobreescribira a la tasa fija global cuando se use TC Fijo.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="row">
                    <div class="col-12 d-flex align-items-center">
                        <h5 class="mb-0">Precio venta</h5>
                        @if($producto->estadoProductoWeb === 'LIQUIDACION')
                        <span class="badge bg-warning text-dark ms-3"><i class="bi bi-exclamation-triangle-fill"></i> LIQUIDACIÓN: Precio Fijo</span>
                        @endif
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="mb-3 col-md-6 col-lg-4">
                        <label for="precio-producto" class="form-label">Utilidad:</label>
                        <input type="number" value="{{number_format($producto->gananciaExtra, 2, '.', '')}}" name="ganancia" id="precio-product-ganancia" class="form-control input-edit price-product" step="0.01" disabled>
                    </div>
                    <div class="col-lg-8"></div>
                    <div class="mb-3 col-md-6 col-lg-4">
                        <label for="precio-producto" class="form-label">Precio Calculado:</label>
                        <input type="number" value="" id="precio-product-calculado" class="form-control price-product" step="0.01" disabled>
                    </div>
                    <div class="col-lg-8"></div>
                    <div class="row" id="div-total-price">
                    </div>
                    <div class="col-md-8"></div>
                    </br>
                    <div id="div-precio-total-sunat" class="mb-2">
                        <label for="precio-total-sunat" class="form-label text-success fw-bold">Precio Total en Soles (TC SUNAT: {{$tc}})</label>
                        <input type="number" id="precio-total-sunat" value="" class="form-control input-edit text-success fw-bold" step="0.01" disabled>
                        <small class="text-muted" style="font-size: 0.75rem;">Modifica para calcular automáticamente la Utilidad</small>
                    </div>

                    <!--  NUEVO CONTENEDOR PARA EL TC FIJO  -->
                    <div id="div-precio-total-fijo" class="mb-2">
                        <label for="precio-total-fijo" class="form-label text-success fw-bold">Precio Total en Soles / Tasa Fija ({{$tasaFija}})</label>
                        <input type="number" id="precio-total-fijo" value="" class="form-control input-edit text-success fw-bold" step="0.01" disabled>
                        <small class="text-muted" style="font-size: 0.75rem;">Modifica para calcular automáticamente la Utilidad</small>
                    </div>
                    <!--  FIN DEL NUEVO CONTENEDOR -->

                    <!--  PRECIO TIENDA -->
                    <div class="mb-2">
                        <label for="precio-tienda" class="form-label fw-bold text-success">
                            <i class="bi bi-shop"></i> Precio Tienda (S/.)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">S/.</span>
                            <input type="number"
                                name="precioTienda"
                                id="precio-tienda"
                                value="{{ number_format(optional($producto->PrecioTienda)->precioTienda ?? 0, 2, '.', '') }}"
                                class="form-control input-edit"
                                step="0.01"
                                min="0"
                                disabled>
                            <button type="button"
                                class="btn btn-outline-secondary btn-sm"
                                title="Ver historial de precios de tienda"
                                onclick="verHistorialPrecioTienda({{ $producto->idProducto }})">
                                <i class="bi bi-clock-history"></i>
                            </button>
                        </div>
                        @if($producto->PrecioTienda && $producto->PrecioTienda->updated_at)
                        <small class="text-muted">
                            Últ. actualización: {{ \Carbon\Carbon::parse($producto->PrecioTienda->updated_at)->format('d/m/Y H:i') }}
                        </small>
                        @endif
                    </div>
                    <!--  DETALLE PRODUCTO (WEB & PASE)  -->
                    <div class="row mt-3 mb-2 border-top pt-3">
                        <div class="col-6">
                            <label for="precio-pase" class="form-label fw-bold text-primary">
                                <i class="bi bi-tag"></i> Precio Pase (S/.)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number"
                                    name="precio_pase"
                                    id="precio-pase"
                                    value="{{ number_format(optional($producto->DetalleProducto)->precio_pase ?? 0, 2, '.', '') }}"
                                    class="form-control input-edit"
                                    step="0.01"
                                    min="0"
                                    disabled>
                            </div>
                            <small class="text-muted">Precio para otras tiendas.</small>
                        </div>
                        <div class="col-6 d-flex align-items-center">
                            <div class="form-check form-switch fs-5">
                                <input type="hidden" name="mostrarPrecioWeb" value="0" class="input-edit" disabled>
                                <input class="form-check-input input-edit" type="checkbox" name="mostrarPrecioWeb" id="mostrarPrecioWeb" value="1" {{ optional($producto->DetalleProducto)->mostrarPrecioWeb ?? true ? 'checked' : '' }} disabled>
                                <label class="form-check-label fs-6 fw-bold" for="mostrarPrecioWeb">Mostrar Precio en Web</label>
                            </div>
                        </div>
                    </div>
                    <!--  FIN DETALLE PRODUCTO  -->

                </div>
            </div>
            <!-- El bloque de Tipo de Cambio fue movido a la columna izquierda -->
        </div>

        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="mb-2 col-6">
                <h3>Datos clave</h3>
            </div>
            <div class="mb-2 col-6 text-end">
                @foreach ($user->Accesos as $access)
                @if($access->idVista == 7)
                <button type="button" class="btn btn-info text-light btn-edit">Editar <i class="bi bi-pencil"></i></button>
                @endif
                @endforeach
            </div>
            <div class="col-lg-4">
                <label class="form-label">UPC/EAN</label>
                <input type="text" name="upc" class="form-control input-edit" value="{{$producto->UPC}}" aria-describedby="basic-addon1" maxlength="13" disabled>
                <small id="upcError" class="text-danger"></small>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Modelo</label>
                <input type="text" name="modelo" class="form-control input-edit" value="{{$producto->modelo}}" aria-describedby="basic-addon1" maxlength="70" disabled>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Part Number</label>
                <input type="text" name="partnumber" class="form-control input-edit" value="{{$producto->partNumber}}" aria-describedby="basic-addon1" maxlength="50" disabled>
            </div>
        </div>

        @include('productos.components.inventario_disponible')
        {{-- Prueba de VIDEOS --}}
        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="col-6 mb-2">
                <h3>Videos</h3>
            </div>
            <div class="col-6 mb-2 text-end">
                <button type="button" class="btn btn-info text-light btn-edit">Editar <i class="bi bi-pencil"></i></button>
            </div>
            <div class="mb-3">
                <label for="video1">URL del Video de Unike Store (Opcional)</label>
                <input name="videoUrl1" value="https://www.youtube.com/watch?v={{$producto->videoUrl1}}" type="text" class="form-control input-edit" disabled>
                <small class="form-text text-muted">Ingrese la primera URL oficial o de referencia del producto.</small>
            </div>
            <div class="mb-3">
                <label for="video2">URL del Video de la Marca (Opcional)</label>
                <input name="videoUrl2" value="https://www.youtube.com/watch?v={{$producto->videoUrl2}}" type="text" class="form-control input-edit" disabled>
                <small class="form-text text-muted">Ingrese la segunda URL oficial o de referencia del producto.</small>
            </div>
        </div>
        {{-- Fin videos --}}

        @include('components.modo_pack')

        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="col-6 mb-2">
                <h3>Detalles</h3>
            </div>
            <div class="col-6 mb-2 text-end">
                <button type="button" class="btn btn-info text-light btn-edit">Editar <i class="bi bi-pencil"></i></button>
            </div>
            <div class="col-md-6">
                <h5>Imagenes</h5>
                <label for="desc-producto" class="form-label"><i class="bi bi-exclamation-circle-fill" data-bs-toggle="tooltip" data-bs-placement="top" title="Las imagenes tardan en actualizar"></i> Imagenes en formato 800 x 800px o 1000 x 1000px (1:1)</label>
                <div class="row">
                    <div class="col-6 mb-3 img-div" id="previewImage1" data-bs-toggle="tooltip" data-bs-placement="top" title="Imagen de cabecera">
                        <input class="d-none input-edit img-input" name="imgone" type="file" accept="image/*" id="imgone-product" onchange="changeImage(event,this,'previewImage1','triggerImage1')" disabled>
                        <img src="{{$producto->publicImages()[0]}}" alt="Click to upload" id="triggerImage1" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                    </div>
                    <div class="col-6 mb-3 img-div" id="previewImage2">
                        <input class="d-none input-edit img-input" name="imgtwo" type="file" accept="image/*" id="imgtwo-product" onchange="changeImage(event,this,'previewImage2','triggerImage2')" disabled>
                        <img src="{{$producto->publicImages()[1]}}" alt="Click to upload" id="triggerImage2" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                    </div>
                    <div class="col-6 img-div" id="previewImage3">
                        <input class="d-none input-edit img-input" name="imgtree" type="file" accept="image/*" id="imgtree-product" onchange="changeImage(event,this,'previewImage3','triggerImage3')" disabled>
                        <img src="{{$producto->publicImages()[2]}}" alt="Click to upload" id="triggerImage3" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                    </div>
                    <div class="col-6 img-div" id="previewImage4">
                        <input class="d-none input-edit img-input" name="imgfour" type="file" accept="image/*" id="imgfour-product" onchange="changeImage(event,this,'previewImage4','triggerImage4')" disabled>
                        <img src="{{$producto->publicImages()[3]}}" alt="Click to upload" id="triggerImage4" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label for="desc-producto" class="form-label">Descripción:</label>
                <textarea name="descripcion" type="text" maxlength="5000" id="desc-producto" class="form-control input-edit" style=" width: 100%;max-height: 660px;overflow-y: auto;" oninput="autoResize(this)" disabled>{{ $producto->descripcionProducto }}</textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-3 d-flex flex-column gap-2">
                @foreach ($producto->Inventario as $inv)
                @php
                $stockAlmacen = $inv->stock;
                if ($stockAlmacen > 0) {
                $almacenId = $inv->idAlmacen;
                $descripcionAlmacen = $inv->almacen->descripcion ?? 'Almacén Desconocido';
                echo '<button type="button" class="btn btn-danger mb-2 text-nowrap" onclick="reportSerials(' . $almacenId . ')">';
                    echo '<i class="bi bi-file-earmark-pdf"></i> Series - ' . $descripcionAlmacen . '</button>';
                }
                @endphp
                @endforeach

            </div>
            <div class="col-3 d-flex flex-column gap-2">
                @foreach ($producto->Inventario as $inv)
                @if ($inv->stock > 0)
                <button
                    type="button"
                    class="btn btn-info text-nowrap"
                    data-producto-id="{{ $producto->idProducto }}"
                    data-almacen-id="{{ $inv->idAlmacen }}"
                    onclick="openSeriesPdf(this.getAttribute('data-producto-id'), this.getAttribute('data-almacen-id'))">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Series - {{ $inv->almacen->descripcion }}
                </button>
                @endif
                @endforeach

                @if(isset($producto->GrupoProducto) && (str_contains(strtolower($producto->GrupoProducto->nombreGrupo), 'reset') || $producto->idGrupo == 77))
                <button type="button" class="btn btn-warning mt-3 text-nowrap shadow" onclick="openServiciosModal({{ $producto->idProducto }})">
                    <i class="bi bi-tools"></i> Herramientas de Servicio
                </button>
                @endif
            </div>
            <div class="col-6 text-center">
                <button type="submit" class="btn btn-success" id="btnSave" disabled>Guardar <i class="bi bi-floppy"></i></button>
            </div>
            <div class="col-3">
            </div>
        </div>
    </form>
    <br>
    <br>
</div>

@include('productos.components.modal_herramientas_servicio')

<script>
    // Usamos strings para las variables de Blade así el formateador (Prettier) no las destruye al guardar el archivo.
    window.APP_DATA = {
        tc: parseFloat("{{ $tc ?? '0' }}"),
        productoIdEncriptado: "{{ encrypt($producto->idProducto) }}",
        isLiquidacion: ("{{ $producto->estadoProductoWeb }}" === "LIQUIDACION"),
        precioLiquidacionSoles: ("{{ $producto->estadoProductoWeb }}" === "LIQUIDACION" && "{{ optional($producto->liquidacion)->precio_liquidacion ?? 'null' }}" !== "null") ?
            parseFloat("{{ optional($producto->liquidacion)->precio_liquidacion }}") : null
    };
</script>
<script src="{{ asset('js/update-product-scripts.js') }}?v=1.04"></script>
@include('productos.logic.producto_scripts')

<script src="{{ asset('js/modo-pack-scripts.js') }}?v=1.01"></script>

@include('productos.modals.ubicacion')

@include('productos.logic.updatefilas')

@include('productos.partials.modal_historial_precio_tienda')

@include('productos.partials.modal_titulos_falabella')

@endsection