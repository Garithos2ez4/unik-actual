<div class="row border shadow rounded-3 pt-2 mb-4">
    <div class="col-md-12 pb-2">
        <div class="accordion accordion-flush" id="accordionAlertas">
            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="flush-headingAlertas">
                    <button class="accordion-button collapsed fs-5 fw-bold flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseAlertas" aria-expanded="false" aria-controls="flush-collapseAlertas">
                        <i class="bi bi-bell-fill text-warning me-2"></i> Alertas de Precios
                        @if($alertas->count() > 0)
                        <span class="badge bg-danger ms-2">{{$alertas->count()}}</span>
                        @endif
                    </button>
                    <button class="btn btn-primary ms-2 my-2 text-nowrap" style="z-index: 10;" onclick="ejecutarBotPrecios(this)">
                        <i class="bi bi-robot"></i> Ejecutar Bot
                    </button>
                    <button class="btn btn-secondary ms-2 my-2 text-nowrap" style="z-index: 10;" onclick="verLogBot()">
                        <i class="bi bi-file-earmark-text"></i> Ver Último Reporte
                    </button>
                    <button class="btn btn-success ms-2 my-2 text-nowrap" style="z-index: 10;" data-bs-toggle="modal" data-bs-target="#modalNuevaAlerta">
                        <i class="bi bi-plus-circle"></i> Nueva Alerta
                    </button>
                    <button class="btn btn-info ms-2 me-3 my-2 text-nowrap text-white" style="z-index: 10;" data-bs-toggle="modal" data-bs-target="#modalModelosVigilados">
                        <i class="bi bi-eye"></i> Modelos Vigilados ({{ $vigilados->count() ?? 0 }})
                    </button>
                </h2>
                <div id="flush-collapseAlertas" class="accordion-collapse collapse" aria-labelledby="flush-headingAlertas" data-bs-parent="#accordionAlertas">
                    <div class="accordion-body">
                        <p class="text-secondary mb-3">Monitoreo de productos que requieren atención en su precio.</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Modelo</th>
                                        <th>Mi Precio</th>
                                        <th>Competidor</th>
                                        <th>Precio Competidor</th>
                                        <th>Diferencia (%)</th>
                                        <th>Sugerencia</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alertas as $alerta)
                                    <tr>
                                        <td class="fw-bold">{{$alerta->modelo}}</td>
                                        <td>S/ {{number_format($alerta->mi_precio, 2)}}</td>
                                        <td>{{$alerta->competidor}}</td>
                                        <td>S/ {{number_format($alerta->precio_competidor, 2)}}</td>
                                        <td>
                                            @if($alerta->diferencia_porcentaje < 0)
                                                <span class="text-danger fw-bold"><i class="bi bi-arrow-down-right"></i> {{$alerta->diferencia_porcentaje}}%</span>
                                                @elseif($alerta->diferencia_porcentaje > 0)
                                                <span class="text-success fw-bold"><i class="bi bi-arrow-up-right"></i> +{{$alerta->diferencia_porcentaje}}%</span>
                                                @else
                                                <span class="text-secondary fw-bold">0%</span>
                                                @endif
                                        </td>
                                        <td>{{$alerta->sugerencia}}</td>
                                        <td>
                                            <select class="form-select form-select-sm border-0 fw-bold @if($alerta->estado == 'pendiente') text-warning bg-light @elseif($alerta->estado == 'resuelto') text-success bg-light @elseif($alerta->estado == 'procesada') text-info bg-light @else text-secondary bg-light @endif" onchange="updateAlertaEstado({{$alerta->id}}, this)">
                                                <option value="pendiente" @if($alerta->estado == 'pendiente') selected @endif>Pendiente</option>
                                                <option value="resuelto" @if($alerta->estado == 'resuelto') selected @endif>Resuelto</option>
                                                <option value="procesada" @if($alerta->estado == 'procesada') selected @endif>Procesada</option>
                                                <option value="ignorado" @if($alerta->estado == 'ignorado') selected @endif>Ignorado</option>
                                            </select>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay alertas de precio registradas.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modelos Vigilados -->
<div class="modal fade" id="modalModelosVigilados" tabindex="-1" aria-labelledby="modalModelosVigiladosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalModelosVigiladosLabel"><i class="bi bi-eye text-info"></i> Modelos Vigilados por el Bot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary small mb-3">Estos modelos serán escaneados por el bot automáticamente cada vez que se ejecute, sin importar si están o no en el Top 20.</p>
                <form action="{{ route('addvigilado') }}" method="POST" class="d-flex mb-4 gap-2">
                    @csrf
                    <input type="text" name="modelo" class="form-control" required placeholder="Ingresar Modelo del Producto (Exacto)">
                    <button type="submit" class="btn btn-success text-nowrap"><i class="bi bi-plus"></i> Agregar</button>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Modelo</th>
                                <th>Agregado el</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vigilados as $index => $vig)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-bold">{{ $vig->modelo }}</td>
                                <td>{{ $vig->created_at ? $vig->created_at->format('d/m/Y') : '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('deletevigilado', $vig->id) }}" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No hay modelos vigilados agregados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@include('configuracion.components.logic.alertas_precios_logic')

<!-- Modal Log Bot -->
<div class="modal fade" id="modalLogBot" tabindex="-1" aria-labelledby="modalLogBotLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalLogBotLabel"><i class="bi bi-file-earmark-text text-secondary"></i> Reporte del Bot de Precios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-dark text-light">
                <pre id="logBotContent" style="white-space: pre-wrap; font-size: 0.85rem;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="verLogBot()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Alerta -->
<div class="modal fade" id="modalNuevaAlerta" tabindex="-1" aria-labelledby="modalNuevaAlertaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalNuevaAlertaLabel"><i class="bi bi-bell-fill text-warning"></i> Registrar Alerta Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaAlerta">
                    <div class="mb-3">
                        <label for="alertaModelo" class="form-label fw-bold">Modelo del Producto *</label>
                        <input type="text" class="form-control" id="alertaModelo" required placeholder="Ej: MONITOR TEROS 24">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="alertaMiPrecio" class="form-label fw-bold">Mi Precio (S/) *</label>
                            <input type="number" step="0.01" class="form-control" id="alertaMiPrecio" required placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="alertaPrecioCompetidor" class="form-label fw-bold">Precio Competidor (S/) *</label>
                            <input type="number" step="0.01" class="form-control" id="alertaPrecioCompetidor" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="alertaCompetidor" class="form-label fw-bold">Competidor (Tienda / Link) *</label>
                        <input type="text" class="form-control" id="alertaCompetidor" required placeholder="Ej: Falabella, MemoryKings, Linio...">
                    </div>
                    <div class="mb-3">
                        <label for="alertaSugerencia" class="form-label fw-bold">Sugerencia (Opcional)</label>
                        <input type="text" class="form-control" id="alertaSugerencia" placeholder="Ej: Bajar S/ 10.00 para igualar">
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarAlertaManual(this)"><i class="bi bi-floppy"></i> Guardar Alerta</button>
            </div>
        </div>
    </div>
</div>