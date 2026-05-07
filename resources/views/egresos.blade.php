@extends('layouts.app')

@section('title', 'Egresos')
@php
    setlocale(LC_TIME, 'es_ES.UTF-8');
@endphp

@section('content')
    <div class="container">
        <div class="bg-secondary" id="hidden-body"
            style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
        </div>
        <br>
        <div class="row">
            <div class="col-9 col-md-7 col-lg-5">
                <div class="input-group mb-3" style="z-index:1000">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" oninput="searchEgreso(this)" placeholder="Serial Number..." id="search">
                    <ul class="list-group w-100" style="position:absolute;top:100%;z-index:1000" id="suggestions-egresos">
                    </ul>
                </div>
            </div>
            <div class="col-md-3 d-none d-lg-block"></div>
            <div class="col-3 col-md-5 col-lg-4 text-end">
                <input type="month" class="form-control hidde-month" id="month" name="month"
                    value="{{ $fecha->format('Y-m') }}">
                <button class="btn btn-light border d-md-none" onclick="hiddeInputDate('month')">
                    <i class="bi bi-calendar3"></i>  <!-- Ícono de calendario -->
                </button>
            </div>
            <div class="col-10 col-md-8">
                <h2><a href="{{ route('documentos', [$fecha->format('Y-m')]) }}" class="text-secondary"><i
                            class="bi bi-arrow-left-circle"></i></a> <i class="bi bi-file-earmark-minus-fill"></i>
                    Egresos<span
                        class="text-capitalize text-secondary fw-light"><em>({{ $fecha->translatedFormat('F Y') }})</em></span>
                </h2>
            </div>

            <div class="col-2 col-md-4 text-end">
                @foreach ($user->Accesos as $vista)
                    @if($vista->idVista == 9)
                    <a class="btn btn-success" href="{{route('createegreso')}}" target="_blank"><i class="bi bi-plus-lg"></i><span class="d-none d-md-inline"> Nuevo Egreso</span> </a>
                    @endif
                @endforeach
            </div>
        </div>
        <br>
        <div id="container-lista-egresos">
            <x-lista_egresos :egresos="$egresos" :container="'container-lista-egresos'" />
        </div>

        
        <!-- Modal -->
        <form action="{{route('devolucionegreso')}}" method="post" id="form-detail-egreso">
            @csrf
            <div class="modal fade" id="detailEgresoModal" tabindex="-1" aria-labelledby="detailEgresoModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="hidden" id="modal-egreso-transaccion" name="transaccion">
                                    <input type="hidden" id="modal-egreso-id" name="idegreso">
                                    <h5 id="modal-egreso-titulo"></h5>
                                </div>
                                <div class="col-md-6 text-secondary">
                                    <h6 id="modal-egreso-serialnumber"></h6>
                                </div>
                                <div class="col-md-6 text-secondary text-end">
                                    <h6 id="modal-egreso-estado"></h6>
                                </div>
                                <div class="col-md-6" id="modal-egreso-fecha">
                                    <p class="mb-0"><strong></strong></p>
                                    <p class="mt-0"><strong></strong></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small><strong></strong></small>
                                    <p class="mb-0"><small id="modal-egreso-usuario"></small></p>
                                </div>
                                <div class="col-md-6 mt-1" id="modal-egreso-publicidad">
                                    
                                </div>
                                <div id="container-campos-devolucion">
                                    <div class="col-md-12 mt-2">
                                        <label class="form-label fw-bold mb-0">Fecha de Retorno F&iacute;sico:</label>
                                        <input type="date" name="fecha_devolucion" id="modal-egreso-fecha-devolucion" class="form-control mb-2" required>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="check-fallo-entrega-egreso">
                                            <label class="form-check-label text-primary fw-bold" style="cursor: pointer" for="check-fallo-entrega-egreso">
                                                Fallo de entrega (Autocompletar)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <label class="form-label fw-bold mb-0 mt-1">Observacion:</label>
                                    <textarea class="form-control" maxlength="500" id="modal-egreso-observacion" name="observacion" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row w-100 pe-0 ps-0">
                                <div class="col-md-6 ps-0">
                                    <button type="button" onclick="formDetailEgreso('devolucion')" id="modal-egreso-btn-devolucion" class="btn btn-warning"><i class="bi bi-arrow-clockwise"></i> Devoluci&oacute;n</button>
                                </div>
                                <div class="col-md-6 pe-0 text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Cerrar</button>
                                    <button type="button" onclick="formDetailEgreso('update')" class="btn btn-primary"><i class="bi bi-floppy"></i> Actualizar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <script src="{{asset('js/egresos.js')}}"></script>
@endsection
