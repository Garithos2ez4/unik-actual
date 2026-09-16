@extends('layouts.app')
@section('title', 'Rutas Delivery Flex')
@section('content')

@include('plataformas.mercadolibre.style.delivery_flex')

<div class="container-fluid pt-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-dark fw-bold m-0" style="color: #043e69 !important;">
                <i class="bi bi-geo-alt-fill text-primary me-2"></i> Rutas Delivery Flex
            </h3>
            <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-success btn-sm fw-bold shadow-sm" onclick="openManualModal()">
                    <i class="bi bi-whatsapp me-1"></i> Agregar Entrega WSP
                </button>
                <span class="badge bg-info text-dark p-2">
                    Punto de Partida: Av Bolivia 180, Lima
                </span>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal -->
    <div class="card card-sistema border-0 rounded-4 overflow-hidden p-3">

        @php
        $iconosZona = [
        'Norte' => '🟦',
        'Sur' => '🟩',
        'Este' => '🟨',
        'Oeste' => '🟧',
        'Centro / Sin Asignar' => '⬜'
        ];
        $coloresZona = [
        'Norte' => 'primary',
        'Sur' => 'success',
        'Este' => 'warning',
        'Oeste' => 'danger',
        'Centro / Sin Asignar' => 'secondary'
        ];
        @endphp

        @forelse($entregasAgrupadas as $zona => $listaEntregas)
        <div class="mb-4">
            <div class="d-flex align-items-center mb-2 px-2">
                <h5 class="fw-bold mb-0 text-dark">
                    {{ $iconosZona[$zona] ?? '📍' }} Zona {{ $zona }}
                </h5>
                <span class="badge bg-{{ $coloresZona[$zona] ?? 'dark' }} ms-2 rounded-pill">
                    {{ count($listaEntregas) }} pedido(s)
                </span>
                <button class="btn btn-sm btn-outline-primary ms-auto" onclick="openMapModal('{{ $zona }}')">
                    <i class="bi bi-map"></i> Ver Mapa
                </button>
            </div>

            <div class="table-responsive rounded-3 border border-light shadow-sm">
                @php
                    $tieneWsp = collect($listaEntregas)->contains(function($entrega) {
                        return ($entrega['tipo'] ?? 'ml') === 'wsp';
                    });
                @endphp
                <table class="table table-custom table-hover m-0 text-center">
                    <thead>
                        <tr>
                            <th class="bg-sistema-uno ps-4" style="width: 60px;">Ruta</th>
                            <th class="bg-sistema-uno">Orden</th>
                            <th class="bg-sistema-uno">Cliente</th>
                            <th class="bg-sistema-uno" style="min-width: 200px;">Producto(s)</th>
                            <th class="bg-sistema-uno">Dirección Destino</th>
                            <th class="bg-sistema-uno">Distancia</th>
                            <th class="bg-sistema-uno">Costo Delivery</br>Aproximado</th>
                            @if($tieneWsp)
                            <th class="bg-sistema-uno" style="width: 80px;">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="sortable-list" data-zona="{{ $zona }}">
                        @foreach($listaEntregas as $index => $entrega)
                        <tr data-id="{{ $entrega['order_id'] }}" data-lat="{{ $entrega['lat'] }}" data-lng="{{ $entrega['lng'] }}" data-destino="{{ $entrega['destino'] }}" data-tipo="{{ $entrega['tipo'] ?? 'ml' }}" data-pedido-web-id="{{ $entrega['pedido_web_id'] ?? '' }}" class="flex-row-draggable">
                            <td class="ps-4 align-middle" style="cursor: grab;">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm route-badge" style="width: 32px; height: 32px; font-size: 15px;">{{ $index + 1 }}</span>
                                    <small class="text-muted mt-1" style="font-size: 10px; font-weight: 600;">DESTINO</small>
                                </div>
                            </td>
                            <td class="fw-bold text-dark align-middle">
                                @if(($entrega['tipo'] ?? 'ml') === 'wsp')
                                <span class="badge-wsp"><i class="bi bi-whatsapp me-1"></i>WSP</span>
                                <br><small class="text-muted">{{ $entrega['order_id'] }}</small>
                                @else
                                {{ $entrega['order_id'] }}
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $entrega['buyer_name'] }}</div>
                            </td>
                            <td class="text-start">
                                @if(is_array($entrega['items']) || $entrega['items'] instanceof \Traversable)
                                @foreach($entrega['items'] as $item)
                                <div class="mb-1" style="line-height: 1.2;">
                                    <small class="fw-semibold text-dark">{{ is_object($item) ? $item->title : ($item['title'] ?? '') }}</small>
                                    <span class="badge bg-secondary ms-1">x{{ is_object($item) ? $item->quantity : ($item['quantity'] ?? 1) }}</span>
                                </div>
                                @endforeach
                                @endif
                            </td>
                            <td>
                                <span class="d-block" style="font-size: 0.9em; max-width: 250px; margin: 0 auto; white-space: normal;">
                                    <i class="bi bi-pin-map text-danger me-1"></i> {{ $entrega['destino'] }}
                                </span>
                            </td>
                            <td>
                                @if($entrega['distancia_km'])
                                <span class="badge bg-white text-primary border border-primary p-2 rounded-pill shadow-sm" style="font-size: 13px;">
                                    <i class="bi bi-car-front me-1"></i> {{ $entrega['distancia_km'] }} km
                                </span>
                                @else
                                <span class="badge bg-secondary">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-success" style="font-size: 1.1em;">
                                    S/ {{ number_format($entrega['costo'], 2) }}
                                </span>
                            </td>
                            @if($tieneWsp)
                            <td>
                                @if(($entrega['tipo'] ?? 'ml') === 'wsp')
                                <div class="d-flex gap-1 justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editManual({{ $entrega['pedido_web_id'] }})" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteManual({{ $entrega['pedido_web_id'] }})" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-emoji-frown fs-2 d-block mb-2 text-light"></i>
            No hay pedidos Flex pendientes en este momento.
        </div>
        @endforelse
    </div>
</div>

<!-- Modal del Mapa de Ruta -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-sistema-uno text-white border-0">
                <h5 class="modal-title fw-bold" id="mapModalTitle">Mapa de Ruta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="flexMap" style="width: 100%; height: 500px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Entrega Manual (WSP) -->
<div class="modal fade" id="manualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #25d366, #128c7e);">
                <h5 class="modal-title fw-bold text-white" id="manualModalTitle">
                    <i class="bi bi-whatsapp me-2"></i> Nueva Entrega WhatsApp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="manualForm">
                    <input type="hidden" id="manualEditId" value="">

                    <div class="row g-3">
                        <!-- Cliente -->
                        <div class="col-md-10">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="hidden" id="manualIdCliente" value="">
                                <input type="text" class="form-control" id="manualNombreCliente" placeholder="Buscar por nombre o documento..." autocomplete="off">
                                <div id="clienteSearchResults" class="producto-search-results d-none">
                                    <div class="list-group list-group-flush" id="clienteSearchList"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#nuevoClienteModal" title="Nuevo Cliente">
                                <i class="bi bi-person-plus-fill"></i>
                            </button>
                        </div>

                        <!-- Producto (Buscador) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Productos</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="manualProductoSearch" placeholder="Buscar producto por nombre..." autocomplete="off">
                                <div id="productoSearchResults" class="producto-search-results d-none">
                                    <div class="list-group list-group-flush" id="productoSearchList"></div>
                                </div>
                            </div>
                            <div id="productosSeleccionados" class="mt-2"></div>
                        </div>

                        <!-- Monto -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Monto Total (S/) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="manualMontoTotal" step="0.01" min="0" placeholder="0.00" required>
                        </div>

                        <!-- Dirección -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Dirección de Entrega <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="manualDireccion" placeholder="Ej. Av. Los Pinos 123, Miraflores" autocomplete="off" required>
                        </div>

                        <!-- Mapa -->
                        <div class="col-12">
                            <div id="manualModalMap" class="border"></div>
                            <input type="hidden" id="manualLat">
                            <input type="hidden" id="manualLng">
                            <small class="text-muted">Arrastra el marcador o haz clic en el mapa para ajustar la ubicación.</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn text-white fw-bold" style="background: linear-gradient(135deg, #25d366, #128c7e);" onclick="saveManual()">
                    <i class="bi bi-check-circle me-1"></i> Guardar Entrega
                </button>
            </div>
        </div>
    </div>
</div>

@include('envios.components.modal_new_cliente')

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@include('plataformas.mercadolibre.logic.delivery_flex')

@endsection