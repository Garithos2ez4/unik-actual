@extends('layouts.app')

@section('title', 'Catálogo Deltron API B2B')

@section('content')
@include('compras.styles.styles')

<div class="container-fluid py-4 px-4">
    <!-- Header Principal -->
    <div class="row align-items-center mb-3">
        <div class="col-12 col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h2 class="mb-0 text-primary fw-bold">
                    <i class="bi bi-cloud-check-fill text-primary"></i> Catálogo Deltron (API Oficial B2B)
                </h2>
                <span class="badge bg-success-subtle text-success border border-success px-2 py-1 rounded-pill small">
                    <i class="bi bi-check-circle-fill me-1"></i> Producción v1.0
                </span>
            </div>
            <p class="text-muted mb-2">
                Inventario de Deltron almacenado en la base de datos (<strong class="font-monospace text-primary">Inventario_Proveedor</strong>). Búsquedas y consultas locales instantáneas sin saturar la API de Deltron.
            </p>

            <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                <!-- Modal Historial -->
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#historialModal">
                    <i class="bi bi-clock-history me-1"></i> Última Sincronización: 
                    <span class="fw-semibold">{{ $historial->first() ? $historial->first()->fecha_ejecucion->format('d/m h:i A') : 'Ninguna' }}</span>
                </button>

                <!-- Modal Promociones y Recomendados -->
                <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 shadow-xs fw-semibold" data-bs-toggle="modal" data-bs-target="#recomendadosModal" onclick="if(document.getElementById('tab-promos-link')) document.getElementById('tab-promos-link').click();" title="Ver promociones y oportunidades oficiales de Deltron">
                    <i class="bi bi-tag-fill text-warning me-1"></i> Promociones B2B 
                    <span class="badge bg-warning text-dark ms-1">{{ count($promociones) }}</span>
                </button>

                <!-- Modal Recomendados -->
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-xs fw-semibold" data-bs-toggle="modal" data-bs-target="#recomendadosModal" title="Ver productos recomendados para crear en catálogo">
                    <i class="bi bi-fire me-1"></i> Recomendados Catálogo ({{ count($promociones) }})
                </button>

                <!-- Modal Ficha por SKU Deltron (API-0000027) -->
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs fw-semibold" onclick="abrirModalConsultaSkuPuntual()" title="Consultar o refrescar la ficha técnica puntual de cualquier producto por SKU Deltron (API-0000027)">
                    <i class="bi bi-file-earmark-text text-primary me-1"></i> Ficha por SKU (API-0000027)
                </button>

                <!-- IP Whitelist Badge -->
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2 shadow-xs" title="Dirección IP autorizada en Deltron">
                    <i class="bi bi-hdd-network text-primary me-1"></i> IP: <span class="fw-bold font-monospace">{{ $clientIp }}</span>
                </span>

                <!-- Sincronización oficial Deltron B2B -->
                <div class="btn-group shadow-xs">
                    <form action="{{ route('compras.deltron.sync') }}" method="POST" class="d-inline" id="formSyncInicioDia" onsubmit="confirmarSyncDeltron(event, 'inicio-dia', this)">
                        @csrf
                        <input type="hidden" name="tipo" value="inicio-dia">
                        <button type="submit" id="btnSyncMain" class="btn btn-sm btn-success rounded-start-pill px-3 fw-semibold" title="Descarga corte diario de Deltron (precios y stock matutino)">
                            <i class="bi bi-arrow-repeat me-1"></i> Sincronizar Inicio de Día
                            <span id="syncSpinner" style="display: none;" class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split rounded-end-pill pe-2" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones de sincronización Deltron">
                        <span class="visually-hidden">Opciones de sincronización</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="min-width: 280px;">
                        <li>
                            <form action="{{ route('compras.deltron.sync') }}" method="POST" id="formSyncCatalogo" onsubmit="confirmarSyncDeltron(event, 'catalogo', this)">
                                @csrf
                                <input type="hidden" name="tipo" value="catalogo">
                                <button type="submit" class="dropdown-item py-2">
                                    <div class="fw-semibold text-primary"><i class="bi bi-cloud-arrow-down-fill me-2"></i>Descargar Catálogo Completo</div>
                                    <small class="text-muted d-block ps-4">SKUs oficiales, Mini Códigos, tránsito, specs y stock (timeout 300s)</small>
                                    <span id="catSpinner" style="display: none;" class="spinner-border spinner-border-sm ms-4 mt-1 text-primary" role="status" aria-hidden="true"></span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Buscador Superior -->
        <div class="col-12 col-lg-4 mt-3 mt-lg-0 text-lg-end">
            <form action="{{ route('compras.deltron') }}" method="GET" class="d-flex align-items-center justify-content-lg-end" id="formBuscador">
                <div class="input-group shadow-sm" style="max-width: 360px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Buscar en BD local por PN o modelo..." value="{{ $keyword }}" list="deltronCategories">
                    <datalist id="deltronCategories">
                        <option value="LAPTOP">
                        <option value="MONITOR">
                        <option value="IMPRESORA">
                        <option value="TINTAS">
                        <option value="TECLADO">
                        <option value="MOUSE">
                        <option value="DISCO SSD">
                    </datalist>
                    @if(!empty($keyword))
                    <a href="{{ route('compras.deltron') }}" class="btn btn-outline-secondary" title="Limpiar búsqueda"><i class="bi bi-x-lg"></i></a>
                    @endif
                    <button class="btn btn-primary px-3" type="submit" onclick="document.getElementById('spinnerLoader').style.display='block';">
                        Buscar
                    </button>
                </div>
            </form>
            <div id="spinnerLoader" style="display: none;" class="mt-2 text-primary small">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Filtrando inventario local...
            </div>
        </div>
    </div>

    <!-- Alertas de estado -->
    @if(isset($alertaApi) && $alertaApi)
    <div class="alert alert-warning alert-dismissible fade show py-2 mb-3 shadow-sm border-0" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-exclamation fs-3 me-3 text-warning"></i>
            <div>
                <strong>Aviso de Deltron API:</strong> {{ $alertaApi }}
                @if(str_contains($alertaApi, 'IP no registrada') || str_contains($alertaApi, '403'))
                <div class="mt-1 small">
                    <i class="bi bi-info-circle"></i> Por favor agrega tu dirección IP <strong><code>{{ $clientIp }}</code></strong> a la lista blanca (Whitelist) en el portal de Deltron.
                </div>
                @endif
            </div>
        </div>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 mb-3 shadow-sm border-0" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 mb-3 shadow-sm border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card deltron-kpi-card shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                        <i class="bi bi-boxes fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Catálogo en Stock</div>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalStockProveedor) }}</h4>
                        <small class="text-muted">ítems en almacén local</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card deltron-kpi-card shadow-sm h-100 bg-white" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#recomendadosModal" onclick="if(document.getElementById('tab-promos-link')) document.getElementById('tab-promos-link').click();">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                        <i class="bi bi-lightning-charge-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Promociones Activas</div>
                        <h4 class="mb-0 fw-bold text-dark">{{ count($promociones) }}</h4>
                        <small class="text-warning fw-semibold"><i class="bi bi-eye"></i> Ver en Recomendados</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card deltron-kpi-card shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle {{ $alertaApi ? 'bg-warning bg-opacity-10 text-warning' : 'bg-success bg-opacity-10 text-success' }} p-3 me-3">
                        <i class="bi {{ $alertaApi ? 'bi-shield-exclamation' : 'bi-shield-check' }} fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Conexión Deltron API</div>
                        <h5 class="mb-0 fw-bold {{ $alertaApi ? 'text-warning' : 'text-success' }}">{{ $alertaApi ? 'Pendiente IP' : 'Activa (B2B)' }}</h5>
                        <small class="text-muted font-monospace">IP: {{ $clientIp }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card deltron-kpi-card shadow-sm h-100 bg-white" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#historialModal">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Sincronización Diaria</div>
                        <h5 class="mb-0 fw-bold text-dark">
                            {{ $historial->first() ? $historial->first()->fecha_ejecucion->format('H:i') . ' hrs' : '06:00 AM' }}
                        </h5>
                        <small class="text-muted">{{ $historial->first() ? $historial->first()->fecha_ejecucion->diffForHumans() : 'Programado diario' }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Rápidos por Categoría -->
    <div class="d-flex align-items-center gap-2 overflow-auto pb-2 mb-3">
        <span class="text-muted small fw-bold text-nowrap"><i class="bi bi-funnel"></i> Filtros rápidos:</span>
        <a href="{{ route('compras.deltron') }}" class="btn btn-sm rounded-pill {{ empty($keyword) ? 'btn-primary' : 'btn-outline-secondary' }}">
            Todos
        </a>
        <a href="{{ route('compras.deltron', ['q' => 'LAPTOP']) }}" class="btn btn-sm rounded-pill {{ $keyword === 'LAPTOP' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-laptop"></i> Laptops
        </a>
        <a href="{{ route('compras.deltron', ['q' => 'MONITOR']) }}" class="btn btn-sm rounded-pill {{ $keyword === 'MONITOR' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-display"></i> Monitores
        </a>
        <a href="{{ route('compras.deltron', ['q' => 'IMPRESORA']) }}" class="btn btn-sm rounded-pill {{ $keyword === 'IMPRESORA' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-printer"></i> Impresoras
        </a>
        <a href="{{ route('compras.deltron', ['q' => 'TINTA']) }}" class="btn btn-sm rounded-pill {{ $keyword === 'TINTA' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-droplet"></i> Tintas
        </a>
        <a href="{{ route('compras.deltron', ['q' => 'MOUSE']) }}" class="btn btn-sm rounded-pill {{ $keyword === 'MOUSE' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-mouse"></i> Periféricos
        </a>
        <a href="{{ route('compras.deltron', ['q' => 'TECLADO']) }}" class="btn btn-sm rounded-pill {{ $keyword === 'TECLADO' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-keyboard"></i> Teclados
        </a>
    </div>

    <!-- Tabla de Productos -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0 table-deltron">
                    <thead class="table-light">
                        <tr class="text-secondary small text-uppercase">
                            <th width="3%" class="ps-3">#</th>
                            <th width="37%">Producto</th>
                            <th width="13%">SKU Deltron</th>
                            <th width="14%">Part Number</th>
                            <th width="11%" class="text-center">Precio B2B (US$)</th>
                            <th width="9%" class="text-center">Stock Disp.</th>
                            <th width="13%" class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $index => $prod)
                        <tr>
                            <td class="ps-3 text-muted small">{{ ($page - 1) * 30 + $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark mb-0">{{ $prod['titulo'] }}</div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                    @if(!empty($prod['codigo_unike']))
                                    <small class="text-muted d-inline-flex align-items-center gap-1">
                                        <span>Cód. Unike:</span>
                                        <span class="badge bg-light text-secondary border font-monospace">{{ $prod['codigo_unike'] }}</span>
                                    </small>
                                    @endif
                                    @if(!empty($prod['modelo']) && $prod['modelo'] !== '-')
                                    <small class="text-muted d-inline-flex align-items-center gap-1">
                                        <span>Modelo:</span>
                                        <span class="badge bg-light text-secondary border font-monospace">{{ $prod['modelo'] }}</span>
                                    </small>
                                    @endif
                                    @if(!empty($prod['mini_codigo_deltron']))
                                    <small class="text-muted d-inline-flex align-items-center gap-1">
                                        <span>Mini Código Deltron:</span>
                                        <span class="badge bg-secondary-subtle text-dark border font-monospace fw-bold">{{ $prod['mini_codigo_deltron'] }}</span>
                                    </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if(!empty($prod['sku_deltron']))
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-primary font-monospace">{{ $prod['sku_deltron'] }}</span>
                                    <i class="bi bi-clipboard btn-copy-mini" title="Copiar SKU Deltron" onclick="copyToClipboard('{{ $prod['sku_deltron'] }}', this)"></i>
                                </div>
                                @else
                                <span class="badge bg-light text-muted border font-monospace small" title="El SKU Deltron se vinculará al descargar catálogo o consultar ficha">Sin vincular</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($prod['part_number']) && $prod['part_number'] !== '-')
                                <div class="d-flex align-items-center gap-1">
                                    <span class="fw-semibold text-primary font-monospace">{{ $prod['part_number'] }}</span>
                                    <i class="bi bi-clipboard btn-copy-mini" title="Copiar Part Number" onclick="copyToClipboard('{{ $prod['part_number'] }}', this)"></i>
                                </div>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(isset($prod['precio']) && $prod['precio'] > 0)
                                <div class="fw-bold text-danger fs-6">${{ number_format($prod['precio'], 2) }}</div>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">sin IGV</small>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($prod['stock'] > 0)
                                <span class="badge rounded-pill bg-success px-3 py-2 fs-6 shadow-xs">
                                    <i class="bi bi-box-seam me-1"></i> {{ number_format($prod['stock']) }}
                                </span>
                                @else
                                <span class="badge rounded-pill bg-secondary px-3 py-2">0</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Botón Ver Ficha Técnica (API-0000027) -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 shadow-xs btn-view-ficha" 
                                            data-sku="{{ $prod['sku_deltron'] ?? '' }}" 
                                            data-pn="{{ $prod['part_number'] ?? '' }}" 
                                            data-title="{{ $prod['titulo'] }}"
                                            title="Ver Ficha Técnica, imágenes, tránsito y actualizar por SKU (API-0000027)">
                                        <i class="bi bi-file-earmark-text"></i> Ficha
                                    </button>

                                    <!-- Botón Ver Almacenes en Tiempo Real -->
                                    <button type="button" 
                                            class="btn btn-sm btn-primary rounded-pill px-2 py-1 shadow-xs btn-check-stock" 
                                            data-sku="{{ $prod['sku_deltron'] ?? '' }}" 
                                            data-pn="{{ $prod['part_number'] ?? '' }}" 
                                            data-title="{{ $prod['titulo'] }}"
                                            data-price="{{ $prod['precio'] ?? '' }}"
                                            title="Ver disponibilidad por almacén en tiempo real">
                                        <i class="bi bi-buildings"></i> Almacenes
                                    </button>
                                    
                                    <!-- Botón Cotizar en Calculadora -->
                                    <a href="{{ route('calculadora.cotizador') }}?search={{ urlencode(!empty($prod['part_number']) && $prod['part_number'] !== '-' ? $prod['part_number'] : $prod['modelo']) }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 shadow-xs" 
                                       title="Cotizar publicación con este producto">
                                        <i class="bi bi-calculator"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                                <h5 class="fw-normal">No se encontraron productos en stock para "<strong>{{ $keyword }}</strong>"</h5>
                                <p class="small text-muted mb-3">Intenta buscar por otro término o haz una sincronización con Deltron.</p>
                                <a href="{{ route('compras.deltron') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Ver todos los productos
                                </a>
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
                <a href="{{ route('compras.deltron', ['q' => $keyword, 'page' => $page - 1]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('spinnerLoader').style.display='block';">
                    <i class="bi bi-chevron-left me-1"></i> Anterior
                </a>
                @endif
            </div>
            <div class="text-muted text-center small">
                Página <strong>{{ $page }}</strong> • Consulta oficial B2B Deltron
            </div>
            <div>
                @if(count($productos) >= 30)
                <a href="{{ route('compras.deltron', ['q' => $keyword, 'page' => $page + 1]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('spinnerLoader').style.display='block';">
                    Siguiente <i class="bi bi-chevron-right ms-1"></i>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modales de Soporte (Componentes) -->
@include('compras.components.modal_almacenes')
@include('compras.components.modal_historial')
@include('compras.components.modal_ofertas')
@include('compras.components.modal_ficha_sku')

<!-- Lógica JavaScript (Componentes) -->
@include('compras.logic.logic')
@endsection