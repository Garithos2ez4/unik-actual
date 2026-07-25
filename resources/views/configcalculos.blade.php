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
    <div class="row border shadow rounded-3 pt-2 pb-2 calculos-container sunat-container">
        <form action="{{route('updatecalculos')}}" method="POST">
             @csrf
        <div class="col-md-12">
            <div class="row">
                <div class="col-8 col-md-6">
                    <h3>Calculos Generales - Tasa de Cambio Sunat</h3>
                    <p class="text-secondary">Valores que se aplican a los precios de manera general.</p>
                </div>
                <div class="col-4 col-md-6 text-end">
                    <button class="btn btn-success btn-save-calculos" type="submit"> <i class="bi bi-floppy"></i></button>
                    <button class="btn btn-secondary btn-edit-calculos" type="button"> <i class="bi bi-pencil"></i></button>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="row">
                <div class="col-4 col-md-2">
                    <label>IGV:</label>
                    <div class="input-group mb-3">
                      <span class="input-group-text" id="basic-addon1">%</span>
                      <input type="number" class="form-control input-edit" name="igv" placeholder="IGV" step="0.01" aria-describedby="basic-addon1" value="{{$calculos->igv}}" >
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <label>T. Facturaci&oacuten:</label>
                    <div class="input-group mb-3">
                      <span class="input-group-text" id="basic-addon1">%</span>
                      <input type="number" class="form-control input-edit" name="facturacion" placeholder="Faturacion" step="0.01"  aria-describedby="basic-addon1" value="{{$calculos->facturacion}}" >
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <label><i class="bi bi-info-circle-fill" data-bs-toggle="tooltip" data-bs-placement="top" title="La tasa de cambio se actualiza diariamente"></i> T.C:</label>
                    <div class="input-group mb-3">
                      <span class="input-group-text" id="basic-addon1">$</span>
                      <input type="number" class="form-control" placeholder="T.C." step="0.01" aria-describedby="basic-addon1" value="{{$calculos->tasaCambio}}" disabled>
                    </div>
                </div>
                @foreach($empresas as $empresa)
                    <div class="col-6 col-md-3">
                        <label>{{$empresa->nombreComercial}}:</label>
                        <div class="input-group mb-3">
                          <span class="input-group-text" id="basic-addon1">%</span>
                          <input type="number" class="form-control input-edit" name="empresas[{{$empresa->idEmpresa}}]" placeholder="Comision" step="0.01" aria-describedby="basic-addon1" value="{{$empresa->comision}}" >
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        </form>
    </div>
    <br>
    <!-- Formulario para cambiar la tasa de cambio fija\ -->
    <div class="row border shadow rounded-3 pt-2 pb-2 calculos-container fija-container">
        <form action="{{route('updateCalculosTasaFija')}}" method="POST">
             @csrf
        <div class="col-md-12">
            <div class="row">
                <div class="col-8 col-md-6">
                    <h3>Calculos Generales - Tasa de Cambio Fija</h3>
                    <p class="text-secondary">Valores que se aplican a los precios de manera general.</p>
                </div>
                <div class="col-4 col-md-6 text-end">
                    <button class="btn btn-success btn-save-calculos" type="submit"> <i class="bi bi-floppy"></i></button>
                    <button class="btn btn-secondary btn-edit-calculos" type="button"> <i class="bi bi-pencil"></i></button>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="row">
                <div class="col-4 col-md-2">
                    <label>IGV:</label>
                    <div class="input-group mb-3">
                      <span class="input-group-text" id="basic-addon1">%</span>
                      <input type="number" class="form-control input-edit" name="igv" placeholder="IGV" step="0.01" aria-describedby="basic-addon1" value="{{$calculosfijo->igv}}" >
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <label>T. Facturaci&oacuten:</label>
                    <div class="input-group mb-3">
                      <span class="input-group-text" id="basic-addon1">%</span>
                      <input type="number" class="form-control input-edit" name="facturacion" placeholder="Faturacion" step="0.01"  aria-describedby="basic-addon1" value="{{$calculosfijo->facturacion}}" >
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <label> T.C:</label>
                    <div class="input-group mb-3">
                      <span class="input-group-text" id="basic-addon1">$</span>
                      <input type="number" class="form-control input-edit" name="tasaCambio" placeholder="T.C." step="0.01" aria-describedby="basic-addon1" value="{{$calculosfijo->tasaCambio}}" >
                    </div>
                </div>
                @foreach($empresas as $empresa)
                    <div class="col-6 col-md-3">
                        <label>{{$empresa->nombreComercial}}:</label>
                        <div class="input-group mb-3">
                          <span class="input-group-text" id="basic-addon1">%</span>
                          <input type="number" class="form-control input-edit" name="empresas[{{$empresa->idEmpresa}}]" placeholder="Comision" step="0.01" aria-describedby="basic-addon1" value="{{$empresa->comision}}" >
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        </form>
    </div>
    <br>
    <div class="row border shadow rounded-3 pt-2 pb-2">
        <div class="col-md-12">
            <div class="row border-bottom">
                <div class="col-8 col-md-7">
                    <h3>Comisiones por Productos</h3>
                    <p class="text-secondary">Porcentaje aplicado a los precios por su costo.</p>
                </div>
                <div class="col-4 col-md-5 text-end">
                    <div class="btn-group dropstart">
                      <button type="button" class="btn btn-secondary" data-bs-toggle="dropdown" aria-expanded="false">
                        Categorias
                      </button>
                      <ul class="dropdown-menu">
                          @foreach($categorias as $categoria)
                            <li><a onclick="viewElementsComision({{$categoria->idCategoria}})" class="dropdown-item" style="cursor:pointer">
                                {{$categoria->nombreCategoria}}
                            </a></li>
                          @endforeach
                      </ul>
                    </div>
                </div>
            </div>
        </div>
        @foreach($categorias as $categoria)
        <div class="col-md-12 divCategory-{{$categoria->idCategoria}}" style="display:none">
            <div class="row pt-2 pb-1 align-items-center">
                <div class="col-8 col-md-9">
                    <h4>{{$categoria->nombreCategoria}} <i class="{{$categoria->iconCategoria}}"></i></h4>
                </div>
                <div class="col-4 col-md-3 text-end">
                    <button type="button"
                        class="btn btn-sm btn-outline-warning fw-semibold"
                        onclick="abrirModalPlantilla({{$categoria->idCategoria}}, '{{addslashes($categoria->nombreCategoria)}}')"
                        data-bs-toggle="modal" data-bs-target="#modalPlantillaUniforme"
                        title="Aplica los mismos % a todos los grupos de esta categoría">
                        <i class="bi bi-lightning-charge-fill"></i> Aplicar plantilla uniforme
                    </button>
                </div>
            </div>
            <ul class="list-group">
                <li class="list-group-item bg-sistema-uno text-light d-none d-sm-block">
                    <div class="row">
                        <div class="col-md-2">
                            <h6>Grupo</h6>
                        </div>
                        @foreach($rangos as $rango)
                        <div class="col-md-1 text-center">
                            <h6>{{$rango->descripcion}}</h6>
                        </div>
                        @endforeach
                    </div>
                </li>
               @foreach($categoria->GrupoProducto()->select('idGrupoProducto','nombreGrupo')->get() as $grupo)
                <li class="list-group-item pt-0 pb-0" style="border-color: #bcbcbc;">
                    <div class="row h-100 mb-0" id="div-comision-{{$grupo->idGrupoProducto}}">
                        <div class="col-12 col-md-2 pt-2 d-none d-sm-block">
                            <h6>{{$grupo->nombreGrupo}}</h6>
                        </div>
                        <div class="col-12 d-block d-sm-none text-center">
                            <h6>{{$grupo->nombreGrupo}}</h6>
                        </div>
                        @foreach($grupo->Comision as $comision)
                        <div class="col-6 pt-2 pb-2 {{$comision->idRango % 2 == 0 ? 'bg-list' : '' }} d-block d-sm-none text-center">
                            {{$comision->RangoPrecio->descripcion}}
                        </div>
                        <div class="col-6 col-md-1 pt-2 pb-2 {{$comision->idRango % 2 == 0 ? 'bg-list' : '' }} text-center">
                            <small>{{$comision->comision}} %</small>
                            <input type="hidden" class="hidden-comision" data-descripcion="{{$comision->RangoPrecio->descripcion}}" data-rango="{{$comision->idRango}}" value="{{$comision->comision}}">
                        </div>
                        @endforeach
                        <div class="col-12 col-md-1 pt-1 pb-1 text-center">
                            <button type="button" class="btn btn-info btn-sm text-light" onclick="modalComision('{{$grupo->nombreGrupo}}',{{$grupo->idGrupoProducto}},{{$categoria->idCategoria}})" data-bs-toggle="modal" data-bs-target="#comisionModal">Edit</button>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach

    {{-- Modal: Aplicar plantilla uniforme a toda la categoría --}}
    <form action="{{route('aplicarcomisionuniforme')}}" method="POST" id="formPlantillaUniforme">
        @csrf
        <input type="hidden" name="idCategoria" id="plantilla-idCategoria" value="">
        <div class="modal fade" id="modalPlantillaUniforme" tabindex="-1" aria-labelledby="modalPlantillaUniformeLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning bg-opacity-10">
                        <div>
                            <h5 class="modal-title fw-bold" id="modalPlantillaUniformeLabel">
                                <i class="bi bi-lightning-charge-fill text-warning"></i> Aplicar plantilla uniforme
                            </h5>
                            <small class="text-secondary" id="plantilla-subtitle">Se sobreescribirán las comisiones de <strong>todos</strong> los grupos de esta categoría.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pb-0">
                        <p class="text-secondary mb-3"><i class="bi bi-info-circle"></i> Edita los valores si lo necesitas antes de guardar. Los mismos % se aplicarán a <strong>todos</strong> los grupos.</p>
                        <ul class="list-group list-group-flush">
                            @foreach($rangos as $rango)
                            @php
                                $defaultsTemplate = [1=>20.00, 2=>13.00, 3=>10.00, 4=>8.00, 5=>7.30, 6=>6.70, 7=>6.30, 8=>5.80, 9=>5.00];
                                $defaultVal = $defaultsTemplate[$rango->idRango] ?? 0.00;
                            @endphp
                            <li class="list-group-item {{$loop->iteration % 2 == 0 ? 'bg-list' : ''}}">
                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <h6 class="mb-0">{{$rango->descripcion}}</h6>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <input type="number"
                                                name="comision[{{$rango->idRango}}]"
                                                class="form-control plantilla-input"
                                                step="0.01" min="0" max="100"
                                                value="{{$defaultVal}}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold">
                            <i class="bi bi-lightning-charge-fill"></i> Aplicar a todos los grupos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    </div>
    <br>
    <div class="row border shadow rounded-3 pt-2 pb-2">
        <div class="col-md-12">
            <h3>Calculos por Plataforma</h3>
            <p class="text-secondary">Valores que se aplican a los precios por plataforma digital.</p>
        </div>
        <div class="col-md-12">

            <div class="accordion accordion-flush" id="accordionFlushPlataforma">
                @php
                    $count = 0;
                @endphp
                @foreach ($plataformas as $plataforma)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-heading-{{$count}}">
                        <div class="row pt-2">
                            <div class="col-md-1">
                                <img src="{{asset('storage/'. $plataforma->imagenPlataforma)}}" alt="" class="border w-100">
                            </div>
                            <div class="col-md-9 pt-2">
                                <h4>{{$plataforma->nombrePlataforma}}</h4>
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="row">
                                    <div class="col-md-9 text-end">
                                        <button class="btn btn-sm btn-success" onclick="sendIdToModalPlataforma('modal-comisionxplataforma-id','modal-comisionxplataforma-title',{{$plataforma->idPlataforma}},'{{$plataforma->nombrePlataforma}}')" data-bs-toggle="modal" data-bs-target="#createPlataformaModal">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-3 ps-0">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-{{$count}}" aria-expanded="false" aria-controls="flush-collapse-{{$count}}">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </h2>
                    <div id="flush-collapse-{{$count}}" class="accordion-collapse collapse" aria-labelledby="flush-heading-{{$count}}" data-bs-parent="#accordionFlushPlataforma">
                        <div class="accordion-body">
                            <ul class="list-group list-group-flush">
                                @foreach ($plataforma->ComisionPlataforma as $comision)
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="text-secondary">Comision</label>
                                            <h6>{{$comision->comision}}</h6>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <label class="text-secondary">Flete</label>
                                            <h6>{{$comision->flete}}</h6>
                                        </div>
                                        <div class="col-md-4 text-end d-flex align-items-center justify-content-end">
                                            <button class="btn btn-danger btn-sm" onclick="sendIdToModalPlataforma('modal-deletecomisionxplataforma-hidden','modal-deletecomisionxplataforma-title',{{$comision->idComisionPlataforma}},'{{$plataforma->nombrePlataforma}}')" data-bs-toggle="modal" data-bs-target="#deletePlataformaModal"><i class="bi bi-trash-fill"></i></button>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @php
                    $count ++;
                @endphp
                @endforeach
            </div>
        </div>
    </div>
    <br>
    <br>
    <form action="{{route('updatecomision')}}" method="POST">
        @csrf
        <div class="modal fade" id="comisionModal" tabindex="-1" aria-labelledby="comisionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="comisionModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ps-0 pe-0 pt-0">
                <ul class="list-group list-group-flush ">
                    @foreach($rangos as $rango)
                    <li class="list-group-item">
                        <div class="row">
                            <div class="col-6 col-md-6">
                                <h6>{{$rango->descripcion}}</h6>
                            </div>
                            <div class="col-6 col-md-6">
                                <input type="number" name="comision[{{$rango->idRango}}]" class="form-control" id="comisionModalList-{{$rango->idRango}}" step="0.01">

                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="grupo" id="comisionHiddenGrup" value="">
                <input type="hidden" id="categoryModalComision" name="category" value="{{ old('category', 1) }}">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar <i class="bi bi-floppy"></i></button>
            </div>
            </div>
        </div>
        </div>
    </form>
    <form action="{{route('createcomisionplataforma')}}" method="post">
        @csrf
        <div class="modal fade" id="createPlataformaModal" tabindex="-1" aria-labelledby="createPlataformaModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createPlataformaModalLabel">Nueva Comision <span id="modal-comisionxplataforma-title"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" name="plataforma" id="modal-comisionxplataforma-id"  value="">
                            <div class="col-md-12">
                                <label class="form-label">Comision:</label>
                                <input type="number" oninput="validateModalComision()" class="form-control" name="comision" step="0.01" >
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Flete:</label>
                                <input type="number" oninput="validateModalComision()" class="form-control" name="flete" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="modal-comisionxplataforma-btn">Guardar <i class="bi bi-floppy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <form action="{{route('deletecomisionplataforma')}}" method="post">
        @csrf
        <div class="modal fade" id="deletePlataformaModal" tabindex="-1" aria-labelledby="deletePlataformaModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <h5 class="modal-title" id="deletePlataformaModalLabel">¿Estas seguro de eliminar esta comision?</h5>
                            <small class="text-secondary" id="modal-deletecomisionxplataforma-title"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" value="" name="id" id="modal-deletecomisionxplataforma-hidden">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Borrar <i class="bi bi-trash-fill"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="{{ asset('js/config-calculos-scripts.js') }}?v=1.00"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const containers = document.querySelectorAll('.calculos-container');

        containers.forEach(container => {

            const btnEdit = container.querySelector('.btn-edit-calculos');
            const btnSave = container.querySelector('.btn-save-calculos');
            const inputs = container.querySelectorAll('.input-edit');

            let isDisabled = true;

            // Inicializar bloqueado
            inputs.forEach(input => input.disabled = true);
            btnSave.style.display = 'none';

            btnEdit.addEventListener('click', function () {

                isDisabled = !isDisabled;

                inputs.forEach(input => {
                    input.disabled = isDisabled;
                });

                btnSave.style.display = isDisabled ? 'none' : 'inline-flex';

            });

        });

    });

    function abrirModalPlantilla(idCategoria, nombreCategoria) {
        document.getElementById('plantilla-idCategoria').value = idCategoria;
        document.getElementById('plantilla-subtitle').innerHTML =
            'Se sobreescribirán las comisiones de <strong>todos</strong> los grupos de <strong>' + nombreCategoria + '</strong>.';
    }
</script>
@endsection
