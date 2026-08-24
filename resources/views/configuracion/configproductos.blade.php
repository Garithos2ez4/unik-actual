@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-md-12">
            <h2><i class="bi bi-gear-fill"></i> Configuración</h2>
        </div>
    </div>
    <br>
    <div class="col-md-12">
        <x-nav_config :pag="$pagina" />
    </div>
    <br>
    
    @include('configuracion.components.grupos_lista', ['categorias' => $categorias])
    
    <br>
    @include('configuracion.components.marcas_lista', ['marcas' => $marcas])
    
    @include('configuracion.components.alertas_precios_lista', ['alertas' => $alertas])

    @include('configuracion.components.mappers_lista', ['mappers' => $mappers, 'plataformas' => $plataformas, 'categorias' => $categorias])

    <form action="{{route('insertgrupo')}}" method="post" enctype="multipart/form-data" id="form-insert-grupo">
        @csrf
        <div class="modal fade" id="grupoModal" tabindex="-1" aria-labelledby="grupoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <h1 class="modal-title fs-5" id="grupoModalLabel">Nuevo Grupo</h1>
                            <small class="text-secondary" id="title-modal-grupo"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="body-modal-grupo">
                        <div class="row">
                            <input type="hidden" name="categoria" value="" id="hidde-modal-grupo">
                            <div class="col-md-4" id="drop-area-grupo" class="drop-area">
                                <input class="d-none" id="file-modal-grupo" name="img" type="file" accept="image/*">
                                <img src="https://placehold.co/300x300"  alt="Click to upload" id="img-modal-grupo" class="w-100 border border-secondary rounded-3" style="cursor: pointer; object-fit: cover;">
                            </div>
                            <div class="col-md-8 mb-3">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Nombre del Grupo:</label>
                                        <input type="text" maxlength="50" class="form-control" name="grupo">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Tipo de producto</label>
                                        <select name="tipo" class="form-select">
                                            <option value="" selected>-Elige-</option>
                                            @foreach ($tipos as $tipo)
                                            <option value="{{$tipo->idTipoProducto}}">{{$tipo->tipoProducto}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                            </div>
                            
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" onclick="validateForm('form-insert-grupo')" class="btn btn-primary" id="btn-modal-grupo"><i class="bi bi-floppy-fill"></i> Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <form action="{{route('insertmarca')}}" method="post" enctype="multipart/form-data" id="form-insert-marca">
        @csrf
        <div class="modal fade" id="marcaModal" tabindex="-1" aria-labelledby="marcaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="marcaModalLabel">Nueva Marca</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body-modal-marcas">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nombre de la Marca:</label>
                        <input type="text" maxlength="50" class="form-control" name="nombre" id="">
                    </div>
                    <div class="col-md-12" id="drop-area-marca" class="drop-area">
                        <input class="d-none" id="file-modal-marca" name="img" type="file" accept="image/*">
                        <img src="https://placehold.co/1000x400"  alt="Click to upload" id="img-modal-marca" class="w-100 border border-secondary rounded-3" style="cursor: pointer; object-fit: cover;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" onclick="validateForm('form-insert-marca')" class="btn btn-primary" id="btn-modal-marca"><i class="bi bi-floppy-fill"></i> Guardar</button>
            </div>
          </div>
        </div>
      </div>
    </form>
    <form action="{{route('insertcategoria')}}" method="post" id="form-insert-categoria">
        @csrf
        <div class="modal fade" id="categoriaModal" tabindex="-1" aria-labelledby="categoriaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="categoriaModalLabel">Nueva Categoría</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body-modal-categoria">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nombre de la Categoría:</label>
                        <input type="text" maxlength="50" class="form-control" name="nombreCategoria" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Icono de la Categoría (Clase bi):</label>
                        <input type="text" maxlength="50" class="form-control" name="iconCategoria" placeholder="ej. bi bi-laptop">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" onclick="validateForm('form-insert-categoria')" class="btn btn-primary" id="btn-modal-categoria"><i class="bi bi-floppy-fill"></i> Guardar</button>
            </div>
          </div>
        </div>
      </div>
    </form>
    
    <form action="{{route('updatecategoria')}}" method="post" id="form-update-categoria">
        @csrf
        <div class="modal fade" id="editCategoriaModal" tabindex="-1" aria-labelledby="editCategoriaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="editCategoriaModalLabel">Editar Categoría</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body-modal-edit-categoria">
                <div class="row">
                    <input type="hidden" name="idCategoria" id="edit-id-categoria">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nombre de la Categoría:</label>
                        <input type="text" maxlength="50" class="form-control" name="nombreCategoria" id="edit-nombre-categoria" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Icono de la Categoría (Clase bi):</label>
                        <input type="text" maxlength="50" class="form-control" name="iconCategoria" id="edit-icon-categoria" placeholder="ej. bi bi-laptop">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" onclick="validateForm('form-update-categoria')" class="btn btn-primary" id="btn-modal-edit-categoria"><i class="bi bi-floppy-fill"></i> Guardar</button>
            </div>
          </div>
        </div>
      </div>
    </form>

    <form action="{{route('updategrupo')}}" method="post" enctype="multipart/form-data" id="form-update-grupo">
        @csrf
        <div class="modal fade" id="editGrupoModal" tabindex="-1" aria-labelledby="editGrupoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <h1 class="modal-title fs-5" id="editGrupoModalLabel">Editar Grupo</h1>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="body-modal-edit-grupo">
                        <div class="row">
                            <input type="hidden" name="idGrupo" value="" id="edit-id-grupo">
                            <div class="col-md-4" id="drop-area-edit-grupo" class="drop-area">
                                <input class="d-none" id="file-modal-edit-grupo" name="img" type="file" accept="image/*">
                                <img src="https://placehold.co/300x300?text=Cambiar+Imagen"  alt="Click to upload" id="img-modal-edit-grupo" class="w-100 border border-secondary rounded-3" style="cursor: pointer; object-fit: cover;">
                            </div>
                            <div class="col-md-8 mb-3">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Nombre del Grupo:</label>
                                        <input type="text" maxlength="50" class="form-control" name="grupo" id="edit-nombre-grupo">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Tipo de producto</label>
                                        <select name="tipo" id="edit-tipo-grupo" class="form-select">
                                            <option value="">-Elige-</option>
                                            @foreach ($tipos as $tipo)
                                            <option value="{{$tipo->idTipoProducto}}">{{$tipo->tipoProducto}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                            </div>
                            
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" onclick="validateForm('form-update-grupo')" class="btn btn-primary" id="btn-modal-edit-grupo"><i class="bi bi-floppy-fill"></i> Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="{{asset('js/configproductos.js')}}"></script>
@endsection
