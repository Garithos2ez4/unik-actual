<div class="row border shadow rounded-3 pt-2 mb-4 mt-4">
    <div class="col-md-12 pb-2">
        <div class="accordion accordion-flush" id="accordionMainMappers">
            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="flush-headingMainMappers">
                    <button class="accordion-button collapsed fs-5 fw-bold flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseMainMappers" aria-expanded="false" aria-controls="flush-collapseMainMappers">
                        <i class="bi bi-file-earmark-excel text-success me-2"></i> Mappers de Plataforma (Plantillas Excel)
                    </button>
                    <button class="btn btn-primary ms-2 me-3 my-2" style="z-index: 10;" data-bs-toggle="modal" data-bs-target="#mapperModal">
                        <i class="bi bi-plus-lg"></i> Nuevo
                    </button>
                </h2>
                <div id="flush-collapseMainMappers" class="accordion-collapse collapse" aria-labelledby="flush-headingMainMappers" data-bs-parent="#accordionMainMappers">
                    <div class="accordion-body">
                        <p class="text-secondary mb-3">Configura qué categorías o grupos tienen acceso a descargar plantillas Excel (mappers) para cada plataforma.</p>
                        <div class="col-md-12" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-hover text-center align-middle">
                                <thead class="bg-sistema-uno text-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th>Plataforma</th>
                                        <th>Categoría</th>
                                        <th>Grupo Producto</th>
                                        <th>Tipo Template</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($mappers as $mapper)
                                    <tr>
                                        <td class="fw-bold">{{ optional($mapper->plataforma)->nombrePlataforma ?? 'N/A' }}</td>
                                        <td>
                                            @if($mapper->idCategoria)
                                            <span class="badge bg-info text-dark">{{ optional($mapper->categoria)->nombreCategoria ?? 'ID: '.$mapper->idCategoria }}</span>
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>
                                            @if($mapper->idGrupoProducto)
                                            <span class="badge bg-secondary">{{ optional($mapper->grupoProducto)->nombreGrupo ?? 'ID: '.$mapper->idGrupoProducto }}</span>
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>
                                            @if($mapper->tipo_template == 'express')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-lightning-fill"></i> Express</span>
                                            @else
                                            <span class="badge bg-success"><i class="bi bi-file-earmark-excel-fill"></i> Completo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('delete_mapper', $mapper->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('¿Eliminar este mapper?')) this.form.submit();" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-secondary">No hay mappers configurados.</td>
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


<!-- Modal para Nuevo Mapper -->
<div class="modal fade" id="mapperModal" tabindex="-1" aria-labelledby="mapperModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('insert_mapper') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapperModalLabel"><i class="bi bi-plus-circle"></i> Nuevo Mapper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Plataforma</label>
                        <select name="idPlataforma" class="form-select" required>
                            <option value="">Selecciona una plataforma</option>
                            @foreach($plataformas as $p)
                            <option value="{{ $p->idPlataforma }}">{{ $p->nombrePlataforma }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-primary">Aplica a Categoría (Opcional)</label>
                        <select name="idCategoria" class="form-select">
                            <option value="">-- Ninguna --</option>
                            @foreach($categorias as $c)
                            <option value="{{ $c->idCategoria }}">{{ $c->nombreCategoria }}</option>
                            @endforeach
                        </select>
                        <small class="text-secondary">Si seleccionas categoría, aplica a TODOS los productos de la categoría.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-success">O aplica a Grupo Específico (Opcional)</label>
                        <select name="idGrupoProducto" class="form-select">
                            <option value="">-- Ninguno --</option>
                            @foreach($categorias as $c)
                            <optgroup label="{{ $c->nombreCategoria }}">
                                @foreach($c->GrupoProducto as $g)
                                <option value="{{ $g->idGrupoProducto }}">{{ $g->nombreGrupo }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <small class="text-secondary">Si seleccionas grupo, solo aplica a los productos de ese grupo.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Plantilla (Template)</label>
                        <select name="tipo_template" class="form-select" required>
                            <option value="express">Express</option>
                            <option value="completo">Completo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-primary">Clase PHP del Mapper (Opcional pero Recomendado)</label>
                        <input type="text" name="mapper_class" class="form-control" placeholder="\App\Services\Falabella\Mappers\NuevoProductoMapper::class">
                        <small class="text-secondary">Ej: \App\Services\Falabella\Mappers\LaptopMapper::class</small>
                    </div>
                    <div class="alert alert-warning py-2 mb-0" style="font-size: 0.9em;">
                        <strong>Nota:</strong> Debes seleccionar al menos una Categoría o un Grupo. Si seleccionas ambos, el mapper podría duplicarse o generar conflictos lógicos.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-floppy"></i> Guardar Mapper</button>
                </div>
            </div>
        </form>
    </div>
</div>