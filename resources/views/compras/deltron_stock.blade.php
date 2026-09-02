@extends('layouts.app')

@section('title', 'Oportunidades Deltron')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 col-md-8">
            <h2 class="mb-1 text-primary">
                <i class="bi bi-robot"></i> Catálogo Deltron (En vivo)
            </h2>
            <div class="text-muted mb-3">Consulta los productos con mayor stock disponibles ahora mismo en Deltron.
                <button type="button" class="btn btn-sm btn-outline-info ms-2" data-bs-toggle="modal" data-bs-target="#historialModal">
                    <i class="bi bi-clock-history"></i> Última Actualización: {{ $historial->first() ? $historial->first()->fecha_ejecucion->format('d/m h:i A') : 'Ninguna' }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#recomendadosModal">
                    <i class="bi bi-fire"></i> Recomendados ({{ $historial->sum(function($log) { return is_array($log->recomendados_json) ? count($log->recomendados_json) : 0; }) }})
                </button>
                <form action="{{ route('compras.deltron.sync') }}" method="POST" class="d-inline-block ms-1" onsubmit="document.getElementById('syncSpinner').style.display='inline-block'; this.querySelector('button').disabled=true;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-arrow-repeat"></i> Sincronizar Ahora
                        <span id="syncSpinner" style="display: none;" class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <!-- Formulario de Búsqueda Rápida -->
            <form action="{{ route('compras.deltron') }}" method="GET" class="d-flex align-items-center justify-content-md-end" id="formBuscador">
                <div class="input-group" style="max-width: 300px;">
                    <input type="text" name="q" class="form-control" placeholder="Buscar en Deltron..." value="{{ $keyword }}" list="deltronCategories" required>
                    <datalist id="deltronCategories">
                        <option value="LAPTOP">
                        <option value="MONITOR">
                        <option value="IMPRESORA">
                        <option value="TECLADO">
                    </datalist>
                    <button class="btn btn-primary" type="submit" onclick="document.getElementById('spinnerLoader').style.display='inline-block';">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
            <div id="spinnerLoader" style="display: none;" class="mt-2 text-primary">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Consultando Deltron...
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="45%">Producto</th>
                            <th width="20%">Código Deltron (Modelo)</th>
                            <th width="10%">Part Number</th>
                            <th width="10%" class="text-center text-danger">Precio (US$)</th>
                            <th width="10%" class="text-center">Stock Disp.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $index => $prod)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-bold">{{ $prod['titulo'] }}</span>
                                @if($prod['mini_codigo'])
                                <br><small class="text-muted">Mini Código: {{ $prod['mini_codigo'] }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $prod['modelo'] }}</span></td>
                            <td>{{ $prod['part_number'] ?? '-' }}</td>
                            <td class="text-center text-danger fw-bold">
                                @if(isset($prod['precio']) && $prod['precio'])
                                ${{ number_format($prod['precio'], 2) }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-success fs-6">
                                    <i class="bi bi-box-seam"></i> {{ $prod['stock'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-info-circle fs-4 d-block mb-2"></i>
                                No se encontraron productos en stock para "<strong>{{ $keyword }}</strong>".
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                @if($page > 1)
                <a href="{{ route('compras.deltron', ['q' => $keyword, 'page' => $page - 1]) }}" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('spinnerLoader').style.display='inline-block';">
                    <i class="bi bi-chevron-left"></i> Anterior
                </a>
                @endif
            </div>
            <div class="text-muted text-center">
                <small>Página {{ $page }} • Resultados en tiempo real.</small>
            </div>
            <div>
                @if(count($productos) > 0)
                <a href="{{ route('compras.deltron', ['q' => $keyword, 'page' => $page + 1]) }}" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('spinnerLoader').style.display='inline-block';">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div class="modal fade" id="historialModal" tabindex="-1" aria-labelledby="historialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="historialModalLabel"><i class="bi bi-clock-history"></i> Historial de Actualizaciones (Stock Proveedor)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Categoría Buscada</th>
                            <th class="text-center">Encontrados</th>
                            <th class="text-center">Actualizados en BD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $log)
                        <tr>
                            <td>{{ $log->fecha_ejecucion->format('d/m/Y h:i A') }}</td>
                            <td><span class="badge bg-secondary">{{ $log->keyword }}</span></td>
                            <td class="text-center">{{ $log->productos_encontrados }}</td>
                            <td class="text-center text-success fw-bold">
                                +{{ $log->productos_actualizados }}
                                @if($log->productos_actualizados > 0 && !empty($log->detalles_json))
                                <button class="btn btn-sm btn-link text-decoration-none py-0 ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#detalles-{{ $log->idDetalle }}">
                                    (Ver)
                                </button>
                                @endif
                            </td>
                        </tr>
                        @if($log->productos_actualizados > 0 && !empty($log->detalles_json))
                        <tr class="collapse bg-light" id="detalles-{{ $log->idDetalle }}">
                            <td colspan="4" class="p-3">
                                <h6 class="mb-2 text-primary" style="font-size: 0.9rem;">Productos Actualizados:</h6>
                                <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                                    @foreach($log->detalles_json as $detalle)
                                    <li>
                                        <i class="bi bi-check2-circle text-success"></i>
                                        <strong>PN:</strong> {{ $detalle['part_number'] }}
                                    </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No hay registros de actualizaciones aún. Ejecuta el comando o espera a la madrugada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Recomendados -->
<div class="modal fade" id="recomendadosModal" tabindex="-1" aria-labelledby="recomendadosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="recomendadosModalLabel"><i class="bi bi-fire"></i> Productos Recomendados para Crear</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-warning m-3 py-2 border-0 shadow-sm">
                    <i class="bi bi-info-circle-fill me-1"></i> Estos productos fueron detectados con más de 100 unidades en Deltron, pero <strong>no existen en tu catálogo</strong>. Recomendamos crearlos para aprovechar el inventario.
                </div>
                <table class="table table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-4">Fecha Reporte</th>
                            <th>Producto (Deltron)</th>
                            <th>Part Number</th>
                            <th>Modelo</th>
                            <th class="text-center pe-4">Stock Encontrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasRecomendados = false; @endphp
                        @foreach($historial as $log)
                        @if(!empty($log->recomendados_json))
                        @php
                        $hasRecomendados = true;
                        $recomendadosAsc = collect($log->recomendados_json)->sortByDesc('stock')->all();
                        @endphp
                        @foreach($recomendadosAsc as $rec)
                        <tr>
                            <td class="ps-4 text-muted small">{{ $log->fecha_ejecucion->format('d/m/Y h:i A') }}</td>
                            <td>
                                <span class="fw-bold text-dark d-block" style="max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $rec['titulo'] }}">
                                    {{ $rec['titulo'] }}
                                </span>
                            </td>
                            <td><span class="fw-bold text-primary">{{ $rec['part_number'] }}</span></td>
                            <td><span class="badge bg-secondary">{{ $rec['modelo'] }}</span></td>
                            <td class="text-center pe-4">
                                <span class="badge bg-success rounded-pill px-3">{{ $rec['stock'] }} uds.</span>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                        @endforeach

                        @if(!$hasRecomendados)
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-check-circle fs-1 d-block mb-3 opacity-25 text-success"></i>
                                ¡Felicidades! Todo el catálogo de alto inventario en Deltron ya existe en tu tienda.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection