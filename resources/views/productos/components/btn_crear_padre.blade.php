{{-- Boton disparador en la cabecera / barra de acciones --}}
<div class="d-inline">
    @if ($producto->es_padre)
        <button type="button" class="btn btn-warning text-dark shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#modalCrearPadreComponent" title="Crear otro Contenedor Padre">
            <i class="bi bi-diagram-3-fill"></i> <span class="d-none d-lg-inline">Padre ({{ $producto->variantes->count() }} Hijas)</span>
        </button>
    @elseif ($producto->idProductoPadre)
        <button type="button" class="btn btn-outline-warning text-dark shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#modalCrearPadreComponent" title="Crear Contenedor Padre">
            <i class="bi bi-diagram-3"></i> <span class="d-none d-lg-inline">Crear Padre</span>
        </button>
    @else
        <button type="button" class="btn btn-warning text-dark shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#modalCrearPadreComponent" title="Crear o Convertir a Contenedor Padre">
            <i class="bi bi-diagram-3-fill"></i> <span class="d-none d-lg-inline">Crear Padre</span>
        </button>
    @endif
</div>
