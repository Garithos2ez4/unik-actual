@extends('layouts.app')

@section('title', 'Top Compradores')

@section('content')
<div class="container py-4">

    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-6">
            <h2 class="mb-0 fw-bold">
                <i class="bi bi-trophy-fill text-warning me-2"></i>
                Top Compradores
            </h2>
            <small class="text-muted">Clientes con mayor volumen de compras</small>
        </div>
        <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
            <span class="badge bg-sistema-uno fs-6 px-3 py-2">
                <i class="bi bi-people-fill me-1"></i>
                {{ $compradores->count() }} clientes encontrados
            </span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('top-compradores') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="bi bi-calendar3 me-1"></i>Período
                    </label>
                    <select name="periodo" class="form-select form-select-sm" id="selectPeriodo">
                        <option value="mes" {{ ($periodo ?? 'mes') === 'mes' ? 'selected' : '' }}>Este mes</option>
                        <option value="trimestre" {{ ($periodo ?? '') === 'trimestre' ? 'selected' : '' }}>Últimos 3 meses</option>
                        <option value="semestre" {{ ($periodo ?? '') === 'semestre' ? 'selected' : '' }}>Últimos 6 meses</option>
                        <option value="anio" {{ ($periodo ?? '') === 'anio' ? 'selected' : '' }}>Este año</option>
                        <option value="todo" {{ ($periodo ?? '') === 'todo' ? 'selected' : '' }}>Histórico</option>
                        <option value="personalizado" {{ ($periodo ?? '') === 'personalizado' ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </div>

                <div class="col-12 col-md-2" id="fechaDesdeContainer" style="{{ ($periodo ?? 'mes') === 'personalizado' ? '' : 'display:none' }}">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="bi bi-calendar-event me-1"></i>Desde
                    </label>
                    <input type="date" name="desde" class="form-control form-control-sm"
                           value="{{ $desde ?? '' }}">
                </div>

                <div class="col-12 col-md-2" id="fechaHastaContainer" style="{{ ($periodo ?? 'mes') === 'personalizado' ? '' : 'display:none' }}">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="bi bi-calendar-check me-1"></i>Hasta
                    </label>
                    <input type="date" name="hasta" class="form-control form-control-sm"
                           value="{{ $hasta ?? '' }}">
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="bi bi-funnel me-1"></i>Ordenar por
                    </label>
                    <select name="orden" class="form-select form-select-sm">
                        <option value="monto" {{ ($orden ?? 'monto') === 'monto' ? 'selected' : '' }}>Mayor monto</option>
                        <option value="compras" {{ ($orden ?? '') === 'compras' ? 'selected' : '' }}>Más compras</option>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="bi bi-list-ol me-1"></i>Mostrar
                    </label>
                    <select name="limite" class="form-select form-select-sm">
                        <option value="10" {{ ($limite ?? 10) == 10 ? 'selected' : '' }}>Top 10</option>
                        <option value="20" {{ ($limite ?? 10) == 20 ? 'selected' : '' }}>Top 20</option>
                        <option value="50" {{ ($limite ?? 10) == 50 ? 'selected' : '' }}>Top 50</option>
                    </select>
                </div>

                <div class="col-12 col-md-1">
                    <button type="submit" class="btn btn-sistema-uno btn-sm w-100" id="btn-filtrar-compradores">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de compradores --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($compradores->isEmpty())
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="bi bi-person-x" style="font-size: 3rem"></i>
                    <p class="mt-2">No se encontraron compradores en el período seleccionado.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabla-top-compradores">
                        <thead class="bg-sistema-uno text-light">
                            <tr>
                                <th class="ps-4" style="width:50px">#</th>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th class="text-center">Compras</th>
                                <th class="text-end">Monto Total</th>
                                <th class="text-end">Ticket Prom.</th>
                                <th class="text-center">Última Compra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($compradores as $index => $comprador)
                                <tr class="{{ $index < 3 ? 'table-warning' : '' }}">
                                    <td class="ps-4 fw-bold">
                                        @if($index === 0)
                                            <i class="bi bi-trophy-fill text-warning fs-5"></i>
                                        @elseif($index === 1)
                                            <i class="bi bi-trophy-fill text-secondary fs-5"></i>
                                        @elseif($index === 2)
                                            <i class="bi bi-trophy-fill" style="color:#cd7f32;font-size:1.2rem"></i>
                                        @else
                                            <span class="text-muted">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-sistema-uno text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                                 style="width:36px;height:36px;font-size:0.85rem">
                                                {{ strtoupper(substr($comprador->nombre ?? 'N', 0, 1)) }}{{ strtoupper(substr($comprador->apellidoPaterno ?? 'N', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold lh-sm">
                                                    {{ $comprador->nombre }} {{ $comprador->apellidoPaterno }} {{ $comprador->apellidoMaterno }}
                                                </div>
                                                @if($comprador->correo)
                                                    <small class="text-muted">{{ $comprador->correo }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $comprador->numeroDocumento ?? '—' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill">
                                            {{ number_format($comprador->total_compras) }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        S/ {{ number_format($comprador->total_monto, 2) }}
                                    </td>
                                    <td class="text-end text-muted">
                                        <small>S/ {{ number_format($comprador->total_monto / max($comprador->total_compras, 1), 2) }}</small>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">
                                            {{ $comprador->ultima_compra ? \Carbon\Carbon::parse($comprador->ultima_compra)->format('d/m/Y') : '—' }}
                                        </small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totales --}}
                <div class="d-flex justify-content-end align-items-center gap-4 px-4 py-3 border-top bg-light">
                    <span class="text-muted small">
                        <i class="bi bi-cart-check me-1"></i>
                        Total compras: <strong>{{ number_format($compradores->sum('total_compras')) }}</strong>
                    </span>
                    <span class="text-muted small">
                        <i class="bi bi-cash-stack me-1"></i>
                        Monto total: <strong class="text-success">S/ {{ number_format($compradores->sum('total_monto'), 2) }}</strong>
                    </span>
                </div>
            @endif
        </div>
    </div>

</div>

<script>
document.getElementById('selectPeriodo').addEventListener('change', function () {
    const isPersonalizado = this.value === 'personalizado';
    document.getElementById('fechaDesdeContainer').style.display = isPersonalizado ? '' : 'none';
    document.getElementById('fechaHastaContainer').style.display = isPersonalizado ? '' : 'none';
});
</script>
@endsection
