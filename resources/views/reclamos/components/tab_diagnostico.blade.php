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
                                        <!-- Buscador de Producto -->
                                        <label class="form-label small fw-bold text-primary">Vincular Producto Físico (Serial)</label>
                                        <div class="input-group input-group-sm mb-3" style="position: relative">
                                            <input type="text" class="form-control" placeholder="Escriba Serial..." id="input-serial-search"
                                                @if($reclamo->ProductoFisico)
                                            value="{{ $reclamo->ProductoFisico->numeroSerie }}" readonly
                                            @endif
                                            >
                                            <ul class="list-group w-100 shadow" style="position: absolute; top:100%; z-index: 2000;" id="suggestion-registro"></ul>
                                        </div>

                                        <!-- Input oculto que guarda el ID real para la Base de Datos -->
                                        <input type="hidden" name="idRegistro" id="input-id-registro"
                                            @if($reclamo->ProductoFisico)
                                        value="{{ $reclamo->ProductoFisico->idRegistro }}"
                                        @endif
                                        >

                                        <!-- Div de información pre-llenado si existe el producto -->
                                        <div id="product-info" class="p-2 mb-3 bg-light rounded @if(!$reclamo->ProductoFisico) d-none @endif">
                                            <small class="d-block fw-bold" id="product-name">
                                                {{ $reclamo->ProductoFisico->nombreProducto ?? '' }}
                                            </small>
                                            <small class="text-muted" id="product-serial">
                                                {{ $reclamo->ProductoFisico->numeroSerie ?? '' }}
                                            </small>
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
