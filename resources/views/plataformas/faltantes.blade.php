@extends('layouts.app')

@section('title', 'Productos Faltantes en Plataformas')

@section('content')
<div class="container-fluid pt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-exclamation-square-fill text-warning"></i> Productos Faltantes por Publicar</h3>
        <a href="{{ route('plataformas') }}" class="btn btn-secondary">Volver a Plataformas</a>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill"></i> Solo se muestran productos que <strong>tienen stock (>0)</strong>. La comparación se realiza vinculando el producto en el sistema, o buscando su modelo en las publicaciones de cada plataforma.
    </div>

    <div class="bg-white p-4 border rounded shadow-sm">
        @if(count($faltantes) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Mini Código</th>
                            <th>Nombre del Producto</th>
                            <th>Modelo (SKU Búsqueda)</th>
                            <th>Stock Disp.</th>
                            <th class="text-center">Falta publicar en</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faltantes as $prod)
                            <tr>
                                <td>{{ $prod->idProducto }}</td>
                                <td>
                                    <a href="{{ route('producto', [encrypt($prod->idProducto)]) }}" target="_blank" class="fw-bold text-decoration-none">
                                        {{ $prod->nombreProducto }}
                                    </a>
                                    <div class="small text-muted">{{ $prod->codigoProducto }}</div>
                                </td>
                                <td>
                                    @if(!empty($prod->modelo) && $prod->modelo !== '-')
                                        <span class="badge bg-secondary fs-6">{{ $prod->modelo }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Sin modelo</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $stockTotal = $prod->Inventario->sum('stock');
                                    @endphp
                                    <span class="badge {{ $stockTotal > 5 ? 'bg-success' : 'bg-warning text-dark' }} fs-6">
                                        {{ $stockTotal }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @foreach($prod->missing_platforms as $plat)
                                        <div class="d-inline-block border rounded p-1 mx-1 mb-1 bg-light text-center" style="min-width: 60px;">
                                            @if($plat->imagenPlataforma)
                                                <img src="{{ asset('storage/'.$plat->imagenPlataforma) }}" alt="{{ $plat->nombrePlataforma }}" style="height: 20px; object-fit: contain;">
                                            @else
                                                <span class="small fw-bold">{{ $plat->nombrePlataforma }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    <a href="{{ route('producto', [encrypt($prod->idProducto)]) }}" target="_blank" class="btn btn-sm btn-primary">Ver Producto</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-success text-center py-4">
                <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                <p class="mt-3 mb-0 fs-5">¡Excelente! Todos los productos con stock están publicados en todas las plataformas.</p>
            </div>
        @endif
    </div>
</div>
@endsection
