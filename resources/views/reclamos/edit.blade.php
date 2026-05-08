@extends('layouts.app')

@section('title', 'Seguimiento de Reclamo')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <!-- Sidebar de Resumen -->
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Resumen del Caso</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="badge bg-primary fs-5">{{ $reclamo->codigoReclamo }}</span>
                    </div>
                    <hr>
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Cliente:</small><br>
                        @if($reclamo->Cliente)
                        <strong>{{ $reclamo->Cliente->nombre }} {{ $reclamo->Cliente->apellidoPaterno }}</strong>
                        @else
                        {{ $reclamo->nombresCliente ?? 'N/A' }}
                        @endif
                    </p>
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Origen / Plataforma:</small><br>
                        <span class="badge bg-secondary">{{ $reclamo->Plataforma->nombrePlataforma ?? 'N/A' }}</span>
                    </p>
                    @if($reclamo->Publicacion)
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">SKU Publicación:</small><br>
                        <span class="badge bg-info text-dark">{{ $reclamo->Publicacion->sku }}</span><br>
                        <small class="text-muted">{{ $reclamo->Publicacion->titulo }}</small>
                    </p>
                    @endif
                    @if($reclamo->ProductoFisico)
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Producto Físico:</small><br>
                        <strong>{{ $reclamo->ProductoFisico->nombreProducto }}</strong><br>
                        <small class="text-muted">S/N: {{ $reclamo->ProductoFisico->numeroSerie }}</small>
                    </p>
                    @endif
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Orden:</small><br><strong>{{ $reclamo->ordenCompra ?? 'VENTA DIRECTA' }}</strong></p>
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Plataforma:</small><br>{{ $reclamo->Plataforma->nombrePlataforma ?? 'N/A' }}</p>
                    <p class="mb-0"><small class="text-muted text-uppercase fw-bold">Fecha Reg:</small><br>{{ $reclamo->fechaReclamo->format('d-m-Y') }}</p>
                </div>
            </div>

            <!-- Estados Globales -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="{{ route('reclamos.update', $reclamo->idReclamoPlataforma) }}" method="POST">
                        @csrf
                        <label class="form-label fw-bold">Estado General</label>
                        <select name="estadoGeneral" class="form-select mb-3 border-primary">
                            <option value="ABIERTO" {{ $reclamo->estadoGeneral == 'ABIERTO' ? 'selected' : '' }}>ABIERTO</option>
                            <option value="EN ATENCION" {{ $reclamo->estadoGeneral == 'EN ATENCION' ? 'selected' : '' }}>EN ATENCION</option>
                            <option value="CERRADO" {{ $reclamo->estadoGeneral == 'CERRADO' ? 'selected' : '' }}>CERRADO</option>
                        </select>

                        <label class="form-label fw-bold">Resultado Final</label>
                        <select name="resultadoReclamo" class="form-select mb-3">
                            <option value="PENDIENTE" {{ $reclamo->resultadoReclamo == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                            <option value="FAVORABLE" {{ $reclamo->resultadoReclamo == 'FAVORABLE' ? 'selected' : '' }}>FAVORABLE</option>
                            <option value="DESFAVORABLE" {{ $reclamo->resultadoReclamo == 'DESFAVORABLE' ? 'selected' : '' }}>DESFAVORABLE</option>
                        </select>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">Actualizar Estado</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="col-lg-9">

            <!-- Tabs para organización -->
            <ul class="nav nav-tabs mb-4" id="reclamoTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">1. Información Inicial</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="seguimiento-tab" data-bs-toggle="tab" data-bs-target="#seguimiento" type="button">2. Seguimiento y Evidencia</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="diagnostico-tab" data-bs-toggle="tab" data-bs-target="#diagnostico" type="button">3. Diagnóstico Técnico</button>
                </li>
            </ul>

            <div class="tab-content" id="reclamoTabsContent">

                <!-- TAB 1: INFO INICIAL -->
                <div class="tab-pane fade show active" id="info" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Detalle / Relato del Reclamo</label>
                                    <div class="p-3 bg-light rounded border">{{ $reclamo->detalleReclamo }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: SEGUIMIENTO -->
                <div class="tab-pane fade" id="seguimiento" role="tabpanel">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Historial de Contacto</h5>
                                </div>
                                <div class="card-body">
                                    @forelse($reclamo->Seguimientos as $seg)
                                    <div class="d-flex mb-3 pb-3 border-bottom">
                                        <div class="me-3">
                                            <div class="bg-light p-2 rounded-circle">
                                                <i class="bi bi-chat-dots text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between">
                                                <strong class="text-primary">Vía: {{ $seg->respondioCanal ?? 'N/A' }}</strong>
                                                <small class="text-muted">{{ $seg->created_at->format('d/m/Y H:i') }}</small>
                                            </div>
                                            <p class="mb-1">{{ $seg->contactoRealizado }}</p>
                                            <div class="mt-1">
                                                @if($seg->graboVideo) <span class="badge bg-info text-dark me-1"><i class="bi bi-camera-video"></i> Video</span> @endif
                                                @if($seg->tomoFoto) <span class="badge bg-info text-dark me-1"><i class="bi bi-image"></i> Foto</span> @endif
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center py-4 text-muted">
                                        <i class="bi bi-journal-x fs-1"></i>
                                        <p>No hay registros de seguimiento aún.</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 bg-light">
                                <div class="card-header bg-warning text-dark fw-bold">Registrar Seguimiento</div>
                                <div class="card-body">
                                    <form id="formSeguimiento">
                                        @csrf
                                        <input type="hidden" name="idReclamoPlataforma" value="{{ $reclamo->idReclamoPlataforma }}">
                                        <label class="form-label small fw-bold">Vía de Respuesta</label>
                                        <input type="text" name="respondioCanal" class="form-control form-control-sm mb-2" placeholder="Ej: WhatsApp, Llamada, Correo" required>
                                        <label class="form-label small fw-bold">Resumen del Contacto</label>
                                        <textarea name="contactoRealizado" class="form-control form-control-sm mb-3" rows="3" placeholder="Ej: Se envió mensaje por WhatsApp..." required></textarea>

                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="graboVideo" value="1">
                                            <label class="form-check-label small">Se obtuvo Video</label>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" name="tomoFoto" value="1">
                                            <label class="form-check-label small">Se obtuvo Foto</label>
                                        </div>

                                        <button type="button" onclick="saveSeguimiento()" class="btn btn-warning btn-sm w-100 fw-bold">Guardar Seguimiento</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: DIAGNOSTICO -->
                <div class="tab-pane fade" id="diagnostico" role="tabpanel">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Evaluaciones Técnicas</h5>
                                </div>
                                <div class="card-body">
                                    @forelse($reclamo->Diagnosticos as $diag)
                                    <div class="p-3 border rounded mb-3 bg-white">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge bg-dark">Producto: {{ $diag->ProductoFisico->nombreProducto ?? 'N/A' }}</span>
                                            <small class="text-muted">{{ $diag->created_at->format('d/m/Y') }} | Técnico: {{ $diag->Tecnico->user ?? 'N/A' }}</small>
                                        </div>
                                        <p class="mb-1"><strong>S.A:</strong> {{ $diag->situacionActual }}</p>
                                        <p class="mb-1"><strong>Diag:</strong> {{ $diag->diagnosticoPrevio }}</p>
                                        <p class="mb-1 text-muted small">{{ $diag->descripcionDiagnostico }}</p>
                                        <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                            <span class="text-success fw-bold">{{ $diag->respuestaSolucion }}</span>
                                            <span class="text-info">{{ $diag->estadoEvolucion }}</span>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center py-4 text-muted">
                                        <p>No se ha realizado un diagnóstico técnico formal.</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-primary text-white">Nuevo Diagnóstico</div>
                                <div class="card-body">
                                    <form id="formDiagnostico">
                                        @csrf
                                        <input type="hidden" name="idReclamoPlataforma" value="{{ $reclamo->idReclamoPlataforma }}">

                                        <!-- Buscador de Producto -->
                                        <label class="form-label small fw-bold text-primary">Vincular Producto Físico (Serial)</label>
                                        <div class="input-group input-group-sm mb-3" style="position: relative">
                                            <input type="text" class="form-control" placeholder="Escriba Serial..." id="input-serial-search">
                                            <ul class="list-group w-100 shadow" style="position: absolute; top:100%; z-index: 2000;" id="suggestion-registro"></ul>
                                        </div>
                                        <input type="hidden" name="idRegistro" id="input-id-registro">
                                        <div id="product-info" class="p-2 mb-3 bg-light rounded d-none">
                                            <small class="d-block fw-bold" id="product-name"></small>
                                            <small class="text-muted" id="product-serial"></small>
                                        </div>

                                        <label class="form-label small fw-bold">Situación Actual</label>
                                        <input type="text" name="situacionActual" class="form-control form-control-sm mb-2">

                                        <label class="form-label small fw-bold">Diagnóstico</label>
                                        <select name="diagnosticoPrevio" class="form-select form-select-sm mb-2">
                                            <option value="FALLA FABRICA">FALLA FABRICA</option>
                                            <option value="DAÑO POR CLIENTE">DAÑO POR CLIENTE</option>
                                            <option value="ACCESORIO FALTANTE">ACCESORIO FALTANTE</option>
                                            <option value="MAL USO">MAL USO</option>
                                        </select>

                                        <label class="form-label small fw-bold">Detalle Técnico</label>
                                        <textarea name="descripcionDiagnostico" class="form-control form-control-sm mb-2" rows="2"></textarea>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-bold">Solución</label>
                                                <select name="respuestaSolucion" class="form-select form-select-sm">
                                                    <option value="CAMBIO">CAMBIO</option>
                                                    <option value="REPARACION">REPARACION</option>
                                                    <option value="NOTA DE CREDITO">NOTA DE CREDITO</option>
                                                    <option value="RECHAZADO">RECHAZADO</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold">Evolución</label>
                                                <select name="estadoEvolucion" class="form-select form-select-sm">
                                                    <option value="FINALIZADO">FINALIZADO</option>
                                                    <option value="EN TALLER">EN TALLER</option>
                                                    <option value="ESPERA REPUESTO">ESPERA REPUESTO</option>
                                                </select>
                                            </div>
                                        </div>

                                        <button type="button" onclick="saveDiagnostico()" class="btn btn-primary btn-sm w-100 fw-bold">Guardar Diagnóstico</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Guardar Seguimiento vía AJAX
    function saveSeguimiento() {
        const formData = new FormData(document.getElementById('formSeguimiento'));
        fetch('{{ route("reclamos.seguimiento.add", $reclamo->idReclamoPlataforma) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert('Error al guardar seguimiento');
        });
    }

    // Guardar Diagnóstico vía AJAX
    function saveDiagnostico() {
        if (!document.getElementById('input-id-registro').value) {
            alert('Debe vincular un producto mediante el serial');
            return;
        }
        const formData = new FormData(document.getElementById('formDiagnostico'));
        fetch('{{ route("reclamos.diagnostico.add", $reclamo->idReclamoPlataforma) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert('Error al guardar diagnóstico');
        });
    }

    // Buscador de Seriales (Basado en createGarantia.js logic)
    const inputSerial = document.getElementById('input-serial-search');
    const suggestionBox = document.getElementById('suggestion-registro');
    const inputIdReg = document.getElementById('input-id-registro');
    const productInfo = document.getElementById('product-info');

    inputSerial.addEventListener('input', function() {
        if (this.value.length > 2) {
            fetch(`/egresos/searchregistro?query=${this.value}`)
                .then(r => r.json())
                .then(data => {
                    suggestionBox.innerHTML = '';
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action small';
                        li.innerHTML = `<strong>${item.nombreProducto}</strong><br><span class="text-muted">${item.numeroSerie}</span>`;
                        li.onclick = () => {
                            inputIdReg.value = item.idRegistroProducto;
                            inputSerial.value = item.numeroSerie;
                            document.getElementById('product-name').textContent = item.nombreProducto;
                            document.getElementById('product-serial').textContent = item.numeroSerie;
                            productInfo.classList.remove('d-none');
                            suggestionBox.innerHTML = '';
                        };
                        suggestionBox.appendChild(li);
                    });
                });
        } else {
            suggestionBox.innerHTML = '';
        }
    });
</script>
@endsection