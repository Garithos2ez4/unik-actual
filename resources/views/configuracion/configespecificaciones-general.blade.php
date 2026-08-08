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
        <x-nav_config :pag="$pagina" :subDivide="$subDivide" />
    </div>
    <br>
    <div class="row">
        <div class="col-8 col-md-6">
            <h3 class="mt-2">Especificaciones</h3>
        </div>
        <div class="col-4 col-md-6 text-end pt-2">
            <a class="btn btn-primary" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createSpectModal">
                <i class="bi bi-plus-circle" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Nueva especificacion"></i>
            </a>
        </div>
        <div class="col-md-12 text-secondary">
            <small>Especificaciones generales para todos los grupos.</small>
        </div>
        <div class="col-md-12 mt-2">
            <ul class="list-group">
                <li class="list-group-item bg-sistema-uno text-light">
                    <div class="row">
                        <div class="col-md-3">
                            <h6>Especificacion</h6>
                        </div>
                        <div class="col-md-8 text-center">
                            <h6>Grupos</h6>
                        </div>
                    </div>
                </li>
                @foreach ($caracteristicas as $caracteristica)
                 @if($caracteristica->tipo != 'INVALIDO')
                 <li class="list-group-item">
                    <div class="row h-100">
                        <div class="col-md-3">
                            <strong>{{$caracteristica->especificacion}}</strong>
                            <br>
                            <small>{{$caracteristica->tipo}}</small>
                        </div>
                        <div class="col-md-8 text-secondary text-center">
                            @foreach ($caracteristica->Caracteristicas_Grupo as $grupo)
                            {{ $loop->index  == 0 ? '' : ' | '}}
                            <small class="text-decoration-underline">{{$grupo->GrupoProducto->nombreGrupo}}</small>
                            @endforeach
                        </div>
                        <div class="col-md-1 text-end">
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                data-bs-target="#removeSpectModal" 
                                onclick='sendDataToEdit({{$caracteristica->idCaracteristica}},
                                                        "{{$caracteristica->tipo}}",
                                                        "{{$caracteristica->especificacion}}",
                                                        @json($caracteristica->Caracteristicas_Sugerencias
                                                                ->sortBy("sugerencia")))'>
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                        </div>
                    </div>
                </li> 
                 @endif
                    
                @endforeach
            </ul>
        </div>
    </div>
    <form action="{{route('createcaracteristica')}}" method="POST">
        @csrf
        <div class="modal fade" id="createSpectModal" tabindex="-1" aria-labelledby="createSpectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createSpectModalLabel">Agregar Especificaci&oacute;n</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-8">
                                <label>Especificacion:</label>
                                <input type="text" name="descripcion" class="form-control" required>
                            </div>
                            <div class="col-4">
                                <label>Tipo:</label>
                                <select name="tipo"  onchange="changeTypeCreate(this)" class="form-select" required>
                                    <option value="FILTRO">Filtro</option>
                                    <option value="DETALLE" selected>Detalle</option>
                                </select>
                            </div>
                            <div class="col-12" id="filtros-modal-createespecificacion">

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-floppy"></i> Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <form action="{{route('updatecaracteristica')}}" method="POST">
        @csrf
        <div class="modal fade" id="removeSpectModal" tabindex="-1" aria-labelledby="removeSpectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="removeSpectModalLabel">
                            <span id="title-modal-editespecificacion"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" value="" id="hidden-modal-editespecificacion" name="id">
                            <input type="hidden" value="" id="operacion-modal-editespecificacion" name="operacion">
                            <div class="col-12">
                                <label class="form-label">Tipo de caracteristica:</label>
                                <select name="tipo" id="tipo-modal-editespecificacion" onchange="changeTypeSugerencia(this)" class="form-select">
                                    <option value="FILTRO">Filtro</option>
                                    <option value="DETALLE">Detalle</option>
                                </select>
                            </div>
                            <div class="col-12" id="filtros-modal-editespecificacion">

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row w-100">
                            <div class="col-4">
                                <button type="submit" onclick="changeOperacionModal('DELETE')" class="btn btn-danger"><i class="bi bi-trash3-fill"></i> Borrar</button>
                            </div>
                            <div class="col-8 text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" onclick="changeOperacionModal('UPDATE')" class="btn btn-success ms-1"><i class="bi bi-floppy-fill"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <form action="{{route('removesugerencia')}}" method="post" id="form-delete-sugerencia">
        @csrf
        <input type="hidden" name="sugerencia" value="" id="hidden-delete-sugerencia">
        <input type="hidden" name="type" value="" id="hidden-delete-sugerencia-type">
    </form>
</div>
<script src="{{asset('js/configCaracteristicas_general.js')}}"></script>
@endsection