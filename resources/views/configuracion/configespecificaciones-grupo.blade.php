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
        <div class="col-7 col-md-9 d-flex align-items-center pt-1">
            <h3 class="mt-2 ms-1"><i class="{{$categoria->iconCategoria}}"></i> {{$categoria->nombreCategoria}}</h3>
        </div>
        <div class="col-5 col-md-3 d-flex align-items-center justify-content-end">
            <div class="btn-group dropstart">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Categorias
                </button>
                <ul class="dropdown-menu">
                    @foreach ($categorias as $cat)
                    <li><a class="dropdown-item"
                            href="{{route('configespecificacionesxgrupo',[encrypt($cat->idCategoria)])}}">{{$cat->nombreCategoria}}</a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="col-md-12 text-secondary">
            <small>Especificaciones asignadas para cada grupo.</small>
        </div>
        @foreach ($categoria->GrupoProducto as $grupo)
        <div class="col-md-12 mt-2">
            <div class="row border shadow rounded-3 mb-3">
                <div class="col-8 col-md-6 d-flex align-items-center border-bottom border-secondary">
                    <h5 class="pt-1">{{$grupo->nombreGrupo}}</h5>
                </div>
                <div class="col-4 col-md-6 border-bottom border-secondary text-end pt-1 pb-1">
                    <button class="btn btn-sm btn-success"
                        onclick="sendGrupoToModal({{$grupo->idGrupoProducto}},'{{$grupo->nombreGrupo}}')"
                        data-bs-toggle="modal" data-bs-target="#spectXGrupoModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="col-md-12 bg-list">
                    <div class="row pt-2 pe-2 pb-1" id="div-row-groupSpect-{{$grupo->idGrupoProducto}}">
                        @foreach ($grupo->Caracteristicas_Grupo as $caracteristica)
                        <div class="col-4 col-md-2 mb-1" id="div-groupSpect-{{$caracteristica->idGrupoProducto.'-'.$caracteristica->idCaracteristica}}">
                            <div class="row ms-1 h-100 border bg-light rounded-2 truncate">
                                <small>
                                    <a href="javascript:void(0)" class="text-dark link-danger"
                                        onclick="sendDataToDelete({{$caracteristica->idGrupoProducto}},{{$caracteristica->idCaracteristica}},'{{$grupo->nombreGrupo .'-'.$caracteristica->Caracteristicas->especificacion}}')"
                                        data-bs-toggle="modal" data-bs-target="#deleteSpectXGrupoModal">
                                        <i class="bi bi-x-lg "></i></a>
                                    {{$caracteristica->Caracteristicas->especificacion}}
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <form action="{{route('deletecaracteristicaxgrupo')}}" id="modal-delete-caracteristica" method="POST">
        @csrf
        <div class="modal fade" id="deleteSpectXGrupoModal" tabindex="-1" aria-labelledby="deleteSpectXGrupoModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteSpectXGrupoModalLabel">Estas seguro(a) de borrar esta
                            especificaci&oacute;n?
                            <span class="text-secondary fst-italic" id="title-modal-deleteespecificacionxgrupo"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <input type="hidden" value="" id="hidden-modal-deleteespecificacionxgrupo-caracteristica"
                            name="caracteristica">
                        <input type="hidden" value="" id="hidden-modal-deleteespecificacionxgrupo-grupo" name="grupo">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" onclick="submitFormDeleteCar()" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-trash3-fill"></i>
                            Borrar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <form action="{{route('insertcaracteristicaxgrupo')}}" method="POST" id="modal-add-caracteristica">
        @csrf
        <div class="modal fade" id="spectXGrupoModal" tabindex="-1" aria-labelledby="spectXGrupoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="spectXGrupoModalLabel">Agregar Especificaci&oacute;n <span id="title-modal-addespecificacionxgrupo"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name="grupo" value="" id="hidden-modal-addespecificacionxgrupo-grupo">
                                <input type="hidden" name="caracteristica" value="" id="hidden-modal-addespecificacionxgrupo-especificacion">
                                <input class="form-control" type="text" placeholder="Busca una caracteristica..." id="text-modal-addespecificacionxgrupo" readonly>
                            </div>
                            <div class="col-md-12 mt-3">
                                <input class="form-control" oninput="inputSearch(this);disableButtonSave();" type="text" placeholder="busca aqui!!">
                                <ul class="list-group" id="modal-addespecificacionxgrupo-results" style="position:absolute;top:100%;z-index:1000"></ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn-modal-addespecificacionxgrupo" data-bs-dismiss="modal"><i class="bi bi-floppy"></i> Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    var dataSpects = Object.values(@json($caracteristicas));
</script>
<script src="{{asset('js/configCaracteristicas_grupo.js')}}"></script>
@endsection