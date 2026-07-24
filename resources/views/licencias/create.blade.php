@extends('layouts.app')

@section('title', 'Registrar Licencia')

@section('content')
<link rel="stylesheet" href="{{ asset(path:'css/licencias/create-licencias.css') }}">
<div class="register-container">
    <div class="register-panel">
        <!-- Header del Formulario -->
        <div class="form-header">
            <div class="header-icon">
                <i class="bi bi-plus-circle-fill"></i>
            </div>
            <div class="header-content">
                <h3 class="form-title">Registrar Nueva Licencia</h3>
                <p class="form-subtitle">Ingrese los datos de la licencia en el sistema de inventario</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="form-body">
            <form action="{{ route('licencias.store') }}" method="POST" id="licenseForm">
                @csrf

                <!-- Código de Licencia -->
                <div class="form-group">
                    <label for="voucher_code" class="form-label">
                        <i class="bi bi-qr-code label-icon"></i>
                        Código de Licencia
                        <span class="required-indicator">*</span>
                    </label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <input type="text"
                               name="voucher_code"
                               id="voucher_code"
                               value="{{ old('voucher_code') }}"
                               class="form-input @error('voucher_code') is-invalid @enderror"
                               placeholder="Ej: R1OV67FVN23UDSZYCX"
                               autocomplete="off">
                        <div class="input-border"></div>
                    </div>
                    <small class="input-help">Ingrese el código único de la licencia</small>
                </div>

                <!-- Orden de Compra -->
                <div class="form-group">
                    <label for="orden_compra" class="form-label">
                        <i class="bi bi-receipt label-icon"></i>
                        Orden de Compra
                        <span class="optional-indicator">(opcional)</span>
                    </label>
                    <div class="input-container">
                        <div class="input-icon">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <input type="text"
                               name="orden_compra"
                               id="orden_compra"
                               value="{{ old('orden_compra') }}"
                               class="form-input @error('orden_compra') is-invalid @enderror"
                               placeholder="N° de orden"
                               autocomplete="off">
                        <div class="input-border"></div>
                    </div>
                    <small class="input-help">Número de orden de compra asociada</small>
                </div>

                <!-- Tipo de Licencia -->
                <div class="form-group">
                    <label for="id_tipo" class="form-label">
                        <i class="bi bi-tags label-icon"></i>
                        Tipo de Licencia
                        <span class="required-indicator">*</span>
                    </label>
                    <div class="d-flex gap-2 align-items-start">
                        <div class="select-container flex-grow-1">
                            <div class="select-icon">
                                <i class="bi bi-collection"></i>
                            </div>
                            <select name="id_tipo"
                                    id="id_tipo"
                                    class="form-select @error('id_tipo') is-invalid @enderror">
                                <option value="">-- Seleccione el tipo de licencia --</option>
                                @foreach($tiposLicencia as $tipo)
                                    <option value="{{ $tipo->id }}" {{ old('id_tipo') == $tipo->id ? 'selected' : '' }}>
                                        {{ $tipo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="select-arrow">
                                <i class="bi bi-chevron-down"></i>
                            </div>
                            <div class="input-border"></div>
                        </div>
                        <div class="d-flex flex-column gap-1 mt-1">
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    title="Agregar nuevo tipo de licencia"
                                    onclick="abrirModalNuevoTipo()"
                                    style="height:42px; min-width:42px; border-radius:10px; font-size:1.3rem; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    title="Gestionar tipos de licencia"
                                    onclick="abrirModalGestionarTipos()"
                                    style="height:42px; min-width:42px; border-radius:10px; font-size:1.2rem; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-gear"></i>
                            </button>
                        </div>
                    </div>
                    <small class="input-help">Categoría de la licencia a registrar</small>
                </div>

                @include('licencias.modals.modal_nuevo_tipo')
                @include('licencias.modals.modal_gestionar_tipos')
                <!-- proveedor -->
                <div class="mb-3">
                    <label for="idProveedor" class="form-label">Proveedor</label>
                    <select name="idProveedor" id="idProveedor" class="form-select" required>
                        <option value="">-- Seleccione un proveedor --</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->idProveedor }}">
                                {{ $proveedor->nombreProveedor }} - {{ $proveedor->razSocialProveedor }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <!-- Categoria licencia -->
                <div class="mb-3">
                    <label for="id_categoria" class="form-label">Categoria</label>
                    <select name="id_categoria" id="id_categoria" class="form-select" required>
                        <option value="">-- Seleccione una Categoria --</option>
                        @foreach ($categoria as $cate)
                            <option value="{{ $cate->id_categoria }}"
                                    data-tipo="{{ $cate->tipo_categoria }}"
                                    {{ old('id_categoria') == $cate->id_categoria ? 'selected' : '' }}>
                                {{ $cate->tipo_categoria }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <!-- Cantida de usos - Multifuncional -->
                <div id="usos-container" class="mb-3 d-none">
                    <label for="cantidad_usos" class="form-label">
                        <i class="bi bi-123"></i> Cantidad máxima de usos
                        <span class="text-danger">*</span>
                    </label>

                    <div class="input-group has-validation">
                        <input type="number"
                            name="cantidad_usos"
                            id="cantidad_usos"
                            class="form-control"
                            min="1"
                            max="10"
                            step="1"
                            value="{{ old('cantidad_usos', 1) }}"
                            placeholder="Ingrese número de usos permitidos"
                            oninput="validarUsos(this)">
                        <span class="input-group-text">
                            <span id="usos-text">uso(s)</span>
                        </span>
                        <div id="usos-feedback" class="invalid-feedback">
                            Por favor ingrese un número entre 1 y 10
                        </div>
                    </div>

                    <div class="form-text d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Licencia válida para <span id="usos-preview" class="fw-bold">1</span> uso(s)
                        </small>
                        <small class="text-muted">
                            <span id="usos-restantes">10</span> usos disponibles
                        </small>
                    </div>

                    <!-- Barra de progreso visual -->
                    <div class="progress mt-2" style="height: 5px;">
                        <div id="usos-progress" class="progress-bar bg-success"
                            role="progressbar" style="width: 0.1%"></div>
                    </div>
                </div>
                <!-- Información Adicional -->
                <div class="info-panel">
                    <div class="info-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="info-content">
                        <strong>Información:</strong> La licencia será registrada con estado "DISPONIBLE" y podrá ser asignada posteriormente a equipos específicos.
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="form-actions">
                    <a href="{{ route('licencias.index') }}" class="btn-action btn-secondary">
                        <i class="bi bi-arrow-left"></i>
                        <span>Volver al Inventario</span>
                    </a>
                    <button type="submit" class="btn-action btn-primary">
                        <i class="bi bi-save"></i>
                        <span>Registrar Licencia</span>
                        <div class="btn-loading">
                            <div class="spinner"></div>
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Errores de validación de Laravel --}}
@if ($errors->any())
<script>
    let errorMessages = '';
    @foreach ($errors->all() as $error)
        errorMessages += '• {{ $error }}\n';
    @endforeach
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        html: `<pre style="text-align:left;">${errorMessages}</pre>`,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Cerrar'
    });
</script>
@endif

{{-- Inputs hidden para pasar las URL de los endpoints a los JS externos --}}
<input type="hidden" id="storeNuevoTipoUrl" value="{{ route('tiposLicencia.store') }}">
<input type="hidden" id="getAllTiposUrl" value="{{ route('tiposLicencia.all') }}">
<input type="hidden" id="toggleTipoUrl" value="{{ route('tiposLicencia.toggle') }}">

<script src="{{ asset('js/Licencias/create-licencias.js') }}"></script>
<script src="{{ asset('js/Licencias/create-licencias-tipo.js') }}"></script>
<script src="{{ asset('js/Licencias/create-licencias-gestionar-tipos.js') }}"></script>

@endsection
