@extends('layouts.app')
@section('title', 'Rutas Delivery Flex')
@section('content')

<style>
    .card-sistema {
        background-color: #ffffff;
        border: 1px solid #e0e4e8;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
    }

    th.bg-sistema-uno {
        background-color: #043e69 !important;
        color: #ffffff !important;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-bottom: 0 !important;
        padding: 15px 12px !important;
    }

    .table-custom td {
        vertical-align: middle;
        color: #333;
        border-bottom: 1px solid #f1f3f5;
        padding: 12px;
    }

    .table-custom tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>

<div class="container-fluid pt-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-dark fw-bold m-0" style="color: #043e69 !important;">
                <i class="bi bi-geo-alt-fill text-primary me-2"></i> Rutas Delivery Flex (Mercado Libre)
            </h3>
            <span class="badge bg-info text-dark p-2">
                Punto de Partida: Av Bolivia 180, Lima
            </span>
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
                </div>

                <div class="table-responsive rounded-3 border border-light shadow-sm">
                    <table class="table table-custom table-hover m-0 text-center">
                        <thead>
                            <tr>
                                <th class="bg-sistema-uno ps-4">Orden ML</th>
                                <th class="bg-sistema-uno">Cliente</th>
                                <th class="bg-sistema-uno" style="min-width: 200px;">Producto(s)</th>
                                <th class="bg-sistema-uno">Dirección Destino</th>
                                <th class="bg-sistema-uno">Distancia</th>
                                <th class="bg-sistema-uno">Costo Delivery</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($listaEntregas as $entrega)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $entrega['order_id'] }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $entrega['buyer_name'] }}</div>
                                </td>
                                <td class="text-start">
                                    @foreach($entrega['items'] as $item)
                                    <div class="mb-1" style="line-height: 1.2;">
                                        <small class="fw-semibold text-dark">{{ $item->title }}</small>
                                        <span class="badge bg-secondary ms-1">x{{ $item->quantity }}</span>
                                    </div>
                                    @endforeach
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

@endsection