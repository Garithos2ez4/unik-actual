@extends('layouts.app')

@section('title', 'Productos | ' . $categoria['nombreCategoria'])

@section('content')

    <div class="container">
        <br>
        <div class="row">
            <div class="col-9 col-lg-4">
                <x-buscador_producto />
            </div>
            <div class="col-3 col-lg-8 text-end">
                <a href="{{ route('createproducto') }}" target="_blank" class="btn btn-success"><i class="bi bi-plus-square"></i> <span class="d-none d-md-inline"> Nuevo Producto</span></a>
            </div>
            <div class="col-6 col-md-8 pt-2">
                <h3><i class="bi bi-box-fill"></i> Productos</h3>
            </div>
            <div class="col-6 col-md-4 text-end">
                <div class="btn-group dropstart">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                        data-bs-display="static" aria-expanded="false">
                        Categorías
                    </button>
                    <ul class="dropdown-menu dropdown-menu-lg-end">
                        @foreach ($categorias as $cat)
                            @if (count($cat['GrupoProducto']) > 0)
                                <li><a class="dropdown-item text-dark" type="button"
                                        href="{{ route('productos', [encrypt($cat['idCategoria']), encrypt($cat['GrupoProducto'][0]->idGrupoProducto)]) }}">{{ $cat['nombreCategoria'] }}</a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-7 col-md-8">
                <h3 class="">{{ $categoria['nombreCategoria'] }} <em
                        class="fw-light text-secondary">({{ $grupo['nombreGrupo'] }})</em></h3>
            </div>
        </div>
        <br>
        
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs d-none d-sm-none d-md-flex">
                    @foreach ($grupos as $gp)
                        @if ($gp['idGrupoProducto'] == $grupo['idGrupoProducto'])
                            <li class="nav-item">
                                <a class="nav-link active bg-sistema-dos text-light"
                                    href="#">{{ $gp['nombreGrupo'] }}</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link text-dark"
                                    href="{{ route('productos', [encrypt($categoria['idCategoria']), encrypt($gp['idGrupoProducto'])]) }}">{{ $gp['nombreGrupo'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <ul class="nav nav-tabs  flex-nowrap d-flex d-sm-none" style="overflow-y: hidden;overflow-x: auto;">
                    @foreach ($grupos as $gp)
                        @if ($gp['idGrupoProducto'] == $grupo['idGrupoProducto'])
                            <li class="nav-item">
                                <a class="nav-link active bg-sistema-dos text-light"
                                    href="#">{{ $gp['nombreGrupo'] }}</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link text-dark"
                                    href="{{ route('productos', [encrypt($categoria['idCategoria']), encrypt($gp['idGrupoProducto'])]) }}">{{ $gp['nombreGrupo'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
        <x-filtro_productos :marcas="$filtros['marcas']" :estados="$filtros['estados']" />
        <div id="container-list-products">
            <x-lista_producto :productos="$productos" :tc="$tc" :container="'container-list-products'" />
        </div>
        </div>
    </div>
    
    @include('productos.partials.modal_ubicacion_base')

    <script src="{{asset('js/filtro_componente.js')}}"></script>
@endsection
