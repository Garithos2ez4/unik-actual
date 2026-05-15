@extends('layouts.app')

@section('title', 'Producto | '.$producto->codigoProducto)

@section('og_title', 'T赤tulo de Ejemplo para Open Graph')
@section('og_description', 'Descripci車n de ejemplo que aparece en la vista previa.')
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
            <div class="col-10 col-lg-6 d-flex align-items-center">
                <h3>

                    PRODUCTO: <span class="text-secondary">{{$producto->codigoProducto}}</span>
                </h3>
            </div>
            <div class="col-2 col-lg-6 text-end pt-2">
                <h5><a class="btn btn-secondary" href="{{route('details',[$producto->idProducto])}}"><i class="bi bi-layers"></i> <span class="d-none d-lg-inline">Especificaciones</span></a></h5>
            </div>
        </div>
        <br>
        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3">
            <div class="mb-2 col-6">
                <h3>Datos generales</h3>
            </div>
            <div class="mb-2 col-6 text-end">
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
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="row">
                    <h5>Precio venta</h5>
                </div>
                <div class="row">
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
                        <label for="precio-total-sunat" class="form-label">Precio Total en Soles (TC SUNAT: {{$tc}})</label>
                        <input type="number" id="precio-total-sunat" value="" class="form-control" disabled>
                    </div>

                    <!-- 👇 NUEVO CONTENEDOR PARA EL TC FIJO 👇 -->
                    <div id="div-precio-total-fijo" class="mb-2">
                        <label for="precio-total-fijo" class="form-label">Precio Total en Soles / Tasa Fija ({{$tasaFija}})</label>
                        <input type="number" id="precio-total-fijo" value="" class="form-control" disabled>
                    </div>
                    <!-- 👆 FIN DEL NUEVO CONTENEDOR 👆 -->

                </div>
            </div>
            <div class="mb-3 col-md-6 col-lg-6">
                <label class="form-label">Tipo de Cambio:</label>

                <div class="form-check form-switch">
                    <input type="hidden" name="usar_tc_fijo" value="0">
                    <input class="form-check-input input-edit"
                        type="checkbox"
                        name="usar_tc_fijo"
                        id="usar_tc_fijo"
                        value="1"
                        {{ old('usar_tc_fijo', $producto->usar_tc_fijo ?? 1) ? 'checked' : '' }}
                        disabled>

                    <label class="form-check-label" for="usar_tc_fijo">
                        Usar Tipo de Cambio Fijo
                    </label>
                </div>

                <div class="mt-2">
                    <label class="form-label">Tasa de Cambio Personalizada (Opcional):</label>
                    <input type="number" name="tc_fijo" step="0.01" class="form-control input-edit" id="tc_fijo_personalizado" value="{{$producto->tc_fijo}}" placeholder="Ej: 3.80" disabled>
                    <small class="text-muted">Si se llena, esta tasa sobreescribir芍 a la tasa fija global cuando se use TC Fijo.</small>
                </div>
            </div>
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
        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="mb-2 col-6">
                <h3>Inventario disponible</h3>
            </div>
            <div class="mb-2 col-6 text-end">
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
            @endforeach
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label">Stock {{$producto->Inventario_Proveedor->Preveedor->nombreProveedor}}:</label>
                <input name="stockproveedor" value="{{$producto->Inventario_Proveedor->stock}}" type="number" class="form-control input-edit" disabled>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label for="grupo-product" id="proveedor-label" class="form-label">Proveedor:</label>
                <select name="proveedor" id="proveedor-product" class="form-select input-edit" disabled>
                    @foreach($proveedor as $pro)
                    <option value="{{ $pro['idProveedor'] }}"
                        {{ $producto->Inventario_Proveedor->Preveedor->idProveedor == $pro['idProveedor'] ? 'selected' : '' }}>
                        {{ $pro['nombreProveedor'] }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
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
        <div class="editButton row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="col-6 mb-2">
                <h3>Detalles</h3>
            </div>
            <div class="col-6 mb-2 text-end">
                <button type="button" class="btn btn-info text-light btn-edit">Editar <i class="bi bi-pencil"></i></button>
            </div>
            <div class="col-md-6">
                <h5>Imagenes</h5>
                <label for="desc-producto" class="form-label"><i class="bi bi-exclamation-circle-fill" data-bs-toggle="tooltip" data-bs-placement="top" title="Las imagenes tardan en actualizar"></i> Solo imagenes en formato 1000 x 1000px</label>
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
                $descripcionAlmacen = $inv->almacen->descripcion ?? 'Almac谷n Desconocido';
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

<script src="{{ route('js.update-product-scripts', [$tc]) }}"></script>
<script>
    function reportSerials(idAlmacen = null) {
        const baseUrl = "{{ route('seriesXProducto', [$producto->idProducto, 'ALMACEN_ID']) }}";
        const url = baseUrl.replace('ALMACEN_ID', idAlmacen || '');
        window.open(url, '_blank');
    }
</script>

<script>
    function openSeriesPdf(idProducto, idAlmacen) {
        window.open(
            `/pdf/producto-series/${idProducto}/${idAlmacen}`,
            '_blank'
        );
    }
</script>

{{-- Script para manejar el Precio Total Fijo --}}
<script>
    const TC_SUNAT = parseFloat("{{ $tc }}"); // Tasa de cambio SUNAT (ej. 3.52)
    const TC_FIJO = parseFloat("{{ $tasaFija }}"); // Tasa de cambio fija interna (ej. 3.80)

    function getPrecioEnDolares() {
        const divTotal = document.getElementById('div-total-price');
        const selectTipoPrecio = document.getElementById('select-tipoprecio');
        if (!divTotal || divTotal.children.length === 0) return null;

        const primerPrecioTotal = divTotal.querySelector('input[type="number"]');
        if (!primerPrecioTotal || !primerPrecioTotal.value) return null;

        let precioEnDolares = parseFloat(primerPrecioTotal.value);
        const moneda = selectTipoPrecio ? selectTipoPrecio.value : 'DOLAR';

        // Si la moneda es SOL, revertir a dólares usando TC SUNAT
        if (moneda === 'SOL') {
            precioEnDolares = precioEnDolares / TC_SUNAT;
        }
        return precioEnDolares;
    }

    function actualizarPrecioTotalFijo() {
        const usarTcFijo = document.getElementById('usar_tc_fijo');
        const precioSunatInput = document.getElementById('precio-total-sunat');
        const precioFijoInput = document.getElementById('precio-total-fijo');
        const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');

        const precioEnDolares = getPrecioEnDolares();
        if (precioEnDolares === null) return;

        // Determinar qué tasa fija usar (personalizada o global)
        let tasaFijaUsar = TC_FIJO;
        if (tcFijoPersonalizado && tcFijoPersonalizado.value && parseFloat(tcFijoPersonalizado.value) > 0) {
            tasaFijaUsar = parseFloat(tcFijoPersonalizado.value);
        }

        // Precio con TC SUNAT (siempre se calcula)
        if (precioSunatInput) {
            precioSunatInput.value = (precioEnDolares * TC_SUNAT).toFixed(2);
        }

        // Precio con TC Fijo (siempre se calcula usando la tasa determinada)
        if (precioFijoInput) {
            precioFijoInput.value = (precioEnDolares * tasaFijaUsar).toFixed(2);
        }
    }

    function toggleTipoCambio() {
        const usarTcFijo = document.getElementById('usar_tc_fijo');
        const precioSunatInput = document.getElementById('precio-total-sunat');
        const precioFijoInput = document.getElementById('precio-total-fijo');
        const labelSunat = document.querySelector('label[for="precio-total-sunat"]');
        const labelFijo = document.querySelector('label[for="precio-total-fijo"]');
        const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');

        if (!usarTcFijo) return;

        let tasaFijaUsar = TC_FIJO;
        if (tcFijoPersonalizado && tcFijoPersonalizado.value && parseFloat(tcFijoPersonalizado.value) > 0) {
            tasaFijaUsar = parseFloat(tcFijoPersonalizado.value);
        }

        if (usarTcFijo.checked) {
            // Switch ON → TC SUNAT es el precio principal
            if (labelSunat) labelSunat.innerHTML = 'Precio Total en Soles (TC SUNAT: {{ $tc }}) <span class="badge bg-success">En uso</span>';
            if (labelFijo) labelFijo.innerHTML = `Precio Total en Soles / Tasa Fija (${tasaFijaUsar}) <span class="badge bg-secondary">Referencia</span>`;
            if (precioSunatInput) {
                precioSunatInput.classList.add('border-success');
                precioSunatInput.classList.remove('border-warning');
            }
            if (precioFijoInput) {
                precioFijoInput.classList.remove('border-warning');
            }
        } else {
            // Switch OFF → TC Fijo es el precio principal
            if (labelSunat) labelSunat.innerHTML = 'Precio Total en Soles (TC SUNAT: {{ $tc }}) <span class="badge bg-secondary">Referencia</span>';
            if (labelFijo) labelFijo.innerHTML = `Precio Total en Soles / Tasa Fija (${tasaFijaUsar}) <span class="badge bg-warning text-dark">En uso</span>`;
            if (precioSunatInput) {
                precioSunatInput.classList.remove('border-success');
            }
            if (precioFijoInput) {
                precioFijoInput.classList.add('border-warning');
            }
        }

        actualizarPrecioTotalFijo();
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('calcPricesCompleted', function() {
            actualizarPrecioTotalFijo();
        });

        const usarTcFijo = document.getElementById('usar_tc_fijo');
        const tcFijoPersonalizado = document.getElementById('tc_fijo_personalizado');

        if (usarTcFijo) {
            usarTcFijo.addEventListener('change', toggleTipoCambio);
            toggleTipoCambio(); // Estado inicial al cargar
        }

        if (tcFijoPersonalizado) {
            tcFijoPersonalizado.addEventListener('input', toggleTipoCambio);
        }
    });
</script>
@endsection