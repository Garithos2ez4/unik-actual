@extends('layouts.app')

@section('title', 'Documento | ' . $documento->Preveedor->nombreProveedor)

@section('content')
    <div class="container">
        <br>
        <div class="row">
            <div class="col-md-6">
                <h2><a href="{{ route('documentos', [$documento->fechaRegistro->format('Y-m')]) }}" class="text-secondary"><i
                            class="bi bi-filter-circle"></i></a> {{ $documento->Preveedor->razSocialProveedor }}</h2>
            </div>
            <div class="col-8 col-md-6 ">
                <h2 class="text-end d-none d-sm-none d-md-block">{{ $documento->numeroComprobante }}</h2>
                <h4 class="d-block d-sm-none">{{ $documento->numeroComprobante }}</h4>
            </div>
            <div class="col-md-6 d-none d-md-block">
                <h4 class="text-secondary">RUC: {{ $documento->Preveedor->rucProveedor }}</h4>
            </div>
            <div class="col-4 col-md-6 text-end">
                <h4 class="text-secondary">{{ $documento->TipoComprobante->descripcion }}</h4>
            </div>
        </div>
        <br>
        <form method="POST" action="{{ route('insertingreso',[encrypt($documento->idComprobante)]) }}" data-comprobante="{{$documento->idComprobante}}" id="form-create-doc">
            @csrf
            <div class="row">
                <div class="col-md-12 mb-3">
                    @if ($validate)
                        <div class="row mb-2">
                            <div class="col-4 col-md-3 col-lg-2">
                                <label class="form-label" style="color:red" id="select-label-moneda">Moneda:</label>
                                <select class="form-select" onchange="changeLabel(this,'select-label-moneda')"
                                    id="select-moneda" name="comprobante[moneda]">
                                    <option class="text-danger" value="" selected>-Elige-</option>
                                    <option value="SOL">Soles</option>
                                    <option value="DOLAR">Dolares</option>
                                </select>
                            </div>
                            <div class="col-4 col-md-3 col-lg-2">
                                <label class="form-label" style="color:red"
                                    id="select-label-adquisicion">Adquisici&oacuten</label>
                                <select class="form-select" onchange="changeLabel(this,'select-label-adquisicion')"
                                    id="select-adquisicion" name="comprobante[adquisicion]">
                                    <option class="text-danger" value="" selected>-Elige-</option>
                                    @foreach ($adquisiciones as $ad)
                                        <option value="{{ $ad['value'] }}">{{ $ad['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4 col-md-3 col-lg-2">
                                <label class="form-label" style="color:red" id="select-label-almacen">Almac&eacuten</label>
                                <select class="form-select" onchange="changeLabel(this,'select-label-almacen')"
                                    id="select-almacen" name="comprobante[almacen]">
                                    <option class="text-danger" value="">-Elige-</option>
                                    @foreach ($ubicaciones as $ubi)
                                        <option value="{{ $ubi->idAlmacen }}">{{ $ubi->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-none d-lg-block">
                            </div>
                            <div class="col-4 col-lg-2">
                                <label class="form-label">Descuento:</label>
                                <input type="number" id="importe-descuento-comprobante" class="form-control" name=""
                                    step="0.01" id="" value="0.00">
                            </div>
                            <div class="col-4 col-lg-2 text-lg-end">
                                <label class="form-label">Importe Total:</label>
                                <input type="number" id="importe-total-comprobante" name="comprobante[total]"
                                    step="0.01" class="form-control" value="0.00" readonly>
                            </div>
                            <div class="col-md-3 col-lg-6 d-none d-md-block mt-2">

                            </div>
                            <div class="col-4 col-md-6 mt-2 pt-4 text-end">
                                <button type="button" onclick="generatePlantilla()" class="btn bg-success text-light"><span
                                        class="d-none d-md-inline">Plantilla</span> <i
                                        class="bi bi-file-earmark-excel-fill"></i></button>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                                    data-bs-target="#registerModal"><span class="d-none d-md-inline">Producto</span> <i
                                        class="bi bi-cart-plus-fill"></i></button>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-6 col-md-4 mb-4 ">
                                <button type="button" onclick="deleteForm({{ $documento->idComprobante }})"
                                    class="btn btn-danger {{ $documento->estado == 'INVALIDO' ? 'd-none' : '' }}">
                                    <i class="bi bi-trash3"></i> Eliminar {{ $documento->TipoComprobante->descripcion }}
                                </button>
                            </div>
                            <div class="col-6 col-md-8 text-end mb-4">
                                <button type="button" class="btn btn-warning {{ $documento->estado == 'INVALIDO' ? 'd-none' : '' }}" data-bs-toggle="modal" data-bs-target="#editComprobanteModal">
                                    <i class="bi bi-pencil-square"></i> Editar Comprobante
                                </button>
                                @if (count($pdf) > 0)
                                    <button type="button" class="btn btn-danger ms-2" onclick="openPdfInNewWindow()"><i class="bi bi-file-earmark-pdf"></i> Series</button>
                                @endif
                            </div>
                        </div>
                    @endif
                    <ul class="list-group" id="ul-ingreso" style="max-height: 60vh;overflow-x: hidden; overflow-y: auto;">
                        @if ($validate)
                            <li class="list-group-item bg-sistema-uno text-light text-center fw-normal fs-5"
                                style="position:sticky;top:0;z-index:1000">
                                <div class="row text-center">
                                    <div class="col-1 col-md-1">
                                        <small class="d-none d-md-inline">Cant.</small>
                                        <small class="d-md-none">#</small>
                                    </div>
                                    <div class="col-4 col-md-5 col-lg-3 text-start">
                                        <small>Producto</small>
                                    </div>
                                    <div class="col-2 d-none d-lg-block">
                                        <small>U.M.</small>
                                    </div>
                                    <div class="col-2 col-md-2">
                                        <small class="d-none d-lg-inline">Precio Unitario (SIN IGV)</small>
                                        <small class="d-lg-none">P.U (SIN IGV)</small>
                                    </div>
                                    <div class="col-2 col-md-2">
                                        <small class="d-none d-md-inline">Precio Total</small>
                                        <small class="d-md-none">P.T</small>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item pt-0 pb-0 ps-0 pe-0" id="li-btn-add">
                                <div class="row w-100 h-100 ms-0 me-0">

                                </div>
                            </li>
                        @else
                            <li class="list-group-item bg-sistema-uno text-light text-center fw-normal fs-6"
                                style="position:sticky;top:0;z-index:1000">
                                <div class="row w-100">
                                    <div class="col-5 col-md-6 text-start">
                                        <small>Producto</small>
                                    </div>
                                    <div class="col-md-1 d-none d-sm-none d-md-block">
                                        <small>Cantidad</small>
                                    </div>
                                    <div class="col-md-2 d-none d-sm-none d-md-block">
                                        <small>UM</small>
                                    </div>
                                    <div class="col-3 col-md-1">
                                        <small>Precio</small>
                                    </div>
                                    <div class="col-4 col-md-2">
                                        <small>Costo Total</small>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-secondary pt-0 pb-0 ps-0 pe-0">
                                <div class="accordion accordion-flush" id="accordionFlushExample">
                                    @php $cont = 1; @endphp
                                    @foreach ($documento->DetalleComprobante as $detalle)
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="flush-heading-{{ $cont }}">
                                                <button
                                                    class="accordion-button collapsed border-bottom list-group-item-secondary ps-3 pe-3"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#flush-collapse-{{ $cont }}"
                                                    aria-expanded="false"
                                                    aria-controls="flush-collapse-{{ $cont }}">
                                                    <div class="row text-center w-100">
                                                        <div class="col-1 d-block d-md-none pt-1">
                                                            {{ count($detalle->RegistroProducto) }}
                                                        </div>
                                                        <div class="col-11 col-md-6 text-start fw-bold mb-2">
                                                            {{ $detalle->Producto->nombreProducto }}
                                                        </div>
                                                        <div class="col-md-1 d-none d-md-block">
                                                            {{ count($detalle->RegistroProducto) }}
                                                        </div>
                                                        <div class="col-3 col-md-2 d-none d-lg-block">
                                                            {{ $detalle->medida }}
                                                        </div>
                                                        <div class="col-3 col-md-1 d-none d-md-block">
                                                            {{ number_format($detalle->precioUnitario, 2) }}
                                                        </div>
                                                        <div class="col-4 col-md-2 d-none d-md-block">
                                                            {{ number_format($detalle->precioCompra, 2) }}
                                                        </div>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="flush-collapse-{{ $cont }}"
                                                class="accordion-collapse collapse"
                                                aria-labelledby="flush-heading-{{ $cont }}"
                                                data-bs-parent="#accordionFlushExample">
                                                <div class="accordion-body ps-0 pe-0 pt-0 pb-0">
                                                    <ul class="list-group" style="position: relative;">
                                                        <li
                                                            class="list-group-item list-group-item-secondary d-block d-md-none">
                                                            <div class="row">
                                                                <div class="col-4 col-md-2">
                                                                    <small>Unidad de medida:</small>
                                                                    <p class="mb-0">{{ $detalle->medida }}</p>
                                                                </div>
                                                                <div class="col-4 col-md-1">
                                                                    <small>Precio Unitario (SIN IGV):</small>
                                                                    <p class="mb-0">
                                                                        {{ number_format($detalle->precioUnitario, 2) }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-4 col-md-2">
                                                                    <small>Precio Total:</small>
                                                                    <p class="mb-0">
                                                                        {{ number_format($detalle->precioCompra, 2) }}</p>
                                                                </div>
                                                            </div>
                                                        </li>
                                                        @foreach ($detalle->RegistroProducto as $registro)
                                                            <li class="list-group-item">
                                                                <div
                                                                    class="row text-center {{ $registro->estado == 'INVALIDO' ? 'text-danger text-decoration-line-through' : '' }}">
                                                                    <div class="col-6 col-md-3 text-start">
                                                                        <label class="text-secondary fw-italic">Numero de
                                                                            Serie</label>
                                                                        <p>{{ $registro->numeroSerie }}</p>
                                                                    </div>
                                                                    <div class="col-4 col-md-2">
                                                                        <label
                                                                            class="text-secondary fw-italic">Estado</label>
                                                                        <p>{{ $registro->estado }}</p>
                                                                    </div>
                                                                    <div class="col-md-6 d-none d-sm-none d-md-block">
                                                                        <label
                                                                            class="text-secondary fw-italic">Observaciones</label>
                                                                        <p>{{ $registro->observacion ? $registro->observacion : 'Sin observaciones' }}
                                                                        </p>
                                                                    </div>
                                                                    <div
                                                                        class="col-2 col-md-1 text-end d-flex align-items-center">
                                                                        <button type="button"
                                                                            onclick="sendIdToDelete({{ $registro->idRegistro }})"
                                                                            class="btn btn-danger btn-sm"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#exampleModal"><i
                                                                                class="bi bi-trash-fill"></i></button>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        @php $cont++; @endphp
                                    @endforeach
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
                @if ($validate)
                    <div class="col-3">
                        <button type="button" onclick="deleteForm({{ $documento->idComprobante }})"
                            class="btn btn-danger"><i class="bi bi-trash3"></i> <span class="d-none d-lg-inline">Eliminar
                                {{ $documento->TipoComprobante->descripcion }}</span></button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button"
                        onclick="confirmForm()"
                            class="btn btn-success" id="btnSubmit" disabled><i class="bi bi-floppy-fill"></i>
                            Registrar</button>
                    </div>
                    <div class="col-3 text-end">
                    </div>
                @endif
            </div>
        </form>
        <!-- Modal -->
        <form action="{{ route('deleteingreso') }}" method="POST">
            @csrf
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="deleteModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="row">
                                <h5 id="deleteModalLabel">Seguro de Eliminar?</h5>
                                <br>
                                <small class="text-secondary">El registro se invalidara pero no se eliminara de la Base de
                                    datos.</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" value="" id="input-delete-ingreso" name="idingreso">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="row">
                            <h5 id="registerModalLabel">Agregar Producto</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12  mb-3">
                                <label class="form-label">Producto:</label>
                                <input type="text" class="form-control input-modal-product" data-id=""
                                    data-cod="" value="" id="modal-hidden-product"
                                    placeholder="Encuentra un producto valido..." disabled>
                            </div>
                            <div class="col-md-12  mb-3" style="position:relative">
                                <label class="form-label">Buscar Producto:</label>
                                <input type="text" class="form-control" oninput="searchProduct(this)"
                                    placeholder="Modelo..." id="modal-input-product">
                                <ul class="list-group" id="suggestions-product"
                                    style="position:absolute;z-index:999;top:100%">

                                </ul>
                            </div>
                            <div class="col-6 col-md-6">
                                <label class="form-label">Unidad de Medida</label>
                                <select class="form-select input-modal-product" id="modal-select-medida">
                                    <option value="">-Elige una medida-</option>
                                    @foreach ($medidas as $medida)
                                        <option value="{{ $medida['value'] }}">{{ $medida['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-6  mb-3">
                                <label class="form-label">Precio por unidad:</label>
                                <input type="number" step="0.01" class="form-control input-modal-product"
                                    value="" id="modal-input-price">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" value="" id="input-delete-ingreso" name="idingreso">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btnIngreso" class="btn btn-success"
                            data-bs-dismiss="modal">Agregar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal Editar Comprobante -->
        @include('documento.edit_modal')
        
        <br>
        <input type="file" onchange="readExcel(this)" id="excel-file" class="d-none" /> <!--Input reusable -->
        <form action="{{ route('deletecomprobante') }}" method="post" id="form-deletecomprobante">
            @csrf
            <input type="hidden" name="id" id="hidden-form-deletecomprobante">
        </form>
    </div>
    <x-scanner :multiple="true" />
    <script>
        window.APP_DATA = {
            idComprobante: {{ $documento->idComprobante }},
            estados: @json($estados),
            almacenes: @json($ubicaciones)
        };
    </script>
    <script src="{{ asset('js/documento-scripts.js') }}?v=1.00"></script>
    <script src="{{asset('js/documento.js')}}"></script>

@endsection
