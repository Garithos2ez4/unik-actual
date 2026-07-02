<div class="mt-3">
    <h6 class="text-primary border-bottom pb-2 mb-2 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bookshelf"></i> Estantes / Racks:</span>
        <span class="badge bg-secondary rounded-pill" title="Total de estantes">{{ $almacen->Ubicaciones->count() }}</span>
    </h6>

    <div class="estantes-scroll-container pe-1" style="max-height: 320px; overflow-y: auto; overflow-x: hidden;">
        <ul class="list-group list-group-flush mb-2 border rounded shadow-sm">
            @forelse($almacen->Ubicaciones as $ubicacion)
            <li class="list-group-item d-flex justify-content-between align-items-center p-2 hover-estante transition-all">

                <div class="me-2 text-break" style="flex: 1;">
                    <strong class="text-dark">{{$ubicacion->nombre}}</strong>
                    @if($ubicacion->descripcion)
                    <br>
                    <small class="text-muted d-block" title="{{$ubicacion->descripcion}}">
                        {{$ubicacion->descripcion}}
                    </small>
                    @endif

                    @if($ubicacion->foto)
                    <a href="{{$ubicacion->foto_url}}" target="_blank" title="Ver Foto del Estante" class="text-decoration-none ms-1">
                        <i class="bi bi-image text-info"></i>
                    </a>
                    @endif

                    <div class="mt-2 p-1 bg-light border rounded">
                        <small class="fw-bold text-muted" style="font-size: 0.75rem;">Filas:</small>
                        @php
                            $filasActuales = \App\Models\UbicacionEstante::where('nombre_rack', $ubicacion->nombre)
                                ->where('idAlmacen', $almacen->idAlmacen)
                                ->orderBy('fila_estante')
                                ->get();
                        @endphp
                        @foreach($filasActuales as $fila)
                            <span class="badge bg-info text-dark mb-1" style="font-size: 0.7rem;">
                                Fila {{ $fila->fila_estante }} 
                                <a href="{{ route('deletefila', $fila->idUbicacionExacta) }}" class="text-danger ms-1 text-decoration-none" title="Eliminar fila" onclick="return confirm('¿Estás seguro de eliminar esta fila?')"><i class="bi bi-x-circle-fill"></i></a>
                            </span>
                        @endforeach
                        <form action="{{ route('addfila') }}" method="POST" class="d-inline-block ms-1">
                            @csrf
                            <input type="hidden" name="idAlmacen" value="{{ $almacen->idAlmacen }}">
                            <input type="hidden" name="nombre_rack" value="{{ $ubicacion->nombre }}">
                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-1 mb-1" style="font-size: 0.7rem;" title="Agregar nueva fila a este estante"><i class="bi bi-plus"></i></button>
                        </form>
                    </div>
                </div>

                <div class="d-flex gap-1 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-warning" title="Editar" data-bs-toggle="modal" data-bs-target="#editRackModal{{$ubicacion->idUbicacion}}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form action="{{route('deleteubicacion', $ubicacion->idUbicacion)}}" method="POST" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>

                <!-- Modal para Editar Rack -->
                <div class="modal fade" id="editRackModal{{$ubicacion->idUbicacion}}" tabindex="-1" aria-labelledby="editRackModalLabel{{$ubicacion->idUbicacion}}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form action="{{route('updateubicacion', $ubicacion->idUbicacion)}}" method="POST" enctype="multipart/form-data" class="modal-content shadow w-100">
                            @csrf
                            <div class="modal-header bg-warning border-0 text-dark">
                                <h5 class="modal-title fs-5 fw-bold" id="editRackModalLabel{{$ubicacion->idUbicacion}}">
                                    <i class="bi bi-pencil-square me-1"></i> Editar Rack/Estante
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-start bg-light">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombre" value="{{$ubicacion->nombre}}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Descripción <span class="text-muted fw-normal">(Opcional)</span></label>
                                    <textarea class="form-control" name="descripcion" rows="2">{{$ubicacion->descripcion}}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Foto del Estante/Rack <span class="text-muted fw-normal">(Opcional)</span></label>
                                    <input type="file" class="form-control" name="foto" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer border-0 bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-save-fill me-1"></i> Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </li>
            @empty
            <li class="list-group-item p-4 text-center text-muted bg-light">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                <small>No hay racks configurados en este almacén</small>
            </li>
            @endforelse
        </ul>
    </div>
</div>