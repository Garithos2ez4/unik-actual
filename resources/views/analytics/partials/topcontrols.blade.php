<div class="card border-0 shadow-sm rounded-4 mt-3 mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" action="{{ url()->current() }}" id="form-filtros-analytics">
            <div class="row align-items-end g-2">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-event me-1"></i>Año</label>
                    <select name="anio" class="form-select form-select-sm" id="filtro-anio" onchange="actualizarLimitesDia()">
                        @for($y = now()->year; $y >= 2026; $y--)
                        <option value="{{ $y }}" {{ ($filtros['anio'] ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-month me-1"></i>Mes</label>
                    <select name="mes" class="form-select form-select-sm" id="filtro-mes" onchange="actualizarLimitesDia()">
                        @php
                        $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        @endphp
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ ($filtros['mes'] ?? now()->month) == $m ? 'selected' : '' }}>{{ $meses[$m-1] }}</option>
                            @endfor
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-plus me-1"></i>Día Inicio</label>
                    <input type="date" name="dia_inicio" class="form-control form-control-sm" id="filtro-dia-inicio"
                        value="{{ $filtros['dia_inicio'] ?? now()->format('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1 small fw-bold text-muted"><i class="bi bi-calendar-check me-1"></i>Día Corte</label>
                    <input type="date" name="dia_fin" class="form-control form-control-sm" id="filtro-dia-fin"
                        value="{{ $filtros['dia_fin'] ?? now()->format('Y-m-d') }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrar
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-light btn-sm border" title="Limpiar Filtros">
                        <i class="bi bi-arrow-counterclockwise text-secondary"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Indicador de rango activo -->
@if(isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin']))
<div class="mb-3">
    <span class="badge bg-white bg-opacity-10 text-success border border-primary px-5 py-3 rounded-pill">
        <i class="bi bi-calendar-range me-1"></i>
        {{ $filtros['fecha_inicio']->translatedFormat('d M Y') }} — {{ $filtros['fecha_fin']->translatedFormat('d M Y') }}
    </span>
</div>
@endif