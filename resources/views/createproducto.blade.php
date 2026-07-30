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
                <label for="categoria-product" class="form-label">Categoría:</label>
                <select id="categoria-product" class="form-select">
                    <option value="">-Todas-</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat['nombreCategoria'] }}">{{ $cat['nombreCategoria'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 col-12 col-lg-4">
                <label for="grupo-product" id="grupo-label" class="form-label d-flex justify-content-between align-items-center">
                    <span>Grupo:</span>
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 border-0" data-bs-toggle="modal" data-bs-target="#quickGrupoModal" style="font-size: 0.85rem; font-weight: 500;">
                        <i class="bi bi-plus-circle-fill"></i> Nuevo
                    </button>
                </label>
                <select name="grupo" id="grupo-product" class="form-select">
                        <option value="" {{ old('grupo') ? '' : 'selected' }}>-Elige un grupo-</option>
                    @foreach($grupos as $grupo)
                        @php
                            $gp = \App\Models\GrupoProducto::find($grupo['idGrupoProducto']);
                            $catName = $gp && $gp->CategoriaProducto ? $gp->CategoriaProducto->nombreCategoria : '';
                        @endphp
                        <option value="{{ $grupo['idGrupoProducto'] }}"
                            data-categoria="{{ $catName }}"
                            {{ old('grupo', optional($productoCopiar)->idGrupo ?? '') == $grupo['idGrupoProducto'] ? 'selected' : '' }}>
                            {{ $grupo['nombreGrupo'] }}
                        </option>
                    @endforeach
                </select>
                <div id="container-codigo-wrapper" class="mt-2 d-none">
                    <div class="card border-primary shadow-sm bg-light" style="border-left: 4px solid #0d6efd !important;">
                        <div class="card-body py-2.5 px-3">
                            <label for="codigo-product" class="form-label fw-bold text-primary mb-1" style="font-size: 0.85rem;">
                                <i class="bi bi-key-fill me-1"></i> Código de Producto (Editable)
                            </label>
                            <div class="input-group input-group-sm mb-2" style="max-width: 280px;">
                                <input type="text" name="codigo" value="ERROR" id="codigo-product" class="form-control fw-bold text-uppercase text-center border-2 border-primary py-1.5" placeholder="Ej: LAPGAM" maxlength="10" style="letter-spacing: 1.5px; font-size: 0.95rem;">
                                <span class="input-group-text bg-white border-primary text-muted fw-semibold" style="font-size: 0.75rem;">6 carac.</span>
                            </div>
                            <div id="codigo-product-help" class="fs-7 lh-sm"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 col-6 col-lg-4">
                <label for="estado-product" id="estado-label" class="form-label">
                    <i class="bi bi-info-circle-fill" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<small>DISPONIBLE: En stock.</small><br><small>AGOTADO: Sin stock</small><br><small>EXCLUSIVO: Precio sin calculos.</small>"></i> 
                    Estado:
                </label>
                <select name="estado" id="estado-product" class="form-select">
                  <option value="" {{ old('estado') ? '' : 'selected' }}>-Elige un estado-</option>
                  <option value="DISPONIBLE" {{ old('estado', optional($productoCopiar)->estadoProductoWeb ?? '') == 'DISPONIBLE' ? 'selected' : '' }}>DISPONIBLE</option>
                  <option value="AGOTADO" {{ old('estado', optional($productoCopiar)->estadoProductoWeb ?? '') == 'AGOTADO' ? 'selected' : '' }}>AGOTADO</option>
                  <option value="OFERTA" {{ old('estado', optional($productoCopiar)->estadoProductoWeb ?? '') == 'OFERTA' ? 'selected' : '' }}>OFERTA</option>
                  <option value="LIQUIDACION" {{ old('estado', optional($productoCopiar)->estadoProductoWeb ?? '') == 'LIQUIDACION' ? 'selected' : '' }}>LIQUIDACION</option>
                  <option value="EXCLUSIVO" {{ old('estado', optional($productoCopiar)->estadoProductoWeb ?? '') == 'EXCLUSIVO' ? 'selected' : '' }}>EXCLUSIVO</option>
                  <option value="DESCONTINUADO" {{ old('estado', optional($productoCopiar)->estadoProductoWeb ?? '') == 'DESCONTINUADO' ? 'selected' : '' }}>DESCONTINUADO</option>
                </select>
            </div>
            <div class="mb-3 col-6 col-lg-4">
                <label for="garantia-product" id="garantia-label" class="form-label">Garantia:</label>
                <select name="garantia" id="garantia-product" class="form-select">
                  <option value="" {{ old('garantia') ? '' : 'selected' }}>-Elige la garantia-</option>
                  <option value="No tiene" {{ old('garantia', optional($productoCopiar)->garantia ?? '') == 'No tiene' ? 'selected' : '' }}>No tiene</option>
                  <option value="3 meses" {{ old('garantia', optional($productoCopiar)->garantia ?? '') == '3 meses' ? 'selected' : '' }}>3 meses</option>
                  <option value="6 meses" {{ old('garantia', optional($productoCopiar)->garantia ?? '') == '6 meses' ? 'selected' : '' }}>6 meses</option>
                  <option value="12 meses" {{ old('garantia', optional($productoCopiar)->garantia ?? '') == '12 meses' ? 'selected' : '' }}>12 meses</option>
                  <option value="24 meses" {{ old('garantia', optional($productoCopiar)->garantia ?? '') == '24 meses' ? 'selected' : '' }}>24 meses</option>
                  <option value="36 meses" {{ old('garantia', optional($productoCopiar)->garantia ?? '') == '36 meses' ? 'selected' : '' }}>36 meses</option>
                </select>
            </div>
        </div>
        <div class="row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
            <div class="col-12">
                <h3>Precios y Costos:</h3>
            </div>
            <div class="col-6 col-md-4">
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
                         <input type="number" name="precio" value="{{ old('precio', optional($productoCopiar)->precioDolar ?? 0) }}" id="precio-product"  aria-label="Last name" class="form-control  price-product" step="0.01">
                    </div>
                    <div class="col-md-6"></div>
                    <div class="mb-3 col-md-6">
                        <label for="precio-producto" class="form-label">Con IGV:</label>
                         <input type="number"  id="precio-product-igv" value="0" class="form-control price-product" step="0.01">
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="row">
                    <h5>Precio venta</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-12">
                        <label for="precio-producto" class="form-label">Utilidad:</label>
                          <input type="number"  value="{{ old('ganancia', optional($productoCopiar)->gananciaExtra ?? 0) }}" name="ganancia" id="precio-product-ganancia"  class="form-control price-product" step="0.01">
                    </div>
                    <div class="mb-3 col-12">
                        <label for="precio-producto" class="form-label">Precio Calculado:</label>
                          <input type="number"  value="" id="precio-product-calculado" class="form-control price-product" step="0.01" disabled>
                    </div>
                    <div class="row" id="div-total-price">
                    </div>
                    </br>
                    <div id="div-precio-total-sunat" class="mb-2">
                        <label for="precio-total-sunat" class="form-label">Precio Total en Soles (TC SUNAT: {{ $tc }})</label>
                        <input type="number" id="precio-total-sunat" value="" class="form-control" disabled>
                    </div>
                    <div id="div-precio-total-fijo" class="mb-2">
                        <label for="precio-total-fijo" class="form-label">Precio Total en Soles / Tasa Fija ({{ $tasaFija }})</label>
                        <input type="number" id="precio-total-fijo" value="" class="form-control" disabled>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 border-start ps-md-4">
                <div class="row">
                    <h5>Configuración de TC</h5>
                </div>
                <div class="row">
                    <div class="mb-3 col-12">
                        <label class="form-label">Tipo de Cambio:</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="usar_tc_fijo" value="0">
                            <input class="form-check-input"
                                type="checkbox"
                                name="usar_tc_fijo"
                                id="usar_tc_fijo"
                                value="1"
                                {{ old('usar_tc_fijo', optional($productoCopiar)->usar_tc_fijo ?? 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="usar_tc_fijo">
                                Usar TC Fijo / SUNAT
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Actívalo para usar la tasa oficial de SUNAT o una personalizada.</small>
                    </div>
                    
                    <div class="mb-3 col-12">
                        <label class="form-label">TC Personalizado:</label>
                        <input type="number" name="tc_fijo" step="0.01" class="form-control" id="tc_fijo_personalizado" value="{{ old('tc_fijo', optional($productoCopiar)->tc_fijo ?? '') }}" placeholder="Ej: 3.80">
                    </div>
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
                  <button type="submit" class="btn btn-success" id="btnRegistrar">Registrar <i class="bi bi-floppy"></i></button>
              </div>
        </div>
      </form>
      <br>

      <!-- Modal para Creación Rápida de Grupo -->
      <div class="modal fade" id="quickGrupoModal" tabindex="-1" aria-labelledby="quickGrupoModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content shadow-lg border-0 rounded-4">
                  <div class="modal-header bg-gradient-primary text-white border-0 py-3 rounded-top-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                      <h5 class="modal-title fw-bold" id="quickGrupoModalLabel">
                          <i class="bi bi-folder-plus me-2"></i> Crear Nuevo Grupo
                      </h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btn-close-quick-modal"></button>
                  </div>
                  <form id="form-quick-grupo" enctype="multipart/form-data">
                      @csrf
                      <div class="modal-body p-4">
                          <div id="quick-grupo-alert" class="alert alert-danger d-none py-2 px-3 fs-7" role="alert"></div>
                          
                          <div class="mb-3">
                              <label for="quick-categoria" class="form-label fw-semibold">Categoría General</label>
                              <select id="quick-categoria" name="categoria" class="form-select border-2" required>
                                  <option value="" selected>- Selecciona la categoría -</option>
                                  @foreach($categorias as $cat)
                                      <option value="{{ $cat['idCategoria'] }}">{{ $cat['nombreCategoria'] }}</option>
                                  @endforeach
                              </select>
                          </div>
                          
                          <div class="mb-3">
                              <label for="quick-grupo" class="form-label fw-semibold">Nombre del Grupo</label>
                              <input type="text" id="quick-grupo" name="grupo" class="form-control border-2" placeholder="Ej: Laptops Premium, Audífonos Gamer" required maxlength="100">
                          </div>
                          
                          <div class="mb-3">
                              <label for="quick-tipo" class="form-label fw-semibold">Tipo de Producto</label>
                              <select id="quick-tipo" name="tipo" class="form-select border-2" required>
                                  <option value="" selected>- Selecciona el tipo -</option>
                                  @foreach($tipos as $tipoOption)
                                      <option value="{{ $tipoOption->idTipoProducto }}">{{ $tipoOption->tipoProducto }}</option>
                                  @endforeach
                              </select>
                          </div>
                          
                          <div class="mb-3">
                              <label class="form-label fw-semibold">Imagen del Grupo (Requerido)</label>
                              <div class="border border-2 border-dashed rounded-3 p-3 text-center bg-light position-relative hover-shadow" id="quick-img-drag-container" style="border-style: dashed !important; cursor: pointer; position: relative;">
                                  <input type="file" id="quick-img" name="img" accept="image/*" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer; z-index: 2;" required>
                                  <i class="bi bi-cloud-upload fs-1 text-primary mb-2 d-block" id="quick-img-icon"></i>
                                  <span class="d-block text-secondary fs-7 fw-medium" id="quick-img-label">Arrastra o haz clic para subir imagen</span>
                                  <img id="quick-img-preview" src="" alt="Vista Previa" class="w-50 mt-2 border rounded d-none" style="object-fit: cover; max-height: 120px; position: relative; z-index: 3;">
                              </div>
                          </div>
                      </div>
                      <div class="modal-footer bg-light border-0 py-3 rounded-bottom-4 justify-content-between">
                          <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal">Cancelar</button>
                          <button type="submit" class="btn btn-primary px-4 py-2" id="btn-save-quick-grupo">
                              <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true" id="quick-grupo-spinner"></span>
                              <span id="quick-grupo-btn-text">Guardar Grupo <i class="bi bi-check2-all"></i></span>
                          </button>
                      </div>
                  </form>
              </div>
          </div>
      </div>
      </div>
    </div>
      <script>
          window.APP_DATA = {
              tc: {{ $tc ?? '0' }},
              tasaFija: {{ $tasaFija ?? '0' }},
              codigos: @json($codigos->mapWithKeys(function($cod) {
                  return [$cod->codigoProducto => $cod->idGrupo];
              }))
          };
      </script>
      <script src="{{ asset('js/create-product-scripts.js') }}?v=1.01"></script>
@endsection