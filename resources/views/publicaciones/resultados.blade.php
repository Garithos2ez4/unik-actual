@extends('layouts.app')

@section('title', 'Resultados de Búsqueda')

@section('content')
<div class="container">
    <div class="bg-secondary" id="hidden-body" style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
    </div>
    <br>
    <div class="row mb-3 align-items-center">
        <div class="col-12 col-md-5 mb-3 mb-md-0">
            <form action="{{ route('buscar-publicaciones') }}" method="GET" class="w-100">
                <div class="input-group">
                    <button type="submit" class="input-group-text btn btn-secondary" style="border-top-left-radius: 0.375rem; border-bottom-left-radius: 0.375rem;">
                        <i class="bi bi-search"></i>
                    </button>
                    <input type="text" name="q" class="form-control" placeholder="SKU, Título o Producto..." value="{{ $termino }}" required>
                </div>
            </form>
        </div>
        <div class="col-12 col-md-4 mb-3 mb-md-0 text-md-center">
            <h6 class="text-secondary mb-0"><strong>{{ count($publicaciones) }}</strong> coincidencias para "<strong>{{ $termino }}</strong>"</h6>
        </div>
        <div class="col-12 col-md-3 text-end">
            <a href="{{ route('publicaciones', [now()->format('Y-m')]) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Publicaciones
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12" style="overflow-x: hidden;overflow-y:auto;height: 70vh">
            @if(count($publicaciones) > 0)
                <ul class="list-group">
                    <li class="list-group-item bg-sistema-uno text-light pb-0" style="position:sticky; top: 0;z-index:800">
                        <div class="row text-center ">
                            <div class="col-3 col-md-1">
                                <h6>Seller</h6>
                            </div>
                            <div class="col-md-1 d-none d-sm-block">
                                <h6>Usuario</h6>
                            </div>
                            <div class="col-md-2 d-none d-sm-block">
                                <h6>Cuenta</h6>
                            </div>
                            <div class="col-5 col-md-2">
                                <h6>SKU</h6>
                            </div>
                            <div class="col-4 col-md-2">
                                <h6>Producto</h6>
                            </div>
                            <div class="col-md-1 d-none d-sm-block">
                                <h6>Precio</h6>
                            </div>
                            <div class="col-md-1 d-none d-sm-block">
                                <h6>Estado</h6>
                            </div>
                            <div class="col-md-1 d-none d-sm-block">
                                <h6>Actualizado</h6>
                            </div>
                        </div>
                    </li>
                    @foreach($publicaciones as $public)
                    <li class="list-group-item">
                        <div class="row text-center align-items-center">
                            <div class="col-3 col-md-1">
                                <img src="{{ asset('storage/'.$public->CuentasPlataforma->Plataforma->imagenPlataforma) }}" alt="Plataforma" style="width:100%" class="rounded-3">
                            </div>
                            <div class="col-md-1 d-none d-sm-block">
                                <small>{{$public->Usuario->user}}</small>
                            </div>
                            <div class="col-md-2 d-none d-sm-block">
                                <small>{{$public->CuentasPlataforma->nombreCuenta}}</small>
                            </div>
                            <div class="col-5 col-md-2">
                                <a href="javascript:void(0)" class="decoration-link" onclick="ShareId({{$public->idPublicacion}},'{{$public->titulo}}',{{$public->precioPublicacion}},{{$public->estado}})">
                                    <small data-bs-toggle="tooltip" data-bs-placement="top" title="{{$public->titulo}}">
                                        <strong class="text-primary">{{$public->sku}}</strong>
                                    </small>
                                </a>
                            </div>
                            <div class="col-4 col-md-2">
                                <small data-bs-toggle="tooltip" data-bs-placement="top" title="{{$public->Producto->modelo}}">
                                    <a href="{{route('producto',[encrypt($public->Producto->idProducto)])}}" class="decoration-link text-dark fw-bold">
                                        {{$public->Producto->codigoProducto}}
                                    </a>
                                </small>
                                <br>
                                <span class="badge bg-secondary text-truncate" style="max-width: 100%; font-size: 0.65rem;" title="{{$public->Producto->nombreProducto}}">
                                    {{$public->Producto->nombreProducto}}
                                </span>
                            </div>
                            <div class="col-md-1 d-none d-sm-block">
                                <small>S/ {{number_format($public->precioPublicacion,2)}}</small>
                            </div>
                            <div class="col-3 d-block d-sm-none"></div>
                            <div class="col-5 col-md-1">
                                <small >
                                    <span class="badge {{$public->estado == 1 ? 'bg-success' : ($public->estado == 0 ? 'bg-danger' : 'bg-dark text-decoration-line-through')}}">
                                        {{$public->estado == 1 ? 'Activo' : ($public->estado == 0 ? 'Inactivo' : 'Borrado')}}
                                    </span>
                                </small>
                            </div>
                            <div class="col-4 col-md-1">
                                <small>{{$public->fechaPublicacion->format('d/m/Y')}}</small>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @else
                <div class="alert alert-warning text-center mt-4">
                    <i class="bi bi-exclamation-circle-fill fs-4 d-block mb-2"></i>
                    No se encontraron resultados para "<strong>{{ $termino }}</strong>". 
                    Intenta probar con otro SKU o nombre de producto.
                </div>
            @endif
        </div>
    </div>
    <!-- Modal -->
    <form id="estadoForm" action="{{route('update-estado-publicacion')}}" method="POST">
        @csrf
        <div class="modal fade" id="estadoModal" tabindex="-1" aria-labelledby="estadoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="estadoModalLabel">Publicaci&oacute;n <span id="titlepubli"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" name="idpubli" value="" id="hidden-id">
                            <div class="col-md-12">
                                <label class="form-label">Titulo:</label>
                                <input type="text" name="titulo" class="form-control" id="title-text" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Precio:</label>
                                <input type="number" step="0.01" name="precio" class="form-control" id="price-number" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado:</label>
                                <select name="estado" id="estado-select" class="form-select">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                    <option value="-1">Borrado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer ">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill"></i> Actualizar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h5 id="titlepublicacion-modal-detail">[titulo de la publicacion]</h5>
                        </div>
                        <div class="col-6 text-secondary">
                            <h6 id="sku-modal-detail">[numero de sku]</h6>
                        </div>
                        <div class="col-6 text-end">
                            <span id="user-modal-detail">[usuario]</span>
                        </div>
                        <div class="col-6">
                            <span id="state-modal-detail">[Estado]</span>
                        </div>
                        <div class="col-6 text-end">
                            <span id="date-modal-detail">[fechadepubli]</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{asset('js/publish.js')}}"></script>
@endsection
