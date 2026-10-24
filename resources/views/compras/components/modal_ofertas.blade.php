<!-- ========================================== -->
<!-- MODAL: OFERTAS, PROMOCIONES Y RECOMENDADOS -->
<!-- ========================================== -->
<div class="modal fade" id="recomendadosModal" tabindex="-1" aria-labelledby="recomendadosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 me-2">
                        <i class="bi bi-fire fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="recomendadosModalLabel">
                            Catálogo de Ofertas y Productos Recomendados Deltron
                        </h5>
                        <small class="text-white-50">Promociones vigentes B2B oficiales y oportunidades de alto stock en Deltron</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Pestañas de Navegación -->
            <div class="bg-light px-3 pt-3 border-bottom">
                <ul class="nav nav-tabs border-0" id="recomendadosTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark border-bottom-0" id="tab-promos-link" data-bs-toggle="tab" data-bs-target="#tab-promos" type="button" role="tab" aria-controls="tab-promos" aria-selected="true">
                            <i class="bi bi-tag-fill text-warning me-1"></i> Promociones Deltron B2B 
                            <span class="badge bg-warning text-dark rounded-pill ms-1">{{ count($promociones) }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-secondary border-bottom-0" id="tab-stock-link" data-bs-toggle="tab" data-bs-target="#tab-stock" type="button" role="tab" aria-controls="tab-stock" aria-selected="false">
                            <i class="bi bi-boxes text-danger me-1"></i> Oportunidades por Alto Stock
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content" id="recomendadosTabContent">
                <!-- PESTAÑA 1: PROMOCIONES OFICIALES DELTRON -->
                <div class="tab-pane fade show active" id="tab-promos" role="tabpanel" aria-labelledby="tab-promos-link">
                    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="input-group" style="max-width: 420px;">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="promoSearchInput" class="form-control" placeholder="Filtrar por SKU, promoción o descripción...">
                                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('promoSearchInput').value=''; filterPromociones();">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="filterSoloConStock" checked onchange="filterPromociones()">
                                <label class="form-check-label small fw-semibold text-dark user-select-none" for="filterSoloConStock">
                                    <i class="bi bi-check2-circle text-success me-1"></i> Solo con stock disponible
                                </label>
                            </div>
                        </div>
                        <div class="small text-muted">
                            Mostrando <strong id="promoVisibleCount">{{ count($promociones) }}</strong> promociones
                        </div>
                    </div>

                    <div class="modal-body p-0">
                        <div class="table-responsive" style="max-height: 520px;">
                            <table class="table table-hover table-striped align-middle mb-0" id="promosTable">
                                <thead class="table-light sticky-top">
                                    <tr class="small text-uppercase text-secondary">
                                        <th width="15%" class="ps-3">ID Promoción</th>
                                        <th width="40%">Descripción de la Oferta / Promoción</th>
                                        <th width="15%">SKU Deltron</th>
                                        <th width="18%">Producto</th>
                                        <th width="12%" class="text-center pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($promociones as $promo)
                                    <tr class="promo-row" data-promo-id="{{ $promo['promocion_id'] ?? '' }}" data-tiene-stock="{{ !empty($promo['tiene_stock']) ? '1' : '0' }}">
                                        <td class="ps-3">
                                            <span class="badge bg-warning text-dark font-monospace fw-bold">{{ $promo['promocion_id'] ?? '-' }}</span>
                                            @if(!empty($promo['detalle_cargado']))
                                                <span class="badge bg-success text-white rounded-pill ms-1" title="Detalle sincronizado en base de datos"><i class="bi bi-check2"></i> BD</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block">{{ $promo['promocion_descripcion'] ?? '-' }}</span>
                                            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap small">
                                                @if(!empty($promo['tiene_stock']))
                                                    <span class="badge rounded-pill" style="background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc;" title="Stock disponible en Deltron">
                                                        <i class="bi bi-box-seam me-1"></i>Stock: {{ number_format($promo['stock_total_promo']) }} uds.
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger text-white rounded-pill" title="Sin stock disponible en almacenes de Deltron">
                                                        <i class="bi bi-x-circle me-1"></i>Sin stock / Agotado
                                                    </span>
                                                @endif
                                                @if(!empty($promo['precio_promocion']))
                                                    <span class="badge rounded-pill" style="background-color: #fff3cd; color: #664d03; border: 1px solid #ffecb5;">
                                                        <i class="bi bi-tag-fill me-1"></i>Precio Promo: ${{ number_format($promo['precio_promocion'], 2) }}
                                                    </span>
                                                @endif
                                                @if(!empty($promo['fecha_fin']))
                                                    <span class="badge bg-light text-secondary border">
                                                        <i class="bi bi-calendar-event me-1"></i>Fin: {{ $promo['fecha_fin'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="badge bg-secondary font-monospace">{{ $promo['sku'] ?? '-' }}</span>
                                                @if(!empty($promo['sku']))
                                                <i class="bi bi-clipboard btn-copy-mini" title="Copiar SKU" onclick="copyToClipboard('{{ $promo['sku'] }}', this)"></i>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $promo['sku_descripcion'] ?? '-' }}</small>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-2 py-1 shadow-xs btn-promo-detail" 
                                                        data-promo-id="{{ $promo['promocion_id'] }}"
                                                        title="Ver ítems del combo, precios unitarios, vigencia y stock por almacén">
                                                    <i class="bi bi-gift-fill me-1"></i> Ver Combo
                                                </button>
                                                @if(!empty($promo['sku']))
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary rounded-pill px-2 py-1 shadow-xs btn-check-stock" 
                                                        data-sku="{{ $promo['sku'] }}" 
                                                        data-pn="" 
                                                        data-title="{{ $promo['promocion_descripcion'] ?? ($promo['sku_descripcion'] ?? $promo['sku']) }}"
                                                        data-price="{{ $promo['precio_promocion'] ?? '' }}"
                                                        title="Consultar stock en almacenes en tiempo real">
                                                    <i class="bi bi-buildings"></i>
                                                </button>
                                                <a href="{{ route('calculadora.cotizador') }}?search={{ urlencode($promo['sku']) }}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 shadow-xs" 
                                                   title="Abrir en Cotizador para calcular precio de venta y crear en catálogo">
                                                    <i class="bi bi-calculator"></i> Cotizar
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                            No hay promociones activas registradas en este momento.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 2: OPORTUNIDADES POR ALTO STOCK -->
                <div class="tab-pane fade" id="tab-stock" role="tabpanel" aria-labelledby="tab-stock-link">
                    <div class="alert alert-warning m-3 py-2 border-0 shadow-xs">
                        <i class="bi bi-info-circle-fill me-1"></i> Estos productos fueron detectados con más de 50 unidades en Deltron durante la sincronización, pero <strong>no existen en tu catálogo</strong>.
                    </div>
                    <div class="modal-body p-0">
                        <div class="table-responsive" style="max-height: 520px;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="ps-4">Fecha Reporte</th>
                                        <th>Producto (Deltron)</th>
                                        <th>Part Number</th>
                                        <th>Modelo</th>
                                        <th class="text-center">Stock Detectado</th>
                                        <th class="text-center pe-4">Acción</th>
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
                                            <span class="fw-bold text-dark d-block" style="max-width: 380px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $rec['titulo'] ?? '' }}">
                                                {{ $rec['titulo'] ?? 'Producto Deltron' }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $pnFinal = !empty($rec['part_number']) ? $rec['part_number'] : null;
                                                if (!$pnFinal && !empty($rec['sku'])) {
                                                    $foundSku = \App\Models\Inventario\InventarioProveedorSku::where('sku_proveedor', $rec['sku'])->first();
                                                    if ($foundSku && !empty($foundSku->part_number)) {
                                                        $pnFinal = $foundSku->part_number;
                                                    }
                                                }
                                            @endphp
                                            @if(!empty($pnFinal))
                                                <span class="fw-bold text-primary font-monospace">{{ $pnFinal }}</span>
                                                <i class="bi bi-clipboard btn-copy-mini ms-1" title="Copiar Part Number" onclick="copyToClipboard('{{ $pnFinal }}', this)"></i>
                                            @else
                                                <span class="badge bg-light text-secondary border font-monospace small" title="El corte rápido de inicio de día no envía PN. Haz clic en Ficha para consultarlo.">Cód: {{ $rec['sku'] ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-secondary font-monospace">{{ $rec['sku'] ?? ($rec['modelo'] ?? '-') }}</span></td>
                                        <td class="text-center">
                                            <span class="badge bg-success rounded-pill px-3 py-2">
                                                <i class="bi bi-box-seam me-1"></i> {{ number_format($rec['stock'] ?? 0) }} uds.
                                            </span>
                                        </td>
                                        <td class="text-center pe-4 text-nowrap">
                                            @if(!empty($rec['sku']))
                                            <a href="{{ route('createproducto') }}?from_deltron={{ urlencode($rec['sku']) }}" target="_blank" class="btn btn-sm btn-success rounded-pill px-2 py-1 shadow-xs me-1" title="Crear este producto en Unike Store">
                                                <i class="bi bi-plus-circle"></i> Crear
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 me-1" onclick="abrirModalFichaSku('{{ $rec['sku'] }}', '{{ $pnFinal ?? '' }}', false, this)" title="Ver ficha técnica y Part Number oficial en Deltron">
                                                <i class="bi bi-file-earmark-text"></i> Ficha
                                            </button>
                                            @endif
                                            <a href="{{ route('calculadora.cotizador') }}?search={{ urlencode($pnFinal ?: ($rec['sku'] ?? '')) }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1">
                                                <i class="bi bi-calculator"></i> Cotizar
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                    @endforeach

                                    @if(!$hasRecomendados)
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
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

            <div class="modal-footer bg-light py-2">
                <small class="text-muted me-auto">
                    <i class="bi bi-shield-check text-success"></i> Promociones y oportunidades servidas desde la base de datos local.
                </small>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: DETALLE DE PROMOCIÓN B2B (API-0000025: Detalle Combo) -->
<!-- ============================================================== -->
<div class="modal fade" id="modalDetallePromocion" tabindex="-1" aria-labelledby="modalDetallePromocionLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 me-2">
                        <i class="bi bi-gift-fill fs-5 text-white"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold mb-0" id="modalDetallePromocionLabel">Detalle de Promoción Deltron</h5>
                            <span class="badge bg-warning text-dark font-monospace fw-bold" id="promoDetalleIdBadge">-</span>
                            <span class="badge bg-info text-dark font-monospace" style="font-size: 0.72rem;">API-0000025</span>
                        </div>
                        <small class="text-white-50" id="promoDetalleTitle">Cargando información oficial de Deltron...</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- SPINNER LOADING -->
                <div id="promoLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted fw-semibold mb-1">Consultando detalle de la promoción en Deltron...</p>
                    <small class="text-secondary d-block">Verificando productos del combo, precios unitarios, fin de vigencia y stock por almacén</small>
                </div>

                <!-- ALERTA DE ERROR -->
                <div id="promoError" class="alert alert-danger py-3 border-0 shadow-xs" style="display: none;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
                        <div>
                            <strong class="d-block mb-1">No se pudo cargar el detalle</strong>
                            <span id="promoErrorMsg" class="small">Ocurrió un inconveniente al consultar la promoción.</span>
                        </div>
                    </div>
                </div>

                <!-- CONTENIDO DEL DETALLE -->
                <div id="promoContent" style="display: none;">
                    <!-- ALERTA PROMOCIÓN AGOTADA -->
                    <div id="promoAgotadoAlert" class="alert alert-danger py-2 px-3 mb-3 border-0 rounded-3 d-flex align-items-center justify-content-between shadow-xs" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-x-circle-fill text-danger fs-4 me-2"></i>
                            <div>
                                <strong class="text-dark d-block">Promoción agotada en Deltron (0 stock)</strong>
                                <small class="text-secondary">Deltron no cuenta con combos disponibles en este momento en ninguno de sus almacenes.</small>
                            </div>
                        </div>
                        <span class="badge bg-danger text-white rounded-pill px-3 py-2">Sin stock</span>
                    </div>

                    <!-- TARJETAS DE RESUMEN (PRECIO, FECHA FIN, STOCK COMBO, ORIGEN) -->
                    <div class="row g-3 mb-4">
                        <!-- PRECIO PROMO -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card h-100 border-0 bg-light p-3 shadow-xs rounded-3 text-center">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-tag text-success me-1"></i> Precio Promo
                                </small>
                                <h4 class="fw-bold text-success my-2" id="promoDetallePrecioUsd">$0.00</h4>
                                <small class="text-muted" style="font-size: 0.75rem;">+ IGV: <span id="promoDetallePrecioIgv" class="fw-semibold text-dark">$0.00</span></small>
                            </div>
                        </div>

                        <!-- VIGENCIA / FECHA FIN -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card h-100 border-0 bg-light p-3 shadow-xs rounded-3 text-center">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-calendar-event text-danger me-1"></i> Fecha Fin
                                </small>
                                <h6 class="fw-bold text-dark my-2" id="promoDetalleFechaFin">-</h6>
                                <div id="promoDetalleVigenciaBadge">
                                    <span class="badge bg-success rounded-pill px-2">Vigente</span>
                                </div>
                            </div>
                        </div>

                        <!-- STOCK DISPONIBLE DEL COMBO -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card h-100 border-0 bg-light p-3 shadow-xs rounded-3 text-center">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-boxes text-primary me-1"></i> Combos Totales
                                </small>
                                <h4 class="fw-bold text-primary my-2" id="promoDetalleStockTotal">0</h4>
                                <small class="text-muted" style="font-size: 0.75rem;">Promociones completas</small>
                            </div>
                        </div>

                        <!-- ORIGEN / CUPO PROTEGIDO -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card h-100 border-0 bg-light p-3 shadow-xs rounded-3 text-center">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-shield-check text-info me-1"></i> Origen de Datos
                                </small>
                                <div class="my-2" id="promoDetalleOrigenBadge">
                                    <span class="badge rounded-pill px-2 py-1" style="background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc;">
                                        <i class="bi bi-database-check me-1"></i> Base de Datos
                                    </span>
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;" id="promoDetalleActualizadoAt">30 llamadas/h protegidas</small>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 1: PRODUCTOS QUE FORMAN EL COMBO -->
                    <div class="card border-0 shadow-xs rounded-3 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-collection-fill text-warning me-2"></i> Productos que Forman la Promoción / Combo
                            </h6>
                            <span class="badge bg-secondary rounded-pill" id="promoItemsCountBadge">0 ítems</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="promoItemsTable">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="ps-3" width="15%">Tipo</th>
                                        <th width="20%">SKU Deltron</th>
                                        <th width="35%">Descripción del Producto</th>
                                        <th class="text-center" width="15%">Cantidad</th>
                                        <th class="text-end pe-3" width="15%">P. Unit. Promo</th>
                                    </tr>
                                </thead>
                                <tbody id="promoItemsTableBody">
                                    <!-- Items inyectados dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: STOCK DE LA PROMOCIÓN POR ALMACÉN DELTRON -->
                    <div class="card border-0 shadow-xs rounded-3 mb-2">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-buildings-fill text-primary me-2"></i> Stock de Promociones Completas por Almacén
                            </h6>
                            <small class="text-muted">Cuántas promociones completas se pueden atender en cada sede</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="promoStockTable">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="ps-3" width="20%">Cód. Almacén</th>
                                        <th width="50%">Sede / Almacén Deltron</th>
                                        <th class="text-center pe-3" width="30%">Promociones Atendibles</th>
                                    </tr>
                                </thead>
                                <tbody id="promoStockTableBody">
                                    <!-- Almacenes inyectados dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="btnForcePromoRefresh" title="Fuerza una nueva llamada a la API Deltron (consume 1 llamada del límite de 30/hora)">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refrescar desde API en vivo
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <a href="#" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 shadow-xs" id="promoCotizarPrincipalBtn">
                        <i class="bi bi-calculator me-1"></i> Cotizar Producto Principal
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

