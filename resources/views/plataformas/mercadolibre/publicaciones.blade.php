@extends('layouts.app')

@section('content')
<div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-primary fw-bold">Catálogo Mercado Libre</h2>
            <p class="text-muted mb-0">Gestiona tus publicaciones y opciones de envío (Flex)</p>
        </div>

        <!-- Buscador -->
        <form action="{{ route('plataformas.ml.publicaciones') }}" method="GET" class="d-flex align-items-center">
            <div class="input-group shadow-sm">
                <input type="text" name="q" class="form-control" placeholder="Buscar por título o ID..." value="{{ request('q') }}" style="width: 250px;">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            @if(request('q'))
            <a href="{{ route('plataformas.ml.publicaciones') }}" class="btn btn-outline-secondary ms-2 shadow-sm" title="Limpiar búsqueda">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </form>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Publicación</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th class="text-center">Envíos Flex</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($publicaciones as $item)
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $item['thumbnail'] ?? '' }}" alt="Thumbnail" class="rounded border me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 300px;" title="{{ $item['title'] ?? 'Sin título' }}">
                                            {{ $item['title'] ?? 'Sin título' }}
                                        </div>
                                        <a href="{{ $item['permalink'] ?? '#' }}" target="_blank" class="small text-decoration-none text-primary">
                                            {{ $item['id'] ?? '' }} <i class="bi bi-box-arrow-up-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-success">
                                    {{ $item['currency_id'] ?? '' }} {{ number_format($item['price'] ?? 0, 2) }}
                                </div>
                                
                                {{-- Aquí leemos si Mercado Libre le otorgó Envío Gratis --}}
                                @if(isset($item['shipping']['free_shipping']) && $item['shipping']['free_shipping'] === true)
                                    <span class="badge bg-success mt-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-truck"></i> Envío Gratis
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.7rem;">
                                        Envío a cargo del comprador
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary rounded-pill px-3">{{ $item['available_quantity'] ?? 0 }} u.</span>
                            </td>
                            <td>
                                @if(isset($item['status']) && $item['status'] == 'active')
                                <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i> Activo</span>
                                @elseif(isset($item['status']) && $item['status'] == 'paused')
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-pause-circle me-1"></i> Pausado</span>
                                @else
                                <span class="badge bg-secondary rounded-pill px-3 py-2">{{ ucfirst($item['status'] ?? 'Desconocido') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                $tags = $item['shipping']['tags'] ?? [];
                                // 'self_service_in' significa que Flex está activado.
                                $hasFlex = in_array('self_service_in', $tags);
                                @endphp
                                <div class="form-check form-switch d-flex justify-content-center m-0">
                                    <input class="form-check-input form-check-input-lg flex-toggle" type="checkbox" role="switch"
                                        id="flexSwitch_{{ $item['id'] }}"
                                        data-item-id="{{ $item['id'] }}"
                                        {{ $hasFlex ? 'checked' : '' }}
                                        style="width: 3em; height: 1.5em; cursor: pointer;">
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    No se encontraron publicaciones en esta cuenta.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center bg-light">
                <div class="small text-muted">
                    Mostrando {{ $publicaciones->firstItem() ?? 0 }} - {{ $publicaciones->lastItem() ?? 0 }} de {{ $publicaciones->total() }} publicaciones
                </div>
                <div>
                    {{ $publicaciones->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.flex-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const itemId = this.dataset.itemId;
                const isEnabled = this.checked;
                const originalState = !isEnabled;
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                this.disabled = true;

                fetch('{{ route("plataformas.ml.toggle-flex") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            item_id: itemId,
                            enable: isEnabled
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.disabled = false;
                        if (data.success) {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(data.message || 'Estado actualizado.');
                            }
                        } else {
                            this.checked = originalState;
                            if (typeof toastr !== 'undefined') {
                                toastr.error(data.message || 'Error al actualizar.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.disabled = false;
                        this.checked = originalState;
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Ocurrió un error en la solicitud.');
                        }
                    });
            });
        });
    });
</script>
@endpush