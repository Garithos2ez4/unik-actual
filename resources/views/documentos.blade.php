 @extends('layouts.app')

@section('title', 'Documentos')

@section('content')
<div class="container">
    <div class="bg-secondary" id="hidden-body"
        style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
    </div>
    <br>
    <div class="row">
        <div class="col-9 col-md-7 col-lg-5 text-end" style="position:relative;z-index:999">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Nro documento o Serie..." id="search">
                <ul class="list-group w-100" style="position:absolute;top:100%;z-index:1000" id="suggestions">
                </ul>
            </div>
        </div>
        <div class="col-lg-4 d-none d-lg-block"></div>
        <div class="col-3 col-md-5 col-lg-3 text-end">
            <input type="month" class="form-control hidde-month" id="month" name="month" placeholder="MM-YYYY"
                value="{{$fecha->format('Y-m')}}">
            <button class="btn btn-light border d-md-none" onclick="hiddeInputDate('month')">
                <i class="bi bi-calendar3"></i>  <!-- Ícono de calendario -->
            </button>
        </div>
    </div>
    <br>
    <div class="row mb-2">
        <div class="col-6 col-md-6">
            <h2><i class="bi bi-folder-fill"></i> Documentos <span
                    class="text-capitalize text-secondary fw-light"><em>({{$fecha->translatedFormat('F')}})</em></span>
            </h2>
        </div>
        <div class="col-6 col-md-6 text-end">
            <a href="{{ route('licencias.index') }}" class="btn btn-primary mb-2">
                <i class="bi bi-key-fill"></i>
                <span class="d-none d-md-inline">Licencias</span>
            </a>
            <a href="{{route('ingresos', [$fecha->format('Y-m')])}}" class="btn btn-success mb-2"><i
                class="bi bi-file-earmark-plus-fill"></i> <span class="d-none d-md-inline">Ingresos</span></a>
            <a href="{{route('egresos', [$fecha->format('Y-m')])}}" class="btn btn-warning mb-2"><i
                        class="bi bi-file-earmark-minus-fill"></i> <span class="d-none d-md-inline">Egresos</span></a>
            @foreach ($user->Accesos as $vista)
                @switch($vista->idVista)
                    @case(8)
                        <a href="{{route('traslados')}}" class="btn btn-info mb-2"><i class="bi bi-arrow-left-right"></i>
                        <span class="d-none d-md-inline">Traslado</span></a>
                        @break
                    @case(9)
                        <a href="{{route('garantias',[$fecha->format('Y-m')])}}" class="btn btn-marron mb-2">
                        <i class="bi bi-award-fill"></i> <span class="d-none d-md-inline">Garantías</span></a>
                        @break 
                    @default
                        
                @endswitch
                
            @endforeach
                
        </div>
    </div>
    <form action="{{url()->current()}}" method="get" id="form-filtro-componente">
        <div class="row mb-2">
            <div class="col-6 col-md-3 col-lg-2">
                <small>Usuario</small>
                <select class="form-select form-select-sm filtro-componente" name="filtro[usuario]" >
                    <option value="">Todos</option>
                    @foreach ($filtros['users'] as $usuario)
                    <option value="{{$usuario->idUser}}">{{$usuario->Usuario->user}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Proveedores</small>
                <select class="form-select form-select-sm filtro-componente"  name="filtro[proveedor]">
                    <option value="">Todos</option>
                    @foreach ($filtros['proveedores'] as $proveedor)
                    <option value="{{$proveedor->idProveedor}}">{{$proveedor->Preveedor->nombreProveedor}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Documento</small>
                <select class="form-select form-select-sm filtro-componente"  name="filtro[documento]">
                    <option value="">Todos</option>
                    @foreach ($filtros['documentos'] as $doc)
                    <option value="{{$doc->idTipoComprobante}}">{{$doc->TipoComprobante->descripcion}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <small>Estado</small>
                <select class="form-select form-select-sm filtro-componente"  name="filtro[estado]">
                    <option value="">Todos</option>
                    @foreach ($filtros['estados'] as $estado)
                    <option value="{{$estado->estado}}">{{$estado->estado}}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
    <div id="container-lista-doc">
        <x-lista_documentos :documentos="$documentos" :container="'container-lista-doc'"/>
    </div>
</div>
<script src="{{asset('js/documentos.js')}}"></script>
<script src="{{asset('js/filtro_componente.js')}}"></script>
@endsection