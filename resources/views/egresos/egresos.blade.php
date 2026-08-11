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
                <input type="date" class="form-control" id="filtro-dia" name="dia"
                    value="{{ request('dia')}}"
                    min="{{ $fecha->copy()->startOfMonth()->format('Y-m-d') }}"
                    max="{{ $fecha->copy()->endOfMonth()->format('Y-m-d') }}"
                    onblur="filtrarPorDia(this.value)">
                <input type="month" class="form-control hidde-month" id="month" name="month"
                    value="{{$fecha->format('Y-m')}}">

                <button class="btn btn-light border d-md-none" onclick="hiddeInputDate('month')">
                    <i class="bi bi-calendar3"></i> <!-- Ícono de calendario -->
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
                <a class="btn btn-warning me-1 mb-1 mb-md-0" href="{{route('egresos.pendientes_envios')}}" target="_blank"><i class="bi bi-clock-history"></i><span class="d-none d-md-inline"> Pendiente de Egresar</span></a>
                <a class="btn btn-primary me-1 mb-1 mb-md-0" href="{{route('egresos.masivos')}}" target="_blank"><i class="bi bi-lightning-charge-fill"></i><span class="d-none d-md-inline"> Egreso Masivo</span></a>
                <a class="btn btn-success mb-1 mb-md-0" href="{{route('createegreso')}}" target="_blank"><i class="bi bi-plus-lg"></i><span class="d-none d-md-inline"> Nuevo Egreso</span> </a>
                @endif
                @endforeach
            </div>
        </div>
        <br>
        <div id="container-lista-egresos">
            <x-lista_egresos :egresos="$egresos" :container="'container-lista-egresos'" :usuarios="$usuarios" />
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $egresos->withQueryString()->links('pagination::bootstrap-5') }}
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
                                <div class="col-md-12 d-flex justify-content-between align-items-center">
                                    <input type="hidden" id="modal-egreso-transaccion" name="transaccion">
                                    <input type="hidden" id="modal-egreso-id" name="idegreso">
                                    <input type="hidden" id="modal-egreso-has-detalle-venta" value="false">
                                    <h5 id="modal-egreso-titulo" class="mb-0"></h5>
                                    @foreach ($user->Accesos as $vista)
                                    @if($vista->idVista == 9)
                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" id="btn-edit-egreso" onclick="toggleEditEgreso()">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    @endif
                                    @endforeach
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
                                <div class="col-md-12 mt-2 d-none" id="container-edit-fechas">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label fw-bold mb-0"><small>Fecha Compra:</small></label>
                                            <input type="date" name="fecha_compra" id="modal-egreso-edit-fecha-compra" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold mb-0"><small>Fecha Despacho:</small></label>
                                            <input type="date" name="fecha_despacho" id="modal-egreso-edit-fecha-despacho" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p class="mb-0" id="modal-egreso-precio-display"><small><strong>Precio Venta:</strong> S/ <span id="modal-egreso-precio-text">0.00</span></small></p>
                                    <p class="mb-0"><small id="modal-egreso-usuario"></small></p>
                                </div>
                                <div class="col-md-12 mt-1" id="modal-egreso-publicidad">

                                </div>
                                <div class="col-md-12 mt-2 d-none" id="container-edit-publicacion">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label fw-bold mb-0"><small>SKU:</small></label>
                                            <div style="position:relative">
                                                <input type="text" name="sku" id="modal-egreso-edit-sku" oninput="searchPublicacion(this)" class="form-control form-control-sm" autocomplete="off">
                                                <ul class="list-group w-100" style="position:absolute;top:100%;z-index:1100" id="suggestions-sku"></ul>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold mb-0"><small>Nro de Orden:</small></label>
                                            <input type="text" name="nro_orden" id="modal-egreso-edit-nro-orden" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-2 d-none" id="container-edit-precio">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label fw-bold mb-0"><small>Precio Venta:</small></label>
                                            <input type="number" step="0.01" min="0" name="precio_venta" id="modal-egreso-edit-precio" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold mb-0"><small>Precio Costo Base:</small></label>
                                            <input type="number" step="0.01" min="0" name="precio_costo" id="modal-egreso-edit-precio-costo" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <hr class="mt-3 mb-2">
                                    <div class="row align-items-end">
                                        <div class="col-12 mb-2">
                                            <label class="form-label text-success fw-bold mb-0"><small><i class="bi bi-plus-circle"></i> Añadir producto a esta misma Orden</small></label>
                                        </div>
                                        <div class="col-12 mb-2" style="position:relative">
                                            <input type="text" id="append_serialnumber" name="append_serialnumber" class="form-control form-control-sm" placeholder="Escanear Serial Number" oninput="searchRegistroAppend(this)" autocomplete="off">
                                            <input type="hidden" id="hidden_append_idregistro" name="append_idregistro" value="">
                                            <ul class="list-group w-100" style="position:absolute;top:100%;z-index:1100" id="suggestions-append-serial"></ul>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label fw-bold mb-0"><small>SKU:</small></label>
                                            <input type="text" id="append_sku" name="append_sku" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label fw-bold mb-0"><small>Precio:</small></label>
                                            <input type="number" step="0.01" min="0" id="append_precio" name="append_precio" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-4 text-end">
                                            <button type="button" class="btn btn-sm btn-success w-100" onclick="formDetailEgreso('append')">Añadir</button>
                                        </div>
                                    </div>
                                    <div class="row align-items-end d-none" id="container-upgrade-section">
                                        <div class="col-12 mt-2 mb-2">
                                            <hr class="mt-1 mb-2">
                                            <label class="form-label text-primary fw-bold mb-0"><small><i class="bi bi-cpu-fill"></i> Añadir Componente (Upgrade RAM/SSD)</small></label>
                                        </div>
                                        <div class="col-6 mb-2" style="position:relative">
                                            <label class="form-label fw-bold mb-0"><small>Serie Componente:</small></label>
                                            <input type="text" id="upgrade_serialnumber" name="upgrade_serialnumber" class="form-control form-control-sm" placeholder="Escanear Serial Number" oninput="searchRegistroUpgrade(this)" autocomplete="off">
                                            <input type="hidden" id="hidden_upgrade_idregistro" name="upgrade_idregistro" value="">
                                            <ul class="list-group w-100" style="position:absolute;top:100%;z-index:1100" id="suggestions-upgrade-serial"></ul>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <label class="form-label fw-bold mb-0"><small>Costo Componente:</small></label>
                                            <input type="number" step="0.01" min="0" id="upgrade_costo" name="upgrade_costo" class="form-control form-control-sm" placeholder="S/ 0.00">
                                        </div>
                                        <div class="col-8">
                                            <small class="text-muted" style="font-size: 0.75rem;">Su costo se sumará a este equipo.</small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="formDetailEgreso('upgrade')">Upgrade</button>
                                        </div>
                                    </div>
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
        </form>

        <!-- Modal de Confirmación de Migración -->
        <div class="modal fade" id="confirmMigrationModal" tabindex="-1" aria-labelledby="confirmMigrationModalLabel" aria-hidden="true" style="z-index: 1070; backdrop-filter: blur(4px);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-warning shadow-lg">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold text-uppercase mb-0" id="confirmMigrationModalLabel">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmar Migración
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <p class="mb-2">Este egreso corresponde a un registro histórico (anterior al sistema de ventas).</p>
                        <p class="mb-3 text-secondary">
                            <i class="bi bi-info-circle me-1 text-primary"></i> Al guardar esta modificación, el sistema creará automáticamente un registro de <strong>Venta</strong> y <strong>DetalleVenta</strong> para migrar este registro de forma permanente.
                        </p>
                        <p class="mb-0 fw-bold text-center">¿Está seguro de que desea continuar?</p>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                        <button type="button" id="btn-confirm-migration-submit" class="btn btn-warning fw-bold text-dark px-4 shadow-sm">
                            <i class="bi bi-check-lg"></i> Sí, continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <script src="{{asset('js/egresos.js')}}"></script>
    @endsection