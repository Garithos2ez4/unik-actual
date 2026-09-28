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
                                            <div class="d-flex justify-content-between mb-2">
                                                <strong class="text-primary">Vía: {{ $seg->respondioCanal }}</strong>
                                                <small class="text-muted">{{ $seg->created_at->format('d/m/Y H:i') }} | Op: {{ $seg->Operador->user ?? 'N/A' }}</small>
                                            </div>

                                            <!-- Mostramos el mensaje directamente del padre -->
                                            <div class="p-2 mb-2 bg-light rounded border-start border-3 border-secondary">
                                                <p class="mb-1 small">{{ $seg->mensajeRespuesta }}</p>

                                                <!-- Iteramos las evidencias si es que hay -->
                                                @if($seg->Evidencias->count() > 0)
                                                <div class="mt-2 d-flex gap-2">
                                                    @foreach($seg->Evidencias as $evidencia)
                                                    <a href="{{ $evidencia->urlArchivo }}" target="_blank" class="badge bg-info text-dark text-decoration-none">
                                                        @if($evidencia->tipoEvidencia == 'FOTO') <i class="bi bi-image"></i> Ver Foto
                                                        @else <i class="bi bi-camera-video"></i> Ver Video
                                                        @endif
                                                    </a>
                                                    @endforeach
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center py-4 text-muted">
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
                                        <select name="respondioCanal" class="form-select form-select-sm mb-2" required>
                                            <option value="" disabled selected>Seleccione una vía...</option>
                                            <option value="WhatsApp">WhatsApp</option>
                                            <option value="Llamada">Llamada</option>
                                            <option value="Correo">Correo Electrónico</option>
                                            <option value="Facebook">Facebook Messenger</option>
                                            <option value="Instagram">Instagram Direct</option>
                                            <option value="Plataforma">Mensajería de Plataforma</option>
                                            <option value="Otro">Otro</option>
                                        </select>

                                        <label class="form-label small fw-bold">Mensaje / Resumen</label>
                                        <textarea name="mensajeRespuesta" class="form-control form-control-sm mb-3" rows="3" placeholder="Se acordó con el cliente..." required></textarea>

                                        <label class="form-label small fw-bold text-primary">Evidencias Adjuntas (Opcionales)</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light" style="width: 80px;"><i class="bi bi-image"></i>&nbsp;Foto</span>
                                            <input type="url" name="urlFoto" class="form-control" placeholder="Link de Drive, Imgur...">
                                        </div>
                                        <div class="input-group input-group-sm mb-3">
                                            <span class="input-group-text bg-light" style="width: 80px;"><i class="bi bi-camera-video"></i>&nbsp;Video</span>
                                            <input type="url" name="urlVideo" class="form-control" placeholder="Link de Drive, YouTube...">
                                        </div>

                                        <button type="button" onclick="saveSeguimiento()" class="btn btn-warning btn-sm w-100 fw-bold">Guardar Seguimiento</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
