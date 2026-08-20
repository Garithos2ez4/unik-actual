@extends('layouts.app')
@section('title', 'Licencias')
@section('content')
<link rel="stylesheet" href="{{ asset('css/licencias/index-licencias.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/licencias/licencias-usadas.css') }}?v={{ time() }}">
<div class="container" style="max-width: 85%;">

    <div class="licenses-header">
        <div class="titulo-header">
            <h1><i class="bi bi-key-fill me-2"></i>Licencias Nuevas</h1>
        </div>
        <div class="header-stats">
            <span class="stat-item">
                <i class="bi bi-collection"></i>
                Total: <strong>{{ $licencias->total() }}</strong>
            </span>
            <span class="stat-separator">|</span>
        </div>
    </div>

    <div class="options-container-general">
        <div class="options-container">
            <a class="option-toggle-btn" href="#collapse-buttons" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapse-buttons">
                <i class="bi bi-gear-fill"></i>
                Opciones de Licencias
            </a>
            <a class="option-toggle-btn" href="#collapse-buttons-ecxel" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapse-buttons-ecxel">
                <i class="bi bi-file-earmark-excel-fill"></i>
                Opciones para Excel
            </a>
            <a class="option-toggle-btn" href="#collapse-filtros" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapse-filtros">
                <i class="bi bi-funnel-fill"></i>
                Filtros
            </a>
        </div>
        <div class="options-container">
            <button class="btn option-toggle-btn" data-bs-toggle="modal" data-bs-target="#modalTotales">
                <i class="bi bi-graph-up"></i> Ver Totales
            </button>
            <button class="btn option-toggle-btn" data-bs-toggle="modal" data-bs-target="#modalInformation" title="Information">
                <i class="bi bi-info-square"></i>
            </button>
        </div>
    </div>

    <div class="collapse" id="collapse-buttons">
        <div class="collapse-section">
            <div class="action-buttons-grid">
                <a href="{{ route('licencias.create') }}" class="action-btn btn-primary-custom">
                    <i class="bi bi-plus-circle-fill"></i>
                    Agregar Licencia
                </a>
                <a href="{{ route('licencias.importar.vista') }}" class="action-btn btn-primary-custom">
                    <i class="bi bi-box2-fill"></i>
                    Importar licencias
                </a>
                <a href="{{ route('licencias.usadas') }}" class="action-btn btn-info-custom">
                    <i class="bi bi-check-circle-fill"></i>
                    Ver Licencias Usadas
                </a>
                <a href="{{ route('licencias.defectuosas') }}" class="action-btn btn-danger-custom">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Ver Licencias Defectuosas
                </a>
                <a href="{{ route('licencias.recuperadas') }}" class="action-btn btn-warning-custom">
                    <i class="bi bi-arrow-clockwise"></i>
                    Ver Licencias Recuperadas
                </a>
            </div>
        </div>
    </div>

    <div class="collapse" id="collapse-buttons-ecxel">
        <div class="collapse-section">
            <div class="action-buttons-grid">
                <button type="button" class="action-btn btn-success-custom" data-bs-toggle="modal" data-bs-target="#modalProveedores">
                    <i class="bi bi-building"></i>
                    Ver Proveedores (Excel)
                </button>
                <button type="button" class="action-btn btn-success-custom" data-bs-toggle="modal" data-bs-target="#modalTiposLicencias">
                    <i class="bi bi-tags-fill"></i>
                    Ver Tipos de Licencia (Excel)
                </button>
            </div>
        </div>
    </div>

    <div class="collapse" id="collapse-filtros">
        <div class="collapse-section">
            <div class="filter-container">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <label for="filtro-tipo" class="filter-label">
                        <i class="bi bi-filter-circle-fill me-1"></i>
                        Filtrar por tipo:
                    </label>
                    <select id="filtro-tipo" class="filter-select" onchange="filtrarPorTipo()">
                        <option value="">-- Todos los tipos --</option>
                        @foreach ($tiposLicencias as $tipo)
                        <option value="{{ $tipo->id }}"
                            {{ (request('tipo') == $tipo->id || $tipoSeleccionado == $tipo->id) ? 'selected' : '' }}>
                            {{ $tipo->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="search-container mt-3 mb-3 bg-white p-3 rounded-3 shadow-sm border">
        <form id="form-search-licencias" action="{{ route('licencias.index') }}" method="GET" class="search-form d-flex gap-2 align-items-center w-100" onsubmit="event.preventDefault(); buscarLicencias();">
            <input type="hidden" name="tipo" id="search-hidden-tipo" value="{{ request('tipo', $tipoSeleccionado ?? '') }}">
            <div class="search-input-group flex-grow-1 position-relative">
                <i class="bi bi-search search-icon position-absolute" style="left: 1rem; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1rem;"></i>
                <input type="text"
                    id="input-search-licencias"
                    name="search"
                    class="form-control search-input"
                    style="padding: 0.75rem 1rem 0.75rem 2.75rem; border: 2px solid #dee2e6; border-radius: 8px; font-size: 0.95rem; width: 100%;"
                    placeholder="Buscar por Voucher code, Clave key, Serial defectuosa o Serial recuperada..."
                    value="{{ old('search', $search ?? '') }}"
                    oninput="debounceBuscarLicencias()">
            </div>
            <button type="submit" class="btn btn-primary btn-search fw-bold d-flex align-items-center gap-2" style="padding: 0.75rem 1.5rem; border-radius: 8px; white-space: nowrap;">
                <i class="bi bi-search"></i>
                Buscar
            </button>
            @if(!empty($search))
            <a href="{{ route('licencias.index', request('tipo') ? ['tipo' => request('tipo')] : []) }}" class="btn btn-outline-danger btn-clear d-flex align-items-center gap-2" style="padding: 0.75rem 1.25rem; border-radius: 8px; white-space: nowrap;" onclick="limpiarBusqueda(event)">
                <i class="bi bi-x-circle"></i>
                Limpiar
            </a>
            @else
            <button type="button" id="btn-clear-licencias" class="btn btn-outline-danger btn-clear d-none align-items-center gap-2" style="padding: 0.75rem 1.25rem; border-radius: 8px; white-space: nowrap;" onclick="limpiarBusqueda(event)">
                <i class="bi bi-x-circle"></i>
                Limpiar
            </button>
            @endif
        </form>
    </div>

    <div id="container-list-licencias" class="w-100">
        <x-licencias.lista_licencias :licencias="$licencias" :container="'container-list-licencias'" />
    </div>

    @if(session('success'))
    <script>
        Swal.fire({
            title: 'Cesar cabro',
            text: '{{ session('
            success ') }}',
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        Swal.fire({
            title: 'Error',
            text: '{{ session('
            error ') }}',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    </script>
    @endif
</div>
</div>
<div class="modal fade" id="modalProveedores" tabindex="-1" aria-labelledby="modalProveedoresLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProveedoresLabel">Listado de Proveedores</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proveedores as $proveedor)
                        <tr>
                            <td>{{ $proveedor->idProveedor }}</td>
                            <td>{{ $proveedor->nombreProveedor }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTiposLicencias" tabindex="-1" aria-labelledby="modalTiposLicenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="modalTiposLicenciaLabel">Listado Tipos de Licencia</h5>
                <div>

                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoTipoLicencia">
                        + Agregar Tipo
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-ligth">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tiposLicencias as $tipos)
                        <tr>
                            <td>{{ $tipos->id }}</td>
                            <td>{{ $tipos->nombre }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoTipoLicencia" tabindex="-1" aria-labelledby="modalNuevoTipoLicenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tiposLicencia.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Tipo</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInformation" tabindex="-1" aria-labelledby="modalInformationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-3">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title d-flex align-items-center gap-2" id="modalInformationLabel">
                    <i class="bi bi-info-circle-fill fs-4"></i>
                    Información importante sobre Pre-Claves
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <section>
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-receipt text-primary me-2"></i> ¿Qué es la Pre-Clave o Voucher?
                    </h5>
                    <p class="text-muted">
                        La <strong>Pre-Clave</strong> o <strong>Voucher</strong> es un código temporal que debe
                        ser <span class="text-primary fw-semibold">canjeado</span> para obtener la clave de activación
                        real de un producto.
                    </p>
                    <p>
                        El formulario para canjear la clave le solicitara el numero de orden el cual puede visualizar en la tabla,
                        le pedira el codigo que tambien se encuentra en la tabla (Pre-Clave), el correo siempre sera el siguiente: <span class="text-primary fw-semibold">unik_store_peru@hotmail.com</span>
                    </p>

                    <div class="alert alert-info d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-link-45deg fs-4"></i>
                        <div>
                            Puedes canjear tu código en:
                            <a href="https://code4redeem.com/" target="_blank" class="fw-bold text-decoration-underline">https://code4redeem.com/</a>
                        </div>
                    </div>
                </section>

                <hr class="my-4">

                <section>
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-building text-primary me-2"></i> Proveedor: Wincdkey Ltd
                    </h5>
                    <p class="text-muted">
                        Este método de canje <strong>solo aplica</strong> cuando el proveedor es
                        <strong>“Wincdkey Ltd”</strong>.
                    </p>

                    <ul class="list-unstyled ms-3">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>
                            Si el código tiene <strong>18 caracteres</strong> ➝ Es una <span class="fw-bold">Pre-Clave</span>.
                        </li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>
                            Si la clave ya está disponible ➝ Se trata de la <span class="fw-bold">clave real</span>.
                        </li>
                    </ul>

                    <div class="alert alert-warning mt-3" role="alert">
                        Recuerda: Para otros proveedores, la clave real se carga directamente y no requiere canje.
                    </div>
                </section>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalTotales" tabindex="-1" aria-labelledby="modalTotalesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTotalesLabel">
                    <i class="bi bi-graph-up"></i> Totales por tipo de licencia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                @if($totalesPorTipo->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead>
                            <tr>
                                <th>Tipo de licencia</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($totalesPorTipo as $tipo)
                            <tr>
                                <td>{{ $tipo->tipoLicencia->nombre ?? 'Sin nombre' }}</td>
                                <td><strong>{{ $tipo->total }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-warning text-center mb-0">
                    No hay licencias registradas aún.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDecisionUso" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-layers-fill me-2"></i>
                    Tipo de uso
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p class="mb-3">
                    Esta licencia es <strong>Multiusuario</strong>.<br>
                    ¿Cómo deseas usarla?
                </p>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="confirmarUso('PARCIAL')">
                        Uso parcial
                    </button>

                    <button class="btn btn-outline-success" onclick="confirmarUso('COMPLETO')">
                        Uso completo
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalUsarLicencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formUsarLicencia" method="POST" enctype="multipart/form-data" class="mx-auto">
            @csrf
            <input type="hidden" name="nuevo_estado" value="USADA">

            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-sistema-uno text-white border-0 position-relative overflow-hidden">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-container me-3">
                            <i class="bi bi-key-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold">Usar Licencia</h5>
                            <small class="opacity-75">Registrar nueva licencia en el sistema</small>
                        </div>
                    </div>
                    <button type="button"
                        class="btn-close btn-close-white"
                        onclick="cerrarModal()"
                        aria-label="Cerrar">
                    </button>
                    <div class="position-absolute top-0 end-0 opacity-10">
                        <i class="bi bi-shield-check" style="font-size: 6rem; transform: translate(1.5rem, -1.5rem);"></i>
                    </div>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-key text-primary me-2"></i>
                                Clave de Activación
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-modern">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-shield-lock text-primary"></i>
                                </span>
                                <input type="text"
                                    name="clave_key"
                                    id="clave_key"
                                    class="form-control border-start-0 font-monospace"
                                    placeholder="Ingrese la clave de activación">

                            </div>

                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-receipt text-success me-2"></i>
                                Orden de Compra
                            </label>
                            <div class="input-group input-group-modern">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-file-text text-success"></i>
                                </span>
                                <input type="text"
                                    name="orden"
                                    id="orden"
                                    class="form-control border-start-0"
                                    placeholder="Ej: 421852"
                                    readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4 d-none" id="contenedorTipoUso">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-diagram-3 text-warning me-2"></i>
                                Tipo de Uso
                                <span class="text-danger">*</span>
                            </label>
                            <input type="hidden" name="modo_uso" id="modo_uso_hidden">
                        </div>
                    </div>
                    <div class="equipment-section">
                        <div class="section-header mb-3">
                            <h6 class="section-title mb-0">
                                <i class="bi bi-pc-display text-info me-2"></i>
                                Información del Equipo
                            </h6>
                            <div class="section-line"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="bi bi-laptop me-1 text-info"></i>
                                    Equipo
                                </label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-pc text-info"></i>
                                    </span>
                                    <input type="text"
                                        name="equipo"
                                        id="equipo"
                                        class="form-control border-start-0"
                                        placeholder="Nombre del equipo">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="bi bi-tags me-1 text-info"></i>
                                    Tipo de Equipo
                                </label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-collection text-info"></i>
                                    </span>
                                    <input type="text"
                                        name="tipo_equipo"
                                        id="tipo_equipo"
                                        class="form-control border-start-0"
                                        placeholder="Tipo de equipo">
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="bi bi-upc-scan me-1 text-warning"></i>
                                    Serial del Equipo
                                </label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-hash text-warning"></i>
                                    </span>
                                    <input type="text"
                                        name="serial_equipo"
                                        id="serial_equipo"
                                        class="form-control border-start-0 font-monospace"
                                        placeholder="Serial único del equipo">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="description-section">
                        <div class="section-header mb-3">
                            <h6 class="section-title mb-0">
                                <i class="bi bi-card-text text-secondary me-2"></i>
                                Información Adicional
                            </h6>
                            <div class="section-line"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-chat-text me-1 text-secondary"></i>
                                Descripción
                            </label>
                            <div class="textarea-container">
                                <textarea name="descripcion"
                                    id="descripcion"
                                    class="form-control modern-textarea"
                                    rows="3"
                                    placeholder="Información adicional sobre el equipo, ubicación, usuario asignado, etc."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">
                            <i class="bi bi-file-earmark-arrow-up me-1 text-primary"></i>
                            Archivo de licencia (opcional)
                        </label>
                        <input type="file"
                            name="archivo"
                            id="archivo"
                            class="form-control"
                            accept=".rcf,.txt,.pdf">
                        <div class="form-text">Solo archivos pequeños (ej. .rcf)</div>
                    </div>
                    <div class="alert alert-info-modern border-0 mt-4" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="alert-icon me-3">
                                <i class="bi bi-info-circle-fill"></i>
                            </div>
                            <div class="alert-content">
                                <strong>Información:</strong> Esta licencia será marcada como "USADA" y quedará registrada permanentemente en el sistema.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light-subtle border-0 p-4">
                    <div class="d-flex justify-content-between w-100">
                        <button type="button"
                            class="btn btn-outline-secondary btn-modern px-4"
                            data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>
                            Cancelar
                        </button>
                        <button type="submit"
                            class="btn btn-success btn-modern px-4"
                            id="btnGuardar">
                            <span class="spinner-border spinner-border-sm me-2 d-none" id="loadingSpinner"></span>
                            <i class="bi bi-save me-2" id="saveIcon"></i>
                            <span id="btnText">Guardar Licencia</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<x-licencias.nueva.modal-defectuosa />

<script>
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: '¡Exito!',
        text: '{{ session('
        success ') }}',
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
    });
    @endif

    @if($errors - > any())
    Swal.fire({
        icon: 'error',
        title: 'Error al procesar',
        html: `{!! implode('<br>', $errors->all()) !!}`,
        confirmButtonColor: '#dc3545'
    });
    @endif
</script>
<script>
    function abrirModalLicenciaDefectuosa(actionUrl, ordenCompra, idProveedor, razonSocialProveedor) {
        const form = document.getElementById('formLicenciaDefectuosa');
        form.reset();
        form.action = actionUrl;

        form.querySelector('input[name="orden"]').value = ordenCompra ?? '';
        form.querySelector('input[name="razSocialProveedor"]').value = razonSocialProveedor ?? '';

        let hiddenIdField = form.querySelector('input[name="idProveedor"]');
        if (!hiddenIdField) {
            hiddenIdField = document.createElement('input');
            hiddenIdField.type = 'hidden';
            hiddenIdField.name = 'idProveedor';
            form.appendChild(hiddenIdField);
        }
        hiddenIdField.value = idProveedor ?? '';

        const modal = new bootstrap.Modal(document.getElementById('modalLicenciaDefectuosa'));
        modal.show();

        setTimeout(() => {
            form.querySelector('input[name="clave_key"]').focus();
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formLicenciaDefectuosa');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const claveKey = form.querySelector('input[name="clave_key"]').value.trim();
            const numeroTicket = form.querySelector('input[name="numero_ticket"]').value.trim();

            if (!claveKey) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo Requerido',
                    text: 'La Clave de Activación es obligatoria',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#e74c3c'
                });
                form.querySelector('input[name="clave_key"]').focus();
                return;
            }

            if (claveKey.length < 8) {
                Swal.fire({
                    icon: 'error',
                    title: 'Clave Inválida',
                    text: 'La clave debe tener al menos 8 caracteres',
                    confirmButtonText: 'Corregir',
                    confirmButtonColor: '#e74c3c'
                });
                form.querySelector('input[name="clave_key"]').focus();
                return;
            }
            if (!numeroTicket) {
                Swal.fire({
                    icon: 'question',
                    title: '¿Continuar sin Ticket?',
                    text: 'No se ha ingresado un número de ticket. ¿Desea continuar?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f39c12',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        confirmarAccion();
                    }
                });
                return;
            }
            confirmarAccion();
        });

        function confirmarAccion() {
            const claveKey = form.querySelector('input[name="clave_key"]').value.trim();

            Swal.fire({
                icon: 'warning',
                title: 'Confirmar Acción',
                html: `¿Está seguro de marcar la licencia <strong>${claveKey}</strong> como defectuosa?<br><br><small class="text-muted">Esta acción no se puede deshacer</small>`,
                showCancelButton: true,
                confirmButtonText: 'Sí, marcar como defectuosa',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Marcando licencia como defectuosa',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    form.submit();
                }
            });
        }
    });
    // ===============================
    // Variables globales
    // ===============================
    let licenciaPendiente = null;


    // ===============================
    // Abrir modal usar licencia
    // ===============================
    function abrirModalUsarLicencia(actionUrl, ordenCompra, modo = null) {

        const form = document.getElementById('formUsarLicencia');
        const hiddenModo = document.getElementById('modo_uso_hidden');

        if (!form || !hiddenModo) {
            console.error('No se encontró el formulario o el input hidden.');
            return;
        }

        form.reset();
        form.action = actionUrl;

        const ordenInput = form.querySelector('input[name="orden"]');
        if (ordenInput) {
            ordenInput.value = ordenCompra ?? '';
        }

        // 👉 UNIFUNCIONAL
        if (modo === null) {
            hiddenModo.value = 'COMPLETO';
        }
        // 👉 MULTIFUNCIONAL
        else {
            hiddenModo.value = modo;
        }

        const modalElement = document.getElementById('modalUsarLicencia');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }


    // ===============================
    // Flujo decisión multifuncional
    // ===============================
    function usarLicenciaDecision(data) {

        licenciaPendiente = data;

        if (!data.multifuncional) {
            abrirModalUsarLicencia(data.url, data.orden);
            return;
        }

        const modal = new bootstrap.Modal(
            document.getElementById('modalDecisionUso')
        );

        modal.show();
    }


    function confirmarUso(modo) {

        bootstrap.Modal
            .getInstance(document.getElementById('modalDecisionUso'))
            .hide();

        abrirModalUsarLicencia(
            licenciaPendiente.url,
            licenciaPendiente.orden,
            modo
        );
    }


    // ===============================
    // Submit del formulario
    // ===============================
    document.getElementById('formUsarLicencia')
        .addEventListener('submit', function(e) {

            e.preventDefault();

            const claveInput = document.getElementById('clave_key');
            const hiddenModo = document.getElementById('modo_uso_hidden');
            const claveValue = claveInput.value.trim();

            const btnGuardar = document.getElementById('btnGuardar');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const saveIcon = document.getElementById('saveIcon');
            const btnText = document.getElementById('btnText');

            if (claveValue === '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: 'La Clave de Activación es obligatoria.',
                    confirmButtonColor: '#667eea'
                }).then(() => claveInput.focus());
                return;
            }

            if (!hiddenModo.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tipo de uso requerido',
                    text: 'Debe seleccionar si el uso es parcial o completo.',
                    confirmButtonColor: '#667eea'
                });
                return;
            }

            Swal.fire({
                title: '¿Confirmar registro?',
                text: 'Se registrará esta licencia como usada',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (result.isConfirmed) {

                    btnGuardar.disabled = true;
                    loadingSpinner.classList.remove('d-none');
                    saveIcon.classList.add('d-none');
                    btnText.textContent = 'Guardando...';

                    this.submit();
                }
            });
        });


    // ===============================
    // Reset al cerrar modal
    // ===============================
    document.getElementById('modalUsarLicencia')
        .addEventListener('hidden.bs.modal', function() {

            const form = document.getElementById('formUsarLicencia');
            const btnGuardar = document.getElementById('btnGuardar');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const saveIcon = document.getElementById('saveIcon');
            const btnText = document.getElementById('btnText');

            form.reset();

            document.getElementById('modo_uso_hidden').value = '';

            btnGuardar.disabled = false;
            loadingSpinner.classList.add('d-none');
            saveIcon.classList.remove('d-none');
            btnText.textContent = 'Guardar Licencia';
        });


    // ===============================
    // Botones usar licencia
    // ===============================
    document.addEventListener('click', function(e) {

        const btn = e.target.closest('.btn-usar-licencia');
        if (!btn) return;

        const data = {
            url: btn.dataset.url,
            orden: btn.dataset.orden,
            multifuncional: btn.dataset.multifuncional == "1"
        };

        usarLicenciaDecision(data);
    });
</script>
@endsection