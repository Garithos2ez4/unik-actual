@extends('layouts.app')
@section('title', 'Licencias Recuperadas')
@section('content')
<link rel="stylesheet" href="{{ asset('css/licencias/table-licencias.css') }}">
<div class="licencias-container">
    <div class="used-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="bi bi-x-octagon-fill"></i>
            </div>
            <div class="header-info">
                <h4 class="header-title">LICENCIAS RECUPERADAS</h4>
                <div class="header-stats">
                    <span class="stat-item">
                        <i class="bi bi-collection"></i>
                        Total: <strong>{{ $licenciasRecuperadas->total() }}</strong>
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
        <form action="{{ route('licencias.recuperadas') }}" method="GET" class="search-form">
            <div class="search-input-group">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                    name="search"
                    class="search-input"
                    placeholder="Buscar por Serial Recuperada, Serial Defectuosa, Voucher o N° Ticket..."
                    value="{{ old('search', $search ?? '') }}">
            </div>
            <button type="submit" class="btn-search">
                <i class="bi bi-search"></i>
                Buscar
            </button>
            @if(!empty($search))
                <a href="{{ route('licencias.recuperadas') }}" class="btn-clear">
                    <i class="bi bi-x-circle"></i>
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    @if($licenciasRecuperadas->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h4 class="empty-title">
                @if ($search)
                    No se encontraron resultados
                @else
                    Sin Licencias Usadas
                @endif
            </h4>
            <p class="empty-text">
                @if ($search)
                    No hay Licecnias que coincidan con "{{ $search }}""
                @else
                    No hay Licencias Recuperadas Registraads en el sistema
                @endif
            </p>
        </div>
    @else
    <div class="table-wrapper">
        <div class="table-container">
            <table class="table-used">
                <thead>
                    <tr>
                        <th>Serial recuperada</th>
                        <th>Serial defectuosa</th>
                        <th>Voucher code</th>
                        <th>Numero de orden</th>
                        <th>Estado</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenciasRecuperadas as $recuperada)
                    <tr>
                        <td>
                            <span>{{ $recuperada->serial_recuperada }}</span>
                        </td>
                        <td>
                            <span>{{ $recuperada->serial_defectuosa }}</span>
                        </td>
                        <td>
                            <span>{{ $recuperada->licencia->voucher_code }}</span>
                        </td>
                        <td>
                            <span>{{ $recuperada->orden ?? 'Sin numero de orden' }}</span>
                        </td>
                        <td>
                            <span class="badge badge-success">{{ $recuperada->estado }}</span>
                        </td>
                        <td>
                            <span>{{ $recuperada->licencia->proveedor->razSocialProveedor ?? '---' }}</span>
                        </td>
                        <td>
                            <div class="action-butons">
                                <button
                                    onclick="abrirModalUsarLicenciaDesdeRecuperadas(
                                        '{{ route('licencias.cambiar_estado',
                                        $recuperada->licencia->voucher_code) }}',
                                        '{{ $recuperada->orden }}',
                                        '{{ $recuperada->serial_recuperada }}',
                                        '{{ $recuperada->id }}'
                                    )"
                                    class="btn-action btn-usar">
                                    Usar
                                </button>
                                <button
                                onclick="abrirModalLicenciaDefectuosa(
                                    '{{ route('licencias.cambiar_estado',
                                    $recuperada->licencia->voucher_code) }}',
                                    '{{ $recuperada->orden }}',
                                    '{{ $recuperada->serial_recuperada }}',
                                    '{{ $recuperada->licencia->proveedor?->idProveedor ?? '' }}', // ID
                                    '{{ $recuperada->licencia->proveedor?->razSocialProveedor ?? '---' }}', // Nombre
                                    '{{ $recuperada->id }}'
                                )"
                                class="btn-action btn-defectuosa">
                                Defectuosa
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-3">
        {{ $licenciasRecuperadas->links() }}
    </div>
    @endif
</div>
{{-- intento de componente de modal usada--}}
<x-licencias.recuperadas.modal_formulario_usada/>
{{-- Intento de componente de modal defectuosa --}}
<x-licencias.recuperadas.modal-formulario-defectuosa/>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/Licencias/licencias-recuperadas.js') }}"></script>

@endsection
