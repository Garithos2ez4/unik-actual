@extends('layouts.app')

@section('title', 'Historial de Reclamos')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="display-6"><i class="bi bi-clock-history text-primary"></i> Historial de Reclamos</h2>
            <p class="text-muted">Búsqueda global de casos por Orden de Compra o Número de Caso.</p>
        </div>
    </div>

    <!-- Buscador -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light">
            <form action="{{ route('reclamos.historia') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold small">Orden de Compra / Venta</label>
                    <div class="input-group" style="position: relative;">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" name="orden" id="input-search-orden" class="form-control" placeholder="Ej: 2000123456" value="{{ $orden }}" autocomplete="off">
                        <ul class="list-group w-100 shadow" style="position: absolute; top: 100%; z-index: 1000;" id="suggestion-orden"></ul>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold small">Número de Caso (Plataforma)</label>
                    <div class="input-group" style="position: relative;">
                        <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
                        <input type="text" name="caso" id="input-search-caso" class="form-control" placeholder="Ej: CASE-12345" value="{{ $caso }}" autocomplete="off">
                        <ul class="list-group w-100 shadow" style="position: absolute; top: 100%; z-index: 1000;" id="suggestion-caso"></ul>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($orden || $caso)
    <div class="row">
        @forelse($reclamos as $reclamo)
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom py-3">
                    <div>
                        <span class="badge bg-dark me-2">{{ $reclamo->Plataforma->nombrePlataforma ?? 'Plataforma' }}</span>
                        <span class="fw-bold fs-5">Orden: {{ $reclamo->ordenCompra }}</span>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small">Registrado el: {{ $reclamo->fechaReclamo->format('d/m/Y') }}</span>
                        <a href="{{ route('reclamos.edit', $reclamo->idReclamoPlataforma) }}" class="btn btn-sm btn-outline-primary ms-3">
                            <i class="bi bi-eye"></i> Ver Detalle Completo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 border-end">
                            <h6 class="text-uppercase text-muted small fw-bold mb-3">Información Inicial</h6>
                            <p class="mb-2"><strong># Caso:</strong> {{ $reclamo->numeroCaso ?? 'N/A' }}</p>
                            <p class="mb-2"><strong>Estado:</strong> 
                                <span class="badge {{ $reclamo->estadoGeneral == 'CERRADO' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $reclamo->estadoGeneral }}
                                </span>
                            </p>
                            <div class="p-2 bg-light rounded small mt-3">
                                <strong>Relato inicial:</strong><br>
                                {{ Str::limit($reclamo->detalleReclamo, 150) }}
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="text-uppercase text-muted small fw-bold mb-3">Línea de Tiempo de Seguimiento</h6>
                            <div class="timeline-simple">
                                @forelse($reclamo->Seguimientos as $seg)
                                <div class="ps-3 border-start border-2 border-primary mb-3 position-relative">
                                    <div class="position-absolute bg-primary rounded-circle" style="width: 10px; height: 10px; left: -6px; top: 5px;"></div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="fw-bold text-primary">{{ $seg->created_at->format('d/m/Y H:i') }} | Vía: {{ $seg->respondioCanal }}</small>
                                    </div>
                                    <p class="mb-1 small">{{ $seg->mensajeRespuesta }}</p>
                                    @if($seg->Evidencias->count() > 0)
                                    <div class="d-flex gap-2 mt-2">
                                        @foreach($seg->Evidencias as $ev)
                                        <a href="{{ $ev->urlArchivo }}" target="_blank" class="badge bg-info text-dark text-decoration-none">
                                            <i class="bi bi-link-45deg"></i> Ver {{ $ev->tipoEvidencia }}
                                        </a>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @empty
                                <p class="text-muted small">No hay seguimientos registrados.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="bi bi-search text-muted display-1"></i>
            <h4 class="mt-3 text-muted">No encontramos ningún reclamo con esos datos.</h4>
            <p class="text-muted">Prueba con otro número de orden o caso.</p>
        </div>
        @endforelse
    </div>
    @else
    <div class="text-center py-5">
        <div class="p-5 bg-white shadow-sm rounded-3 d-inline-block">
            <i class="bi bi-info-circle text-primary display-4"></i>
            <h4 class="mt-3">Buscador Histórico</h4>
            <p class="text-muted">Ingresa un número de orden o caso arriba para ver todo el historial de interacciones.</p>
        </div>
    </div>
    @endif
</div>

<style>
    .timeline-simple {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 10px;
    }
    .cursor-pointer { cursor: pointer; }
</style>

<script>
    function setupAutocomplete(inputId, suggestionId, field) {
        const input = document.getElementById(inputId);
        const suggestions = document.getElementById(suggestionId);

        input.addEventListener('input', function() {
            if (this.value.length >= 3) {
                fetch(`{{ route('reclamos.search-ajax') }}?query=${this.value}&field=${field}`)
                    .then(r => r.json())
                    .then(data => {
                        suggestions.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item list-group-item-action cursor-pointer';
                                const val = field === 'orden' ? item.ordenCompra : item.numeroCaso;
                                li.innerHTML = `
                                    <div class="d-flex justify-content-between">
                                        <span><strong>${val}</strong></span>
                                        <span class="badge bg-light text-dark border">${item.plataforma.nombre_plataforma || item.plataforma.nombrePlataforma}</span>
                                    </div>
                                `;
                                li.onclick = () => {
                                    input.value = val;
                                    suggestions.innerHTML = '';
                                    // Opcional: enviar formulario al seleccionar
                                    input.closest('form').submit();
                                };
                                suggestions.appendChild(li);
                            });
                        }
                    });
            } else {
                suggestions.innerHTML = '';
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target !== input) suggestions.innerHTML = '';
        });
    }

    setupAutocomplete('input-search-orden', 'suggestion-orden', 'orden');
    setupAutocomplete('input-search-caso', 'suggestion-caso', 'caso');
</script>
@endsection
