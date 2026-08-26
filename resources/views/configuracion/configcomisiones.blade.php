@extends('layouts.app')

@section('title', 'Configuración Comisiones')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-md-12">
            <h2><i class="bi bi-gear-fill"></i> Configuración</h2>
        </div>
    </div>
    <br>
    <div class="col-md-12">
        @include('components.nav_config', ['pag' => 'comisiones'])
    </div>
    <br>
    
    <div class="row">
        <div class="col-md-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Tabs Navs -->
            <ul class="nav nav-tabs mb-4" id="comisionesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="comisiones-tab" data-bs-toggle="tab" data-bs-target="#comisiones-content" type="button" role="tab" aria-controls="comisiones-content" aria-selected="true">
                        <i class="bi bi-percent"></i> Reglas de Comisiones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="tarifas-tab" data-bs-toggle="tab" data-bs-target="#tarifas-content" type="button" role="tab" aria-controls="tarifas-content" aria-selected="false">
                        <i class="bi bi-truck"></i> Tarifas de Envío (Peso)
                    </button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content" id="comisionesTabsContent">
                
                <!-- TAB 1: COMISIONES -->
                <div class="tab-pane fade show active" id="comisiones-content" role="tabpanel" aria-labelledby="comisiones-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Comisiones por Venta</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaRegla">
                            <i class="bi bi-plus-circle"></i> Nueva Regla
                        </button>
                    </div>

                    <div class="card shadow-sm border-primary border-top border-3">
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-hover table-bordered mb-0 text-center align-middle" style="font-size: 0.9rem;">
                                <thead class="bg-sistema-uno text-white">
                                    <tr>
                                        <th>Plataforma</th>
                                        <th>Nombre</th>
                                        <th>Condición</th>
                                        <th>Valor</th>
                                        <th>Comisión %</th>
                                        <th>Monto Fijo</th>
                                        <th>Vigencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reglas as $regla)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $regla->plataforma }}</span></td>
                                        <td>{{ $regla->nombre_regla }}</td>
                                        <td>{{ $regla->tipo_condicion }}</td>
                                        <td>
                                            @if(in_array($regla->tipo_condicion, ['GRUPO_IN', 'CATEGORIA_IN']))
                                                <span title="IDs: {{ $regla->valor_condicion }}">
                                                    {{ \Illuminate\Support\Str::limit($regla->valor_condicion_nombres, 40) }}
                                                </span>
                                            @else
                                                {{ $regla->valor_condicion ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ $regla->porcentaje_comision ? ($regla->porcentaje_comision * 100).'%' : '-' }}</td>
                                        <td>{{ $regla->monto_fijo ? 'S/ '.$regla->monto_fijo : '-' }}</td>
                                        <td>
                                            <small class="text-muted">
                                                Desde: {{ $regla->fecha_inicio ? \Carbon\Carbon::parse($regla->fecha_inicio)->format('d/m/Y') : 'Siempre' }}<br>
                                                Hasta: {{ $regla->fecha_fin ? \Carbon\Carbon::parse($regla->fecha_fin)->format('d/m/Y') : 'Siempre' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($regla->estado)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarRegla{{ $regla->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('comisiones.destroy', $regla->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta regla?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal Editar Regla -->
                                    <div class="modal fade" id="modalEditarRegla{{ $regla->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Editar Regla: {{ $regla->nombre_regla }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('comisiones.update', $regla->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label">Plataforma</label>
                                                            <select class="form-select" name="plataforma" required>
                                                                <option value="FALABELLA" {{ $regla->plataforma == 'FALABELLA' ? 'selected' : '' }}>FALABELLA</option>
                                                                <option value="RIPLEY" {{ $regla->plataforma == 'RIPLEY' ? 'selected' : '' }}>RIPLEY</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Nombre de Regla</label>
                                                            <input type="text" class="form-control" name="nombre_regla" value="{{ $regla->nombre_regla }}" required>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                            <label class="form-label">Condición</label>
                                                                <select class="form-select select-tipo-condicion" name="tipo_condicion" required>
                                                                    <option value="DEFAULT" {{ $regla->tipo_condicion == 'DEFAULT' ? 'selected' : '' }}>Por Defecto</option>
                                                                    <option value="PRECIO_MENOR_IGUAL" {{ $regla->tipo_condicion == 'PRECIO_MENOR_IGUAL' ? 'selected' : '' }}>Precio <= X</option>
                                                                    <option value="CATEGORIA_IN" {{ $regla->tipo_condicion == 'CATEGORIA_IN' ? 'selected' : '' }}>Categoría en (IDs)</option>
                                                                    <option value="GRUPO_IN" {{ $regla->tipo_condicion == 'GRUPO_IN' ? 'selected' : '' }}>Grupo en (Nombres)</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Valor de la Condición</label>
                                                                @php
                                                                    $isGrupo = ($regla->tipo_condicion == 'GRUPO_IN');
                                                                    $isCat = ($regla->tipo_condicion == 'CATEGORIA_IN');
                                                                    $valoresArray = ($isGrupo || $isCat) ? explode(',', $regla->valor_condicion) : [];
                                                                @endphp
                                                                <div class="valor-condicion-text-wrapper {{ ($isGrupo || $isCat) ? 'd-none' : '' }}">
                                                                    <input type="text" class="form-control" name="valor_condicion" value="{{ $regla->valor_condicion }}" placeholder="Ej: 39.00 o 1,3" {{ ($isGrupo || $isCat) ? 'disabled' : '' }}>
                                                                </div>
                                                                <div class="valor-condicion-select-cat-wrapper {{ $isCat ? '' : 'd-none' }}">
                                                                    <select multiple class="form-control tom-select-cats" name="valor_condicion[]" {{ $isCat ? '' : 'disabled' }}>
                                                                        <option value="">Buscar categorías...</option>
                                                                        @foreach($categorias as $cat)
                                                                            <option value="{{ $cat->idCategoria }}" {{ in_array($cat->idCategoria, $valoresArray) ? 'selected' : '' }}>{{ $cat->nombreCategoria }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="valor-condicion-select-grp-wrapper {{ $isGrupo ? '' : 'd-none' }}">
                                                                    <select multiple class="form-control tom-select-grupos" name="valor_condicion[]" {{ $isGrupo ? '' : 'disabled' }}>
                                                                        <option value="">Buscar grupos...</option>
                                                                        @foreach($grupos as $grp)
                                                                            <option value="{{ $grp->nombreGrupo }}" {{ in_array($grp->nombreGrupo, $valoresArray) ? 'selected' : '' }}>{{ $grp->nombreGrupo }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Comisión Porcentaje</label>
                                                                <input type="number" step="0.0001" class="form-control" name="porcentaje_comision" value="{{ $regla->porcentaje_comision }}" placeholder="Ej: 0.12 (Para 12%)">
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Monto Fijo</label>
                                                                <input type="number" step="0.01" class="form-control" name="monto_fijo" value="{{ $regla->monto_fijo }}" placeholder="Ej: 10.90">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Fecha Inicio Vigencia</label>
                                                                <input type="date" class="form-control" name="fecha_inicio" value="{{ $regla->fecha_inicio }}">
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Fecha Fin Vigencia</label>
                                                                <input type="date" class="form-control" name="fecha_fin" value="{{ $regla->fecha_fin }}">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Prioridad (Mayor primero)</label>
                                                                <input type="number" class="form-control" name="prioridad" value="{{ $regla->prioridad }}" required>
                                                            </div>
                                                            <div class="col-6 mb-3 pt-4">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" name="estado" id="estado{{$regla->id}}" {{ $regla->estado ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="estado{{$regla->id}}">Activo</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <tr>
                                        <td colspan="9">No hay reglas de comisiones registradas.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: TARIFAS DE ENVIO -->
                <div class="tab-pane fade" id="tarifas-content" role="tabpanel" aria-labelledby="tarifas-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Tarifas de Envío Logístico (Por Peso)</h4>
                        <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalNuevaTarifa">
                            <i class="bi bi-plus-circle"></i> Nueva Tarifa
                        </button>
                    </div>

                    <div class="card shadow-sm border-info border-top border-3">
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-hover table-bordered mb-0 text-center align-middle" style="font-size: 0.9rem;">
                                <thead class="bg-sistema-uno text-white">
                                    <tr>
                                        <th>Plataforma</th>
                                        <th>Peso Máximo (KG)</th>
                                        <th>Costo Fijo</th>
                                        <th>Vigencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tarifas as $tarifa)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $tarifa->plataforma }}</span></td>
                                        <td>
                                            @if($tarifa->peso_maximo)
                                                <= {{ $tarifa->peso_maximo }} Kg
                                            @else
                                                <span class="text-muted fst-italic">Demás pesos (Por defecto)</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">S/ {{ number_format($tarifa->monto_fijo, 2) }}</td>
                                        <td>
                                            <small class="text-muted">
                                                Desde: {{ $tarifa->fecha_inicio ? \Carbon\Carbon::parse($tarifa->fecha_inicio)->format('d/m/Y') : 'Siempre' }}<br>
                                                Hasta: {{ $tarifa->fecha_fin ? \Carbon\Carbon::parse($tarifa->fecha_fin)->format('d/m/Y') : 'Siempre' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($tarifa->estado)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarTarifa{{ $tarifa->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('tarifas-envio.destroy', $tarifa->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta tarifa?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal Editar Tarifa -->
                                    <div class="modal fade" id="modalEditarTarifa{{ $tarifa->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title">Editar Tarifa de Envío</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('tarifas-envio.update', $tarifa->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label">Plataforma</label>
                                                            <select class="form-select" name="plataforma" required>
                                                                <option value="FALABELLA" {{ $tarifa->plataforma == 'FALABELLA' ? 'selected' : '' }}>FALABELLA</option>
                                                                <option value="RIPLEY" {{ $tarifa->plataforma == 'RIPLEY' ? 'selected' : '' }}>RIPLEY</option>
                                                            </select>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Peso Máximo (Kg)</label>
                                                                <input type="number" step="0.01" class="form-control" name="peso_maximo" value="{{ $tarifa->peso_maximo }}" placeholder="Dejar vacío para 'Demás pesos'">
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Costo (S/)</label>
                                                                <input type="number" step="0.01" class="form-control" name="monto_fijo" value="{{ $tarifa->monto_fijo }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Fecha Inicio Vigencia</label>
                                                                <input type="date" class="form-control" name="fecha_inicio" value="{{ $tarifa->fecha_inicio }}">
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label">Fecha Fin Vigencia</label>
                                                                <input type="date" class="form-control" name="fecha_fin" value="{{ $tarifa->fecha_fin }}">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3 pt-2">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" name="estado" id="estadoTarifa{{$tarifa->id}}" {{ $tarifa->estado ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="estadoTarifa{{$tarifa->id}}">Activo</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <tr>
                                        <td colspan="6">No hay tarifas logísticas registradas.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- End Tab Content -->
        </div>
    </div>
</div>

<!-- Modal Nueva Regla de Comision -->
<div class="modal fade" id="modalNuevaRegla" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Nueva Regla de Comisión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('comisiones.store') }}" method="POST">
                @csrf
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label">Plataforma</label>
                        <select class="form-select" name="plataforma" required>
                            <option value="FALABELLA">FALABELLA</option>
                            <option value="RIPLEY">RIPLEY</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de Regla</label>
                        <input type="text" class="form-control" name="nombre_regla" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Condición</label>
                            <select class="form-select select-tipo-condicion" name="tipo_condicion" required>
                                <option value="DEFAULT">Por Defecto</option>
                                <option value="PRECIO_MENOR_IGUAL">Precio <= X</option>
                                <option value="CATEGORIA_IN">Categoría en (IDs)</option>
                                <option value="GRUPO_IN">Grupo en (Nombres)</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Valor de la Condición</label>
                            <div class="valor-condicion-text-wrapper">
                                <input type="text" class="form-control" name="valor_condicion" placeholder="Ej: 39.00 o 1,3">
                            </div>
                            <div class="valor-condicion-select-cat-wrapper d-none">
                                <select multiple class="form-control tom-select-cats" name="valor_condicion[]" disabled>
                                    <option value="">Buscar categorías...</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->idCategoria }}">{{ $cat->nombreCategoria }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="valor-condicion-select-grp-wrapper d-none">
                                <select multiple class="form-control tom-select-grupos" name="valor_condicion[]" disabled>
                                    <option value="">Buscar grupos...</option>
                                    @foreach($grupos as $grp)
                                        <option value="{{ $grp->nombreGrupo }}">{{ $grp->nombreGrupo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Comisión Porcentaje</label>
                            <input type="number" step="0.0001" class="form-control" name="porcentaje_comision" placeholder="Ej: 0.12 (Para 12%)">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Monto Fijo</label>
                            <input type="number" step="0.01" class="form-control" name="monto_fijo" placeholder="Ej: 10.90">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Fecha Inicio Vigencia</label>
                            <input type="date" class="form-control" name="fecha_inicio">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Fecha Fin Vigencia</label>
                            <input type="date" class="form-control" name="fecha_fin">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Prioridad</label>
                            <input type="number" class="form-control" name="prioridad" value="10" required>
                        </div>
                        <div class="col-6 mb-3 pt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="estado" id="nuevo_estado" checked>
                                <label class="form-check-label" for="nuevo_estado">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Regla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nueva Tarifa de Envio -->
<div class="modal fade" id="modalNuevaTarifa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Nueva Tarifa de Envío</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tarifas-envio.store') }}" method="POST">
                @csrf
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label">Plataforma</label>
                        <select class="form-select" name="plataforma" required>
                            <option value="FALABELLA">FALABELLA</option>
                            <option value="RIPLEY">RIPLEY</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Peso Máximo (Kg)</label>
                            <input type="number" step="0.01" class="form-control" name="peso_maximo" placeholder="Ej: 0.50 (O dejar vacío)">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Costo (S/)</label>
                            <input type="number" step="0.01" class="form-control" name="monto_fijo" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Fecha Inicio Vigencia</label>
                            <input type="date" class="form-control" name="fecha_inicio">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Fecha Fin Vigencia</label>
                            <input type="date" class="form-control" name="fecha_fin">
                        </div>
                    </div>
                    <div class="mb-3 pt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="estado" id="nuevo_estado_tarifa" checked>
                            <label class="form-check-label" for="nuevo_estado_tarifa">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white">Guardar Tarifa</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Tom Select en todos los selects que lo requieran
    document.querySelectorAll('.tom-select-grupos, .tom-select-cats').forEach(function(el) {
        new TomSelect(el, {
            plugins: ['remove_button'],
            placeholder: 'Buscar...',
            maxOptions: 100
        });
    });

    // Manejar el cambio de Condición en cualquier formulario (Nuevo o Edición)
    document.querySelectorAll('.select-tipo-condicion').forEach(function(selectEl) {
        selectEl.addEventListener('change', function(e) {
            const form = this.closest('form');
            const tipo = this.value;
            const isGrupo = (tipo === 'GRUPO_IN');
            const isCat = (tipo === 'CATEGORIA_IN');
            
            const textWrapper = form.querySelector('.valor-condicion-text-wrapper');
            const catWrapper = form.querySelector('.valor-condicion-select-cat-wrapper');
            const grpWrapper = form.querySelector('.valor-condicion-select-grp-wrapper');
            
            const textInput = form.querySelector('input[name="valor_condicion"]');
            const catSelect = catWrapper ? catWrapper.querySelector('select') : null;
            const grpSelect = grpWrapper ? grpWrapper.querySelector('select') : null;

            // Ocultar todos
            if(textWrapper) textWrapper.classList.add('d-none');
            if(catWrapper) catWrapper.classList.add('d-none');
            if(grpWrapper) grpWrapper.classList.add('d-none');
            
            // Deshabilitar todos
            if(textInput) textInput.disabled = true;
            if(catSelect && catSelect.tomselect) catSelect.tomselect.disable();
            if(grpSelect && grpSelect.tomselect) grpSelect.tomselect.disable();

            // Activar solo el seleccionado
            if (isGrupo) {
                if(grpWrapper) grpWrapper.classList.remove('d-none');
                if(grpSelect && grpSelect.tomselect) grpSelect.tomselect.enable();
            } else if (isCat) {
                if(catWrapper) catWrapper.classList.remove('d-none');
                if(catSelect && catSelect.tomselect) catSelect.tomselect.enable();
            } else {
                if(textWrapper) textWrapper.classList.remove('d-none');
                if(textInput) textInput.disabled = false;
            }
        });
    });
});
</script>
@endpush
@endsection
