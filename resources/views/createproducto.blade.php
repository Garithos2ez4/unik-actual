@extends('layouts.app')

@section('title', 'Nuevo producto')

@section('content')
    <div class="container">
        <br>
        <div class="row">
            <div class="col-lg-6">
                <h3><a href="{{route('productos',[encrypt(1),encrypt(1)])}}" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> NUEVO PRODUCTO:</h3>
            </div>
        </div>
        @if(isset($productoCopiar) && $productoCopiar)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill"></i> Copiando datos de: <strong>{{ $productoCopiar->nombreProducto }}</strong> ({{ $productoCopiar->codigoProducto }}). Completa los campos únicos (Modelo, PartNumber, UPC) e imágenes.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        <br>
        <form action="{{route('createdetails')}}" id="form-create"  method="POST" enctype="multipart/form-data">
            @csrf
        <div class="row border shadow rounded-3 pt-3 pb-3 mb-3">
            <div class="row">
                <div class="col-12">
                    <h3>Datos generales</h3>
                </div>
            </div>
            <div class="mb-3 col-12 col-lg-6">
                <label class="form-label">Titulo</label>
                <input type="text" id="name-product" name="name" class="form-control {{ isset($productoCopiar) ? 'border-warning' : '' }}" value="{{ old('name', isset($productoCopiar) ? '(Copia) ' . $productoCopiar->nombreProducto : '') }}" aria-describedby="basic-addon1" maxlength="200">
            </div>
            <div class="mb-3 col-6 col-lg-3">
            <label for="marca-product" id="marca-label" class="form-label">Marca:</label>
                <select name="marca" id="marca-product" class="form-select">
                    <option value="" {{ old('marca') ? '' : 'selected' }}>-Elige una marca-</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca['idMarca'] }}"
                            {{ old('marca', optional($productoCopiar)->idMarca ?? '') == $marca['idMarca'] ? 'selected' : '' }}>
                            {{ $marca['nombreMarca'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 col-6 col-lg-3">
                <label for="grupo-product" id="grupo-label" class="form-label">Grupo:</label>
                <select name="grupo" id="grupo-product" class="form-select">
                        <option value="" {{ old('grupo') ? '' : 'selected' }}>-Elige un grupo-</option>
                    @foreach($grupos as $grupo)
                        <option value="{{ $grupo['idGrupoProducto'] }}"
                            {{ old('grupo', optional($productoCopiar)->idGrupo ?? '') == $grupo['idGrupoProducto'] ? 'selected' : '' }}>
                            {{ $grupo['nombreGrupo'] }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="codigo" value="ERROR" id="codigo-product">
                <small><i class="bi bi-exclamation-circle"></i> No se podra modificar despues</small>
            </div>
            <div class="mb-3 col-6 col-lg-2">
                <label for="estado-product" id="estado-label" class="form-label">
                    <i class="bi bi-info-circle-fill" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<small>DISPONIBLE: En stock.</small><br><small>AGOTADO: Sin stock</small><br><small>EXCLUSIVO: Precio sin calculos.</small>"></i> 
                    Estado:
                </label>
                <select name="estado" id="estado-product" class="form-select">
                  <option value="" {{ old('estado') ? '' : 'selected' }}>-Elige un estado-</option>
                  <option value="DISPONIBLE" {{ old('estado', optional($productoCopiar)->estadoProducto ?? '') == 'DISPONIBLE' ? 'selected' : '' }}>DISPONIBLE</option>
                  <option value="AGOTADO" {{ old('estado', optional($productoCopiar)->estadoProducto ?? '') == 'AGOTADO' ? 'selected' : '' }}>AGOTADO</option>
                  <option value="OFERTA" {{ old('estado', optional($productoCopiar)->estadoProducto ?? '') == 'OFERTA' ? 'selected' : '' }}>OFERTA</option>
                  <option value="EXCLUSIVO" {{ old('estado', optional($productoCopiar)->estadoProducto ?? '') == 'EXCLUSIVO' ? 'selected' : '' }}>EXCLUSIVO</option>
                  <option value="DESCONTINUADO" {{ old('estado', optional($productoCopiar)->estadoProducto ?? '') == 'DESCONTINUADO' ? 'selected' : '' }}>DESCONTINUADO</option>
                </select>
            </div>
            <div class="mb-3 col-6 col-lg-2">
                <label for="garantia-product" id="garantia-label" class="form-label">Garantia:</label>
                <select name="garantia" id="garantia-product" class="form-select">
                  <option value="" {{ old('garantia') ? '' : 'selected' }}>-Elige la garantia-</option>
                  <option value="No tiene" {{ old('garantia', optional($productoCopiar)->garantiaProducto ?? '') == 'No tiene' ? 'selected' : '' }}>No tiene</option>
                  <option value="3 meses" {{ old('garantia', optional($productoCopiar)->garantiaProducto ?? '') == '3 meses' ? 'selected' : '' }}>3 meses</option>
                  <option value="6 meses" {{ old('garantia', optional($productoCopiar)->garantiaProducto ?? '') == '6 meses' ? 'selected' : '' }}>6 meses</option>
                  <option value="12 meses" {{ old('garantia', optional($productoCopiar)->garantiaProducto ?? '') == '12 meses' ? 'selected' : '' }}>12 meses</option>
                  <option value="24 meses" {{ old('garantia', optional($productoCopiar)->garantiaProducto ?? '') == '24 meses' ? 'selected' : '' }}>24 meses</option>
                  <option value="36 meses" {{ old('garantia', optional($productoCopiar)->garantiaProducto ?? '') == '36 meses' ? 'selected' : '' }}>36 meses</option>
                </select>
            </div>
        </div>
        <div class="row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="col-12">
                <h3>Precios y Costos:</h3>
            </div>
            <div class="col-6 col-md-6">
                <div class="row">
                    <h5>Precio producto</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-6 col-lg-4">
                        <label for="select-tipoprecio" class="form-label">Moneda:</label>
                        <select class="form-select" onchange="changeTC()" name="tipoprecio" id="select-tipoprecio">
                            <option value="DOLAR" {{ old('tipoprecio', optional($productoCopiar)->tipoPrecio ?? '') == 'DOLAR' ? 'selected' : '' }}>Dolares</option>
                            <option value="SOL" {{ old('tipoprecio', optional($productoCopiar)->tipoPrecio ?? '') == 'SOL' ? 'selected' : '' }}>Soles</option>
                          </select>
                    </div>
                    <div class="col-lg-8"></div>
                    <div class="mb-3 col-md-6">
                        <label for="precio-producto" class="form-label">Sin IGV:</label>
                         <input type="number" name="precio" value="{{ old('precio', optional($productoCopiar)->precioProducto ?? 0) }}" id="precio-product"  aria-label="Last name" class="form-control  price-product" step="0.01">
                    </div>
                    <div class="col-md-6"></div>
                    <div class="mb-3 col-md-6">
                        <label for="precio-producto" class="form-label">Con IGV:</label>
                         <input type="number"  id="precio-product-igv" value="0" class="form-control price-product" step="0.01">
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
                          <input type="number"  value="{{ old('ganancia', optional($productoCopiar)->gananciaExtra ?? 0) }}" name="ganancia" id="precio-product-ganancia"  class="form-control price-product" step="0.01">
                    </div>
                    <div class="col-lg-8"></div>
                    <div class="mb-3 col-md-6 col-lg-4">
                        <label for="precio-producto" class="form-label">Precio Calculado:</label>
                         <input type="number"  value="" id="precio-product-calculado" class="form-control price-product" step="0.01" disabled>
                    </div>
                    <div class="col-lg-8"></div>
                        <div class="row" id="div-total-price">
                        </div>
                    <div class="col-md-8"></div>
                </div>
            </div>
        </div>
        <div class="row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="row">
                <div class="col-12">
                    <h3>Datos clave</h3>
                </div>
            </div>
            <div class="col-lg-4">
                <label class="form-label d-flex w-100"><span class="w-50">UPC/EAN</span><span class="text-secondary text-end w-50">No aplica</span></label>
                <div class="input-group mb-3">
                    <input type="text" name="upc" class="form-control" id="upc-product" placeholder="Maximo 13 caracteres" value="{{old('upc')}}" maxlength="13" aria-describedby="basic-addon1">
                      <div class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" onchange="checkException(this,'upc-product')" value="0" id="check-upc-product">
                      </div>
                </div>
                
                <small id="upcError" class="text-danger"></small>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Modelo</label>
                <input type="text" name="modelo" class="form-control mb-3" id="modelo-product" value="{{old('modelo')}}" aria-describedby="basic-addon1" maxlength="70">
            </div>
            <div class="col-lg-3">
                <label class="form-label d-flex w-100"><span class="w-50">Numero de Parte</span><span class="text-secondary text-end w-50">No aplica</span></label>
                <div class="input-group mb-3">
                    <input type="text" name="partnumber" class="form-control" placeholder="Part Number" id="partnumber-product" value="{{old('partnumber')}}" aria-describedby="basic-addon1" maxlength="50">
                      <div class="input-group-text">
                        <input class="form-check-input mt-0" type="checkbox" onchange="checkException(this,'partnumber-product')" value="0" id="check-upc-product">
                      </div>
                </div>
                <!--<input type="text" name="partnumber" class="form-control" placeholder="Agrega 0 si no aplica" id="partnumber-product" value="{{old('partnumber')}}" aria-describedby="basic-addon1" maxlength="50">-->
            </div>
        </div>
        <div class="row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="row">
                <div class="col-12">
                    <h3>Cantidad disponible</h3>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label  class="form-label">Stock Minimo:</label>
                 <input name="stockminimo" value="{{ old('stockminimo', optional($productoCopiar)->stockMin ?? 0) }}" type="number" class="form-control" >
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label for="precio-producto" class="form-label">Stock Proveedor:</label>
                 <input name="stockproveedor" value="{{ old('stockproveedor', optional(optional($productoCopiar)->Inventario_Proveedor)->stock ?? 0) }}" type="number" id="stockproveedor-product" class="form-control">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label for="grupo-product" id="proveedor-label" class="form-label">Proveedor:</label>
                <select name="proveedor" id="proveedor-product" class="form-select">
                     <option  value=""  {{ old('proveedor') ? '' : 'selected' }}>-Elige un proveedor-</p></option>
                    @foreach($proveedor as $pro)
                         <option  value="{{ $pro['idProveedor'] }}"
                            {{ old('proveedor', optional(optional($productoCopiar)->Inventario_Proveedor)->idProveedor ?? '') == $pro['idProveedor'] ? 'selected' : '' }}>
                            {{ $pro['nombreProveedor'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="row">
                <div class="col-12">
                    <h3>Videos (clips)</h3>
                </div>
            </div>
            <div class="mb-3">
                <label for="video1">URL del Video de Unike Store (Opcional)</label>
                 <input type="text" name="video1" id="video1" class="form-control" value="{{ old('video1', optional($productoCopiar)->videoUrl1 ?? '') }}" placeholder="Video Unike Store">
                placeholde
                <small class="form-text text-muted">Ingrese la primera URL oficial o de referencia del producto.</small>
            </div>
            <div class="mb-3">
                <label for="video2">URL del Video de la Marca (Opcional)</label>
                 <input type="text" name="video2" id="video2" class="form-control" value="{{ old('video2', optional($productoCopiar)->videoUrl2 ?? '') }}" placeholder="Video Marca">
                <small class="form-text text-muted">Ingrese la segunda URL oficial o de referencia del producto.</small>
            </div>
        </div>

        <div class="row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
        <div class="row">
            <div class="col-12">
                <h3>Detalles</h3>
            </div>
        </div>
        <div class="col-md-6">
            <label for="desc-producto" class="form-label" >Imagenes (Solo imagenes en 1000 x 1000px):</label>
            <div class="row">
                <div class="col-6 mb-3 img-div" id="previewImage1" data-bs-toggle="tooltip" data-bs-placement="top" title="Imagen de cabecera">
                    <input class="d-none img-input" name="imgone" type="file" accept="image/*" id="imgone-product" onchange="changeImage(event,this,'previewImage1','triggerImage1')">
                    <img src="{{ asset('storage/1000x1000image.webp') }}" alt="Click to upload" id="triggerImage1" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                </div>
                <div class="col-6 mb-3 img-div" id="previewImage2">
                    <input class="d-none img-input" name="imgtwo"  type="file" accept="image/*" id="imgtwo-product" onchange="changeImage(event,this,'previewImage2','triggerImage2')">
                    <img src="{{ asset('storage/1000x1000image.webp') }}" alt="Click to upload" id="triggerImage2" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                </div>
                <div class="col-6 img-div" id="previewImage3">
                     <input class="d-none img-input" name="imgtree" type="file" accept="image/*" id="imgtree-product" onchange="changeImage(event,this,'previewImage3','triggerImage3')">
                    <img src="{{ asset('storage/1000x1000image.webp') }}" alt="Click to upload" id="triggerImage3" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                </div>
                <div class="col-6 img-div" id="previewImage4">
                    <input class="d-none img-input" name="imgfour" type="file" accept="image/*" id="imgfour-product" onchange="changeImage(event,this,'previewImage4','triggerImage4')">
                    <img src="{{ asset('storage/1000x1000image.webp') }}" alt="Click to upload" id="triggerImage4" class="w-100 border border-secondary rounded-3 img-preview" style="cursor: pointer; object-fit: cover;">
                </div>
                
            </div>
        </div>        
        <div class="col-md-6">
            <label for="descripcion-product" class="form-label" >Descripcion:</label>
             <textarea name="desc" type="text" maxlength="5000" id="descripcion-product" class="form-control" style=" width: 100%;max-height: 500px;overflow-y: auto;" oninput="autoResize(this)">{{ old('desc', optional($productoCopiar)->descripcionProducto ?? '') }}</textarea>
        </div>
    </div>
       
        <div class="row mt-4 pt-4">
              <div class="col-12 text-center">
                  <button type="submit" onclick="manejarSubmit()"class="btn btn-success " id="btnRegistrar" >Registrar <i class="bi bi-floppy"></i></button>
              </div>
        </div>
      </form>
      <br>
    </div>
    <script src="{{ route('js.create-product-scripts',[$tc]) }}"></script>
    
@endsection