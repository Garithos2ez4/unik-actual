@extends('layouts.app')

@section('title', 'Documento | ' . $documento->Preveedor->nombreProveedor)

@section('content')
<div class="container py-4">
    <!-- Cabecera del Documento -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">
                <a href="{{ route('documentos', [$documento->fechaRegistro->format('Y-m')]) }}" class="text-secondary text-decoration-none me-2">
                    <i class="bi bi-arrow-left-circle"></i>
                </a> 
                {{ $documento->Preveedor->razSocialProveedor }}
            </h2>
            <h5 class="text-muted mt-1"><i class="bi bi-card-text"></i> RUC: {{ $documento->Preveedor->rucProveedor }}</h5>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <h2 class="mb-0 text-primary">{{ $documento->numeroComprobante }}</h2>
            <h5 class="text-muted mt-1">{{ $documento->TipoComprobante->descripcion }}</h5>
        </div>
    </div>

    <form method="POST" action="{{ route('insertingreso',[encrypt($documento->idComprobante)]) }}" data-comprobante="{{$documento->idComprobante}}" id="form-create-doc">
        @csrf
        
        @if ($validate)
        <!-- Panel de Configuración del Ingreso -->
        <div class="card shadow-sm mb-4 border-0 bg-light">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-bold text-danger" id="select-label-moneda">Moneda:</label>
                        <select class="form-select border-danger" onchange="changeLabel(this,'select-label-moneda')" id="select-moneda" name="comprobante[moneda]">
                            <option value="" selected>-Elige-</option>
                            <option value="SOL">Soles (S/)</option>
                            <option value="DOLAR">Dólares ($)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-bold text-danger" id="select-label-adquisicion">Adquisición:</label>
                        <select class="form-select border-danger" onchange="changeLabel(this,'select-label-adquisicion')" id="select-adquisicion" name="comprobante[adquisicion]">
                            <option value="" selected>-Elige-</option>
                            @foreach ($adquisiciones as $ad)
                                <option value="{{ $ad['value'] }}">{{ $ad['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-bold text-danger" id="select-label-almacen">Almacén de Destino:</label>
                        <select class="form-select border-danger" onchange="changeLabel(this,'select-label-almacen')" id="select-almacen" name="comprobante[almacen]">
                            <option value="">-Elige-</option>
                            @foreach ($ubicaciones as $ubi)
                                <option value="{{ $ubi->idAlmacen }}">{{ $ubi->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2 ms-auto">
                        <label class="form-label text-muted">Descuento Global:</label>
                        <div class="input-group">
                            <span class="input-group-text">-</span>
                            <input type="number" id="importe-descuento-comprobante" class="form-control" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="form-label fw-bold">Importe Total:</label>
                        <input type="number" id="importe-total-comprobante" name="comprobante[total]" step="0.01" class="form-control bg-white fw-bold text-primary fs-5" value="0.00" readonly>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botonera de Acción -->
        <div class="d-flex justify-content-end gap-2 mb-3">
            <button type="button" onclick="generatePlantilla()" class="btn btn-outline-success shadow-sm">
                <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-md-inline">Plantilla</span>
            </button>
            <button type="button" class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#registerModal">
                <i class="bi bi-cart-plus"></i> <span class="d-none d-md-inline">Agregar Producto</span>
            </button>
        </div>
        @else
        <!-- Vista de Solo Lectura / Edición Rápida -->
        <div class="d-flex flex-wrap justify-content-between mb-4 gap-2">
            <button type="button" onclick="deleteForm({{ $documento->idComprobante }})" class="btn btn-outline-danger {{ $documento->estado == 'INVALIDO' ? 'd-none' : '' }}">
                <i class="bi bi-trash3"></i> Eliminar Documento
            </button>
            <div>
                <button type="button" class="btn btn-warning shadow-sm {{ $documento->estado == 'INVALIDO' ? 'd-none' : '' }}" data-bs-toggle="modal" data-bs-target="#editComprobanteModal">
                    <i class="bi bi-pencil-square"></i> Editar Cabecera
                </button>
                @if (count($pdf) > 0)
                    <button type="button" class="btn btn-danger ms-2 shadow-sm" onclick="openPdfInNewWindow()">
                        <i class="bi bi-file-earmark-pdf"></i> Ver Series
                    </button>
                @endif
            </div>
        </div>
        @endif

        <!-- Lista de Productos (Tabla Estilizada) -->
        <div class="card shadow-sm border-0">
            <ul class="list-group list-group-flush" id="ul-ingreso" style="max-height: 60vh; overflow-x: hidden; overflow-y: auto;">
                <!-- Cabecera de la lista -->
                @if ($validate)
                <li class="list-group-item bg-sistema-uno text-light fw-bold py-3" style="position:sticky; top:0; z-index:1000">
                    <div class="row text-center align-items-center">
                        <div class="col-1 col-md-1"><small>Cant.</small></div>
                        <div class="col-3 col-md-4 col-lg-3 text-start"><small>Producto</small></div>
                        <div class="col-lg-1 d-none d-lg-block"><small>U.M.</small></div>
                        <div class="col-2 col-md-2"><small>P.U (S/I)</small></div>
                        <div class="col-2 col-md-2"><small>P.U (C/I)</small></div>
                        <div class="col-2 col-md-1"><small>Total</small></div>
                        <div class="col-2 col-md-2"><small>Acción</small></div>
                    </div>
                </li>
                @else
                <li class="list-group-item bg-sistema-uno text-light fw-bold py-3" style="position:sticky; top:0; z-index:1000">
                    <div class="row text-center align-items-center w-100">
                        <div class="col-1 d-block d-md-none pt-1"><small>#</small></div>
                        <div class="col-11 col-md-4 text-start"><small>Producto</small></div>
                        <div class="col-md-1 d-none d-md-block"><small>Cant.</small></div>
                        <div class="col-md-1 d-none d-lg-block"><small>U.M.</small></div>
                        <div class="col-md-2 d-none d-md-block"><small>P.U (Sin IGV)</small></div>
                        <div class="col-3 col-md-2 d-none d-md-block"><small>P.U (Con IGV)</small></div>
                        <div class="col-4 col-md-2 d-none d-md-block"><small>Total</small></div>
                    </div>
                </li>
                @endif
                
                <!-- Aquí se inyectan los items desde JS (li-btn-add) -->
                @if ($validate)
                <li class="list-group-item p-0" id="li-btn-add"></li>
                @endif

                <!-- Items renderizados si NO es validate -->
                @if (!$validate)
                    @php $cont = 1; @endphp
                    @foreach ($documento->DetalleComprobante as $detalle)
                        <li class="list-group-item list-group-item-action p-0 border-bottom">
                            <div class="accordion accordion-flush" id="accordionFlushExample">
                                <div class="accordion-item bg-transparent">
                                    <h2 class="accordion-header" id="flush-heading-{{ $cont }}">
                                        <button class="accordion-button collapsed bg-transparent shadow-none py-3" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-{{ $cont }}">
                                            <div class="row text-center w-100 align-items-center">
                                                <div class="col-1 d-block d-md-none fw-bold text-primary">
                                                    {{ count($detalle->RegistroProducto) }}
                                                </div>
                                                <div class="col-11 col-md-4 text-start fw-bold text-dark">
                                                    {{ $detalle->Producto->nombreProducto }}
                                                </div>
                                                <div class="col-md-1 d-none d-md-block fw-bold text-primary">
                                                    {{ count($detalle->RegistroProducto) }}
                                                </div>
                                                <div class="col-md-1 d-none d-lg-block text-muted">
                                                    {{ $detalle->medida }}
                                                </div>
                                                <div class="col-md-2 d-none d-md-block">
                                                    {{ number_format($detalle->precioUnitario / 1.18, 2) }}
                                                </div>
                                                <div class="col-3 col-md-2 d-none d-md-block text-success">
                                                    {{ number_format($detalle->precioUnitario, 2) }}
                                                </div>
                                                <div class="col-4 col-md-2 d-none d-md-block fw-bold text-primary">
                                                    {{ number_format($detalle->precioCompra, 2) }}
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="flush-collapse-{{ $cont }}" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                                        <div class="accordion-body p-0 bg-light border-top">
                                            <ul class="list-group list-group-flush">
                                                <!-- Vista móvil -->
                                                <li class="list-group-item bg-light d-block d-md-none border-bottom">
                                                    <div class="row text-center small text-muted">
                                                        <div class="col-3">
                                                            <div class="fw-bold text-dark">UM:</div>
                                                            {{ $detalle->medida }}
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="fw-bold text-dark">S/I:</div>
                                                            {{ number_format($detalle->precioUnitario / 1.18, 2) }}
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="fw-bold text-dark">C/I:</div>
                                                            {{ number_format($detalle->precioUnitario, 2) }}
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="fw-bold text-dark">Total:</div>
                                                            {{ number_format($detalle->precioCompra, 2) }}
                                                        </div>
                                                    </div>
                                                </li>
                                                <!-- Series -->
                                                @foreach ($detalle->RegistroProducto as $registro)
                                                <li class="list-group-item bg-transparent">
                                                    <div class="row align-items-center {{ $registro->estado == 'INVALIDO' ? 'text-danger text-decoration-line-through' : '' }}">
                                                        <div class="col-6 col-md-3 text-start">
                                                            <small class="text-muted d-block">Número de Serie</small>
                                                            <span class="fw-bold">{{ $registro->numeroSerie }}</span>
                                                        </div>
                                                        <div class="col-4 col-md-2">
                                                            <small class="text-muted d-block">Estado</small>
                                                            <span class="badge {{ $registro->estado == 'NUEVO' ? 'bg-success' : 'bg-secondary' }}">{{ $registro->estado }}</span>
                                                        </div>
                                                        <div class="col-md-6 d-none d-md-block">
                                                            <small class="text-muted d-block">Observaciones</small>
                                                            <span class="text-truncate d-block">{{ $registro->observacion ?: 'Ninguna' }}</span>
                                                        </div>
                                                        <div class="col-2 col-md-1 text-end">
                                                            <button type="button" onclick="sendIdToDelete({{ $registro->idRegistro }})" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                                <i class="bi bi-trash3-fill"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @php $cont++; @endphp
                    @endforeach
                @endif
            </ul>
        </div>

        @if ($validate)
        <!-- Pie del Formulario -->
        <div class="row mt-4">
            <div class="col-md-4">
                <button type="button" onclick="deleteForm({{ $documento->idComprobante }})" class="btn btn-outline-danger shadow-sm">
                    <i class="bi bi-trash3"></i> Cancelar Ingreso
                </button>
            </div>
            <div class="col-md-4 text-center">
                <button type="button" onclick="confirmForm()" class="btn btn-success btn-lg px-5 shadow" id="btnSubmit" disabled>
                    <i class="bi bi-floppy-fill"></i> Registrar Comprobante
                </button>
            </div>
        </div>
        @endif
    </form>
</div>

<!-- Modal Eliminar Registro -->
<form action="{{ route('deleteingreso') }}" method="POST">
    @csrf
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle"></i> ¿Seguro de Eliminar?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-trash3 text-danger" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">El registro se invalidará, pero no se eliminará físicamente de la Base de datos por temas de auditoría.</p>
                </div>
                <div class="modal-footer bg-light">
                    <input type="hidden" value="" id="input-delete-ingreso" name="idingreso">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal de Registro de Producto -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="registerModalLabel"><i class="bi bi-box-seam"></i> Agregar Producto al Documento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Buscador -->
                <div class="row mb-4">
                    <div class="col-12 position-relative">
                        <label class="form-label fw-bold text-primary">1. Buscar Producto</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0" oninput="searchProduct(this)" placeholder="Escribe el modelo o nombre..." id="modal-input-product">
                        </div>
                        <ul class="list-group shadow w-100" id="suggestions-product" style="position:absolute; z-index:999; top:100%; max-height: 250px; overflow-y: auto;"></ul>
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <!-- Detalles del Producto -->
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-bold text-primary">Producto Seleccionado:</label>
                        <input type="text" class="form-control form-control-lg bg-light input-modal-product border-0" data-id="" data-cod="" value="" id="modal-hidden-product" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">Unidad de Medida:</label>
                        <select class="form-select form-select-lg input-modal-product shadow-sm" id="modal-select-medida">
                            <option value="">-Elige-</option>
                            @foreach ($medidas as $medida)
                                <option value="{{ $medida['value'] }}">{{ $medida['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Calculadora Integrada de Precios -->
                <div class="p-4 bg-light border rounded-3 shadow-sm">
                    <h5 class="text-primary mb-3"><i class="bi bi-calculator"></i> 2. Precios de Costo</h5>
                    <div class="row g-3 align-items-center">
                        <!-- Precio Base -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Precio Unitario (Sin IGV)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white">$ / S/</span>
                                <input type="number" step="0.01" class="form-control text-end" id="modal-input-price-sin-igv" placeholder="0.00" 
                                    oninput="if(this.value){ document.getElementById('modal-input-price').value = (this.value * 1.18).toFixed(2); } else { document.getElementById('modal-input-price').value = ''; }">
                            </div>
                        </div>
                        
                        <!-- Conector visual -->
                        <div class="col-md-2 text-center text-muted">
                            <i class="bi bi-arrow-left-right fs-3 d-none d-md-block text-primary"></i>
                            <i class="bi bi-arrow-down-up fs-3 d-block d-md-none my-2 text-primary"></i>
                            <span class="badge bg-primary rounded-pill mt-1">+ 18% IGV</span>
                        </div>

                        <!-- Precio Final -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-success">Precio Final (Con IGV)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-success text-white">$ / S/</span>
                                <input type="number" step="0.01" class="form-control text-end text-success fw-bold border-success input-modal-product" id="modal-input-price" placeholder="0.00"
                                    oninput="if(this.value){ document.getElementById('modal-input-price-sin-igv').value = (this.value / 1.18).toFixed(2); } else { document.getElementById('modal-input-price-sin-igv').value = ''; }">
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small text-center"><i class="bi bi-info-circle text-primary"></i> Ingresa cualquiera de los dos valores y el sistema calculará el otro automáticamente.</div>
                </div>

            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnIngreso" class="btn btn-primary px-5 shadow" data-bs-dismiss="modal">
                    <i class="bi bi-plus-circle"></i> Agregar a Lista
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Comprobante -->
@include('documento.edit_modal')

<input type="file" onchange="readExcel(this)" id="excel-file" class="d-none" />
<form action="{{ route('deletecomprobante') }}" method="post" id="form-deletecomprobante">
    @csrf
    <input type="hidden" name="id" id="hidden-form-deletecomprobante">
</form>

<x-scanner :multiple="true" />

<script>
    window.APP_DATA = {
        idComprobante: {{ $documento->idComprobante }},
        estados: @json($estados),
        almacenes: @json($ubicaciones)
    };
</script>
<script src="{{ asset('js/documento-scripts.js') }}?v=1.00"></script>
<script src="{{ asset('js/documento.js') }}"></script>
@endsection