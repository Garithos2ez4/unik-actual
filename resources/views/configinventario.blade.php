@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-md-12">
            <h2><i class="bi bi-gear-fill"></i> Configuraci&oacuten</h2>
        </div>
    </div>
    <br>
    <div class="col-md-12">
        <x-nav_config :pag="$pagina" />
    </div>
    <br>
    <div class="row border shadow rounded-3 pt-2 mb-4">
        <div class="col-9 col-md-8 border-bottom border-secondary">
            <h3>Almacenes</h3>
            <small class="text-secondary">Configuracion de almacen relacionado al inventario .</small>
        </div>
        <div class="col-3 col-md-4 border-bottom border-secondary text-end" data-bs-toggle="modal" data-bs-target="#almacenModal">
            <button class="btn btn-success"><i class="bi bi-house-add-fill"></i></button>
        </div>
        <div class="col-md-12 pt-4 pb-3 bg-list">
            <div class="row px-2">
                <!-- Columna Izquierda: Pestañas de Almacenes -->
                <div class="col-md-3 mb-3">
                    <div class="nav flex-column nav-pills shadow-sm rounded-3 bg-white" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        @foreach ($almacenes as $index => $almacen)
                        <button class="nav-link text-start py-3 {{ $index == 0 ? 'active' : '' }} border-bottom" 
                            id="v-pills-almacen-{{$almacen->idAlmacen}}-tab" 
                            data-bs-toggle="pill" 
                            data-bs-target="#v-pills-almacen-{{$almacen->idAlmacen}}" 
                            type="button" role="tab" 
                            aria-controls="v-pills-almacen-{{$almacen->idAlmacen}}" 
                            aria-selected="{{ $index == 0 ? 'true' : 'false' }}">
                            <h6 class="mb-0"><i class="bi bi-house-door-fill me-2"></i> {{$almacen->descripcion}}</h6>
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Columna Derecha: Contenido del Almacén seleccionado (Estantes) -->
                <div class="col-md-9">
                    <div class="tab-content bg-white shadow-sm rounded-3 p-3 border" id="v-pills-tabContent">
                        @foreach ($almacenes as $index => $almacen)
                        <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" 
                            id="v-pills-almacen-{{$almacen->idAlmacen}}" 
                            role="tabpanel" 
                            aria-labelledby="v-pills-almacen-{{$almacen->idAlmacen}}-tab">
                            
                            <!-- Cabecera del Almacén -->
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                <h4 class="text-primary mb-0"><i class="bi bi-house-door"></i> {{$almacen->descripcion}}</h4>
                                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#rackModal{{$almacen->idAlmacen}}" title="Agregar Rack/Estante">
                                    <i class="bi bi-plus-circle me-1"></i> Nuevo Rack
                                </button>
                            </div>

                            <!-- Listado de Estantes usando el componente -->
                            <div class="row">
                                <x-lista_estantes :almacen="$almacen" />
                            </div>
                        </div>

                        <!-- Modal para Nuevo Rack -->
                        <form action="{{route('createubicacion')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="idAlmacen" value="{{$almacen->idAlmacen}}">
                            <div class="modal fade" id="rackModal{{$almacen->idAlmacen}}" tabindex="-1" aria-labelledby="rackModalLabel{{$almacen->idAlmacen}}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content shadow">
                                        <div class="modal-header bg-primary text-white border-0">
                                            <h5 class="modal-title fs-5 fw-bold" id="rackModalLabel{{$almacen->idAlmacen}}">
                                                <i class="bi bi-plus-circle me-1"></i> Nuevo Rack/Estante - {{$almacen->descripcion}}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body text-start bg-light">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="nombre" placeholder="Ej. Rack A, Estante 1" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Descripción <span class="text-muted fw-normal">(Opcional)</span></label>
                                                <textarea class="form-control" name="descripcion" rows="2" placeholder="Detalles sobre su ubicación..."></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">¿Cuántas filas tiene? <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="num_filas" value="1" min="1" max="20" required>
                                                <small class="text-muted">Se crearán automáticamente estas filas (puedes agregar/quitar luego).</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Foto del Estante/Rack <span class="text-muted fw-normal">(Opcional)</span></label>
                                                <input type="file" class="form-control" name="foto" accept="image/*">
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-floppy-fill me-1"></i> Guardar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row border shadow rounded-3 pt-2  mb-4">
        <div class="col-9 col-md-8 border-bottom border-secondary">
            <h3>Proveedores</h3>
            <small class="text-secondary">Configuracion de proveedores para los ingresos y seguimiento de stock.</small>
        </div>
        <div class="col-3 col-md-4 border-bottom border-secondary text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#proveedorModal"><i class="bi bi-plus-lg"></i> <i class="bi bi-truck"></i></button>
        </div>
        <div class="col-md-12 pt-2 pb-2 bg-list">
            <div class="row">
                @foreach ($proveedores as $proveedor)
                <div class="col-md-3 pb-2">
                    <div class="row bg-light border ms-2 me-2 pt-2 h-100">
                        <h5>{{$proveedor->nombreProveedor}}</h5>
                        <small class="text-secondary">{{$proveedor->razSocialProveedor}}</small>
                        <small>{{$proveedor->rucProveedor}}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <!--Modals -->
    <form action="{{route('createalmacen')}}" id="form-almacen" method="POST">
        @csrf
        <div class="modal fade" id="almacenModal" tabindex="-1" aria-labelledby="almacenModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="almacenModalLabel">Nuevo almacen</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <input type="text" maxlength="50" class="form-control" name="descripcion" placeholder="Nombre del Almacen" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="validateForm('form-almacen')"><i class="bi bi-floppy-fill"></i> Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <form action="{{route('createproveedor')}}" id="form-proveedor" method="POST">
        @csrf
        <div class="modal fade" id="proveedorModal" tabindex="-1" aria-labelledby="proveedorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="proveedorModalLabel">Nuevo proveedor</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="" class="form-label">Raz&oacute;n Social:</label>
                                <input type="text" class="form-control" value="" name="razonsocial" placeholder="EMPRESA S.A.C" required>
                            </div>
                            <div class="col-md-6">
                                <label for="" class="form-label">Nombre Comercial:</label>
                                <input type="text" class="form-control" value="" name="nombrecomercial" placeholder="Nombre Empresa" required>
                            </div>
                            <div class="col-md-6">
                                <label for="" class="form-label">RUC:</label>
                                <input type="text" maxlength="11" class="form-control" value="" name="ruc" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" id="btn-modal-proveedor" onclick="validateForm('form-proveedor')"><i class="bi bi-floppy-fill"></i> Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="{{asset('js/configinventario.js')}}"></script>
@endsection