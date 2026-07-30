<link rel="stylesheet" href="{{ asset(path: "css/licencias/lista-licencias.css") }}">
<div class="lista_licencias">
    @if(!$licencias->isEmpty())
    <div class="table-container">
        <table class="licenses-table">
            <thead>
                <tr>
                    <th class="text-start">Pre-Clave</th>
                    <th>Orden Compra</th>
                    <th>Tipo</th>
                    <th>Proveedor</th>
                    <th>Categoria</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($licencias as $licencia)
                <tr>
                    <td class="text-start">
                        <div class="preclave-container">
                            <span class="preclave-text">{{ $licencia->voucher_code }}</span>
                            <button
                                class="copy-btn"
                                onclick="copiarPreClave('{{ $licencia->voucher_code }}')"
                                title="Copiar Pre-Clave"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td>{{ $licencia->orden_compra }}</td>
                    <td>{{ $licencia->tipoLicencia->nombre }}</td>
                    <td>
                        <div class="proveedor-info">
                            <span class="proveedor-nombre">{{ optional($licencia->proveedor)->nombreProveedor ?? 'Sin proveedor' }}</span>
                            @if(optional($licencia->proveedor)->razSocialProveedor)
                            <span class="proveedor-razon">{{ $licencia->proveedor->razSocialProveedor }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="proveedor-nombre">{{ $licencia->categoriaLicencia?->tipo_categoria ?? 'Sin Categoria' }}</span>
                        @if ($licencia->esMultifuncional())
                            <br>
                            <small>
                                Usos: {{$licencia->cantidad_usos}}
                            </small>
                        @endif
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button
                                class="btn-action btn-usar btn-usar-licencia"
                                data-url="{{ route('licencias.cambiar_estado', $licencia->voucher_code) }}"
                                data-orden="{{ $licencia->orden_compra }}"
                                data-multifuncional="{{ $licencia->esMultifuncional() ? 1 : 0 }}"
                            >
                                Usar
                            </button>
                            <button
                                onclick="abrirModalLicenciaDefectuosa('{{ route('licencias.cambiar_estado', $licencia->voucher_code) }}', '{{ $licencia->orden_compra }}', '{{ optional($licencia->proveedor)->idProveedor ?? '' }}', '{{ optional($licencia->proveedor)->razSocialProveedor ?? '' }}')"
                                class="btn-action btn-defectuosa"
                                title="Marcar como defectuosa"
                            >
                                Marcar Defectuosa
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        <x-paginacion :justify="'end'" :coleccion="$licencias" :container="$container"/>
    </div>

    @else
    <div class="empty-state">
        <x-aviso_no_encontrado :mensaje="'No se encontraron licencias.'" />
    </div>
    @endif
</div>

<script src="{{asset('js/Licencias/lista-licencias.js')}}"></script>
