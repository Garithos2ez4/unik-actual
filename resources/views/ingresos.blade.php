@extends('layouts.app')

@section('title', 'Ingresos')

@section('content')
<div class="container">
    <div class="bg-secondary" id="hidden-body"
        style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
    </div>
    <br>
    <div class="row mb-2">
        <div class="col-9 col-md-7 col-lg-5 text-end" style="position:relative;z-index:999">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Serial Number..." id="search">
                <ul class="list-group w-100" style="position:absolute;top:100%;z-index:1000" id="suggestions">
                </ul>
            </div>
        </div>
        <div class="col-lg-4 d-none d-lg-block"></div>
        <div class="col-3 col-md-5 col-lg-3 text-end">
            <input type="month" class="form-control hidde-month" id="month" name="month" value="{{$fecha->format('Y-m')}}">
            <button class="btn btn-light border d-md-none" onclick="hiddeInputDate('month')">
                <i class="bi bi-calendar3"></i> <!-- Ícono de calendario -->
            </button>
        </div>
        <div class="col-10 col-lg-6">
            <h2><a href="{{route('documentos', [$fecha->format('Y-m')])}}" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> <i class="bi bi-file-earmark-plus-fill"></i> Ingresos
                <span class="text-capitalize text-secondary fw-light"><em>({{$fecha->translatedFormat('F')}})</em></span>
            </h2>
        </div>
        <div class="col-2 col-lg-6 text-end">
            @foreach ($user->Accesos as $vista)
            @if($vista->idVista == 8)
            <a class="btn btn-outline-purple me-1" data-bs-toggle="modal" data-bs-target="#buscarPackModal" title="Dividir Pack Antiguo">
                <i class="bi bi-scissors"></i><span class="d-none d-md-inline"> Dividir por Serie</span>
            </a>
            <a class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ingresoModal"><i
                    class="bi bi-file-earmark-plus"></i><span class="d-none d-md-inline"> Nuevo Registro</span></a>
            @endif
            @endforeach
        </div>
    </div>
    <form action="{{url()->current()}}" method="get" id="form-filtro-componente">
        <div class="row mb-2">
            <div class="col-6 col-md-3 col-lg-2">
                <small>Usuario</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[usuario]">
                    <option value="">Todos</option>
                    @foreach ($filtros['users'] as $usuario)
                    <option value="{{$usuario->idUser}}">{{$usuario->Usuario->user}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Proveedores</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[proveedor]">
                    <option value="">Todos</option>
                    @foreach ($filtros['proveedores'] as $proveedor)
                    <option value="{{$proveedor->idProveedor}}">{{$proveedor->nombreProveedor}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Almac&eacute;n</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[almacen]">
                    <option value="">Todos</option>
                    @foreach ($filtros['almacenes'] as $almacen)
                    <option value="{{$almacen->idAlmacen}}">{{$almacen->descripcion}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Estado</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[estado]">
                    <option value="">Todos</option>
                    @foreach ($filtros['estados'] as $estado)
                    <option value="{{$estado->estado}}">{{$estado->estado}}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
    <div id="container-lista-ingresos">
        <x-lista_ingresos :registros="$registros" :container="'container-lista-ingresos'" />
    </div>
    <!-- Modal Buscar Pack -->
    @foreach ($user->Accesos as $vista)
    @if($vista->idVista == 8)
    <div class="modal fade" id="buscarPackModal" tabindex="-1" aria-labelledby="buscarPackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarPackModalLabel"><i class="bi bi-search"></i> Buscar Pack a Dividir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body position-relative">
                    <div class="mb-3">
                        <label class="form-label">Número de Serie</label>
                        <input type="text" class="form-control" id="input-buscar-serie-pack" placeholder="Ingresa o escanea la serie..." autofocus autocomplete="off">
                        <div id="suggestions-serie-pack" class="list-group position-absolute w-100 shadow mt-1" style="z-index: 1050; max-height: 200px; overflow-y: auto; display: none; left: 0; padding: 0 1rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-purple" id="btn-ejecutar-busqueda-pack">Buscar y Dividir</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endforeach

    <!-- Modal -->
    @foreach ($user->Accesos as $vista)
    @if($vista->idVista == 8)
    <form action="{{route('insertcomprobante')}}" method="POST">
        @csrf
        <div class="modal fade" id="ingresoModal" tabindex="-1" aria-labelledby="ingresoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ingresoModalLabel">Nuevo Registro</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6 col-md-6">
                                <label>Proveedor</label>
                                <select class="form-select" id="proveedor-select" name="proveedor">
                                    <option value="" {{old('proveedor')=='' ? 'selected' : '' }}>-Elige un proveedor-
                                    </option>
                                    @foreach($proveedores as $proveedor)
                                    <option value="{{$proveedor['idProveedor']}}"
                                        {{old('proveedor')==$proveedor['idProveedor'] ? 'selected' : '' }}>
                                        {{$proveedor['nombreProveedor']}}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-6">
                                <label>Documento</label>
                                <select class="form-select" id="documento-select" name="tipocomprobante">
                                    <option value="" {{old('tipocomprobante')=='' ? 'selected' : '' }}>-Elige un
                                        documento-</option>
                                    @foreach($documentos as $doc)
                                    <option value="{{$doc->idTipoComprobante}}" {{old('tipocomprobante')==$doc->
                                            idTipoComprobante ? 'selected' : ''}}>{{$doc->descripcion}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label>Nro Documento</label>
                                <input type="text" class="form-control" id="documento-number" name="numerocomprobante"
                                    value="{{old('numerocomprobante')}}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="btn-save"><i class="bi bi-floppy"></i>
                            Registrar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
    @endforeach

    <form action="{{route('updateregistro')}}" method="POST">
        @csrf
        <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" name="idregistro" id="idregistro-modal-detail" value="">
                            <div class="col-12">
                                <h5 id="titleproduct-modal-detail">[titulo del producto]</h5>
                            </div>
                            <div class="col-6 text-secondary">
                                <h6 id="proveedor-modal-detail">[proveedor]</h6>
                            </div>
                            <div class="col-6 text-end text-secondary">
                                <h6 id="serialnumber-modal-detail">[numero de serie]</h6>
                            </div>
                            <div class="col-6">
                                <span id="user-modal-detail">[usuario]</span>
                            </div>
                            <div class="col-6 text-end">
                                <span id="date-modal-detail">[fechademovimiento]</span>
                            </div>
                            <div class="col-6 pt-2">
                                <label class="form-label fw-bold">Ubicacion:</label>
                                <select id="almacen-modal-detail" class="form-select" disabled>
                                    @foreach ($almacenes as $almacen)
                                    <option value="{{$almacen->idAlmacen}}">{{$almacen->descripcion}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 pt-2">
                                <label class="form-label fw-bold">Estado:</label>
                                <select id="state-modal-detail" name="estado" class="form-select">
                                    @foreach ($estados as $estado)
                                    <option value="{{$estado['value']}}" {{$estado['value']=='ENTREGADO' ? 'disabled' :''}}>
                                        {{$estado['name']}}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mt-3 d-none" id="devolucion-info-block">
                                <div class="alert alert-warning mb-0 p-2">
                                    <h6 class="alert-heading mb-1"><i class="bi bi-info-circle"></i> Info. Devolucin previa</h6>
                                    <small id="devolucion-motivo" class="d-block mb-1"></small>
                                    <small class="d-block"><strong>Apto Venta:</strong> <span id="devolucion-apto"></span></small>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check-fallo-entrega">
                                    <label class="form-check-label text-primary fw-bold" style="cursor: pointer" for="check-fallo-entrega">
                                        Fallo de entrega (Autocompletar)
                                    </label>
                                </div>
                                <strong>Observaciones</strong>
                                <textarea name="observacion" maxlength="500" placeholder="Sin observaciones"
                                    id="obs-modal-detail" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Modal de Confirmación de División de Pack --}}
    <form action="{{route('dividirpack')}}" method="POST" id="form-dividir-pack">
        @csrf
        <div class="modal fade" id="dividirPackModal" tabindex="-1" aria-labelledby="dividirPackModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-purple text-white">
                        <h5 class="modal-title" id="dividirPackModalLabel"><i class="bi bi-scissors"></i> Dividir Pack</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idRegistro" id="dividir-pack-idregistro">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Al dividir este pack, se crearán registros individuales para cada componente con la misma serie.
                        </div>
                        <p><strong>Producto:</strong> <span id="dividir-pack-producto"></span></p>
                        <p><strong>Serie:</strong> <span id="dividir-pack-serie"></span></p>
                        <div id="dividir-pack-componentes">
                            <p class="text-secondary"><i class="bi bi-hourglass-split"></i> Cargando componentes...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-purple" id="btn-confirmar-division">
                            <i class="bi bi-scissors"></i> Confirmar División
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<style>
    .bg-purple { background-color: #6f42c1 !important; }
    .text-purple { color: #6f42c1 !important; }
    .btn-purple { background-color: #6f42c1; border-color: #6f42c1; color: #fff; }
    .btn-purple:hover { background-color: #5a32a3; border-color: #5a32a3; color: #fff; }
    .btn-outline-purple { color: #6f42c1; border-color: #6f42c1; }
    .btn-outline-purple:hover { background-color: #6f42c1; color: #fff; }
    .text-info { color: #0dcaf0 !important; }
</style>

<script src="{{asset('js/ingresos.js')}}"></script>
<script src="{{asset('js/filtro_componente.js')}}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('proveedor-select')) {
        new TomSelect('#proveedor-select', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    }
});

// Lógica de División de Pack
function abrirModalDivision(idRegistro, nombreProducto, serie) {
    document.getElementById('dividir-pack-idregistro').value = idRegistro;
    document.getElementById('dividir-pack-producto').textContent = nombreProducto;
    document.getElementById('dividir-pack-serie').textContent = serie;

    let componentesDiv = document.getElementById('dividir-pack-componentes');
    componentesDiv.innerHTML = '<p class="text-secondary"><i class="bi bi-hourglass-split"></i> Cargando componentes...</p>';

    // Cargar componentes del pack via AJAX
    fetch(`/ingresos/verificar-pack?idRegistro=${idRegistro}`)
        .then(r => r.json())
        .then(data => {
            if (data && data.componentes) {
                let html = '<h6 class="fw-bold">Componentes que se crearán:</h6><ul class="list-group">';
                data.componentes.forEach(c => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> ${c.nombreProducto}</span>
                        <span class="badge bg-success rounded-pill">x${c.cantidad}</span>
                    </li>`;
                });
                html += '</ul>';
                componentesDiv.innerHTML = html;
            } else {
                componentesDiv.innerHTML = '<div class="alert alert-warning">Este producto no tiene componentes de pack configurados.</div>';
                document.getElementById('btn-confirmar-division').disabled = true;
            }
        })
        .catch(err => {
            componentesDiv.innerHTML = '<div class="alert alert-danger">Error al cargar componentes.</div>';
        });

    let modalElement = document.getElementById('dividirPackModal');
    let modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    document.getElementById('btn-confirmar-division').disabled = false;
    modal.show();
}

document.addEventListener('click', function(e) {
    let btn = e.target.closest('.btn-dividir-pack');
    if (!btn) return;

    e.preventDefault();
    abrirModalDivision(btn.dataset.idregistro, btn.dataset.nombreproducto, btn.dataset.serie);
});

// Búsqueda de Pack por Serie
const btnBuscarPack = document.getElementById('btn-ejecutar-busqueda-pack');
const inputSeriePack = document.getElementById('input-buscar-serie-pack');
const suggestionsPack = document.getElementById('suggestions-serie-pack');
let timeoutBusquedaSerie;

if (inputSeriePack) {
    inputSeriePack.addEventListener('input', function() {
        clearTimeout(timeoutBusquedaSerie);
        const query = this.value.trim();

        if (query.length < 3) {
            suggestionsPack.style.display = 'none';
            return;
        }

        timeoutBusquedaSerie = setTimeout(() => {
            fetch(`/ingresos/buscar-series-pack-ajax?query=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    suggestionsPack.innerHTML = '';
                    if (data.length === 0) {
                        suggestionsPack.innerHTML = '<div class="list-group-item text-muted">No se encontraron series válidas para dividir</div>';
                    } else {
                        data.forEach(item => {
                            const div = document.createElement('a');
                            div.href = '#';
                            div.className = 'list-group-item list-group-item-action py-1';
                            div.innerHTML = `<strong>${item.serie}</strong><br><small class="text-muted" style="font-size: 0.75rem;">${item.nombreProducto}</small>`;
                            div.onclick = function(e) {
                                e.preventDefault();
                                inputSeriePack.value = item.serie;
                                suggestionsPack.style.display = 'none';
                                btnBuscarPack.click();
                            };
                            suggestionsPack.appendChild(div);
                        });
                    }
                    suggestionsPack.style.display = 'block';
                })
                .catch(err => {
                    suggestionsPack.style.display = 'none';
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (e.target.id !== 'input-buscar-serie-pack') {
            if(suggestionsPack) suggestionsPack.style.display = 'none';
        }
    });
}

if (btnBuscarPack) {
    btnBuscarPack.addEventListener('click', function() {
        const inputSerie = document.getElementById('input-buscar-serie-pack');
        const serie = inputSerie.value.trim();
        
        if (!serie) {
            Swal.fire({toast:true, position:'top-end', icon:'warning', title:'Ingresa una serie', showConfirmButton:false, timer:1500});
            return;
        }

        btnBuscarPack.disabled = true;
        btnBuscarPack.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Buscando...';

        fetch(`/ingresos/buscar-pack-por-serie?serie=${encodeURIComponent(serie)}`)
            .then(res => res.json())
            .then(data => {
                btnBuscarPack.disabled = false;
                btnBuscarPack.innerHTML = 'Buscar y Dividir';
                
                if (data.success) {
                    let buscarModalEl = document.getElementById('buscarPackModal');
                    let buscarModal = bootstrap.Modal.getInstance(buscarModalEl);
                    if (buscarModal) buscarModal.hide();
                    
                    abrirModalDivision(data.data.idRegistro, data.data.nombreProducto, data.data.serie);
                    inputSerie.value = '';
                } else {
                    Swal.fire('No se puede dividir', data.message, 'error');
                }
            })
            .catch(err => {
                btnBuscarPack.disabled = false;
                btnBuscarPack.innerHTML = 'Buscar y Dividir';
                Swal.fire('Error', 'Ocurrió un error al buscar la serie', 'error');
            });
    });
    
    // Permitir buscar con Enter
    document.getElementById('input-buscar-serie-pack').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnBuscarPack.click();
        }
    });
}

// Confirmación con SweetAlert antes de enviar
document.getElementById('form-dividir-pack').addEventListener('submit', function(e) {
    e.preventDefault();
    let form = this;
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se dividirá el pack en sus componentes individuales. Esta acción no se puede deshacer fácilmente.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6f42c1',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-scissors"></i> Sí, dividir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
</script>
@endsection