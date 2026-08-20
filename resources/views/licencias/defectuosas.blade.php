@extends('layouts.app')
@section('title', 'Licencias Defectuosas')
@section('content')
<link rel="stylesheet" href="{{ asset('css/licencias/table-licencias.css') }}">
<div class="licencias-container">
    <div class="used-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="bi bi-x-octagon-fill"></i>
            </div>
            <div class="header-info">
                <h4 class="header-title">LICENCIAS DEFECTUOSAS</h4>
                <div class="header-stats">
                    <span class="stat-item">
                        <i class="bi bi-collection"></i>
                        Total: <strong>{{ $licenciasDefectuosas->total() }}</strong>
                    </span>
                    <span class="stat-separator">|</span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('licencias.index') }}" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Volver a Inventario
            </a>
        </div>
    </div>

    <div class="search-container">
        <form action="{{ route('licencias.defectuosas') }}" method="GET" class="search-form">
            <div class="search-input-group">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                    name="search"
                    class="search-input"
                    placeholder="Buscar por Clave, Voucher, N° Ticket o N° Orden..."
                    value="{{ old('search', $search ?? '') }}">
            </div>
            <button type="submit" class="btn-search">
                <i class="bi bi-search"></i>
                Buscar
            </button>
            @if(!empty($search))
                <a href="{{ route('licencias.defectuosas') }}" class="btn-clear">
                    <i class="bi bi-x-circle"></i>
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    @if($licenciasDefectuosas->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <h3 class="empty-state-title">No hay licencias defectuosas</h3>
            <p class="empty-state-text">Todas las licencias están funcionando correctamente</p>
        </div>
    @else
        <div class="table-wrapper">
            <div class="table-container">
                <table class="table-used">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Voucher</th>
                            <th>Orden</th>
                            <th>N° Ticket</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($licenciasDefectuosas as $defectuosa)
                        <tr>
                            <td>
                                <div class="">
                                    <span class="">{{ $defectuosa->clave_key }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="">{{ $defectuosa->licencia->voucher_code ?? '---' }}</span>
                            </td>
                            <td>
                                <span class="">{{ $defectuosa->orden ?? '---' }}</span>
                            </td>
                            <td>
                                <span class="">{{ $defectuosa->numero_ticket ?? '---' }}</span>
                            </td>
                            <td>
                                <span class="">{{ $defectuosa->proveedor->nombreProveedor ?? '---' }}</span>
                            </td>
                            <td>
                                <span class="badge badge-defectuosa">{{ $defectuosa->estado }}</span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button
                                        onclick="abrirModalLicenciaRecuperada(
                                            '{{ route('licencias.cambiar_estado', $defectuosa->licencia->voucher_code) }}',
                                            '{{ $defectuosa->orden }}',
                                            '{{ $defectuosa->clave_key }}',
                                            '{{ $defectuosa->numero_ticket }}',
                                            '{{ optional($defectuosa->proveedor)->idProveedor}}',
                                            '{{ optional($defectuosa->proveedor)->nombreProveedor}}'
                                        )"
                                        class="btn-action btn-recover">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        <span>Recuperar</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pagination-container">
            {{ $licenciasDefectuosas->links() }}
        </div>
    @endif
</div>
<x-licencias.defectuosa.modal-recuperada/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/Licencias/licencias-defectuosas.js') }}"></script>
@endsection
