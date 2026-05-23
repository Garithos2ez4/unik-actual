@extends('layouts.app')

@section('title', 'Resultados de Búsqueda')

@section('content')
<div class="container">
    <br>
    <div class="row mb-3">
        <div class="col-8">
            <h2><i class="bi bi-search"></i> Resultados para: "{{ $termino }}"</h2>
            <h6 class="text-secondary">{{ count($publicaciones) }} coincidencias encontradas</h6>
        </div>
        <div class="col-4 text-end">
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
                                <small data-bs-toggle="tooltip" data-bs-placement="top" title="{{$public->titulo}}">
                                    <strong class="text-primary">{{$public->sku}}</strong>
                                </small>
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
</div>
@endsection
