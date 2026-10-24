<div class="row border shadow rounded-3 pt-3 pb-3 mb-3 bg-white">
    <div class="mb-3 col-12 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0">
                <i class="bi bi-diagram-3 text-primary"></i> Sistema de Variantes (Colores / Modelos)
            </h3>
            <small class="text-muted">Gestiona el contenedor padre y sus variantes hijas asociadas</small>
        </div>
        @if ($producto->es_padre)
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="bi bi-layers-fill me-1"></i> PRODUCTO CONTENEDOR PADRE
        </span>
        @elseif ($producto->idProductoPadre)
        <span class="badge bg-info text-dark fs-6 px-3 py-2">
            <i class="bi bi-tag-fill me-1"></i> VARIANTE HIJA
        </span>
        @else
        <span class="badge bg-secondary fs-6 px-3 py-2">
            <i class="bi bi-box me-1"></i> PRODUCTO INDIVIDUAL
        </span>
        @endif
    </div>

    {{-- CASO 1: ES PRODUCTO PADRE --}}
    @if ($producto->es_padre)
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-secondary mb-0">
                Variantes Hijas Vinculadas ({{ $producto->variantes->count() }})
            </h5>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalVincularVariante">
                <i class="bi bi-plus-circle me-1"></i> Vincular Variante Existente
            </button>
        </div>

        @if ($producto->variantes->isEmpty())
        <div class="alert alert-warning py-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Este producto esta marcado como contenedor Padre pero aun no tiene variantes hijas vinculadas.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Color / Variante</th>
                        <th>Modelo</th>
                        <th>Nombre Producto</th>
                        <th class="text-center">Stock Fisico</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($producto->variantes as $idx => $variante)
                    @php
                    $det = $variante->DetalleProducto;
                    $colorHex = $det->codigo_color_hex ?? '#6c757d';
                    $colorNombre = $det->valor_variante ?? 'Variante ' . ($idx + 1);
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block rounded-circle border shadow-sm" style="width: 20px; height: 20px; background-color: {{ $colorHex }};"></span>
                                <strong>{{ $colorNombre }}</strong>
                                <small class="text-muted">({{ $colorHex }})</small>
                            </div>
                        </td>
                        <td><code>{{ $variante->modelo }}</code></td>
                        <td>
                            <a href="{{ route('producto', [encrypt($variante->idProducto)]) }}" class="text-decoration-none text-dark fw-semibold">
                                {{ $variante->nombreProducto }}
                            </a>
                            <small class="text-muted d-block">ID: {{ $variante->idProducto }} | Codigo: {{ $variante->codigoProducto }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $variante->stockTotal() > 0 ? 'bg-success' : 'bg-secondary' }} fs-6">
                                {{ $variante->stockTotal() }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('producto', [encrypt($variante->idProducto)]) }}" class="btn btn-outline-primary btn-sm me-1" title="Ver producto">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form action="{{ route('producto.desvincular_variante', [encrypt($variante->idProducto)]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro de desvincular esta variante del producto padre?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Desvincular del padre">
                                    <i class="bi bi-x-circle"></i> Desvincular
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- CASO 2: ES VARIANTE HIJA --}}
    @elseif ($producto->idProductoPadre && $producto->padre)
    <div class="col-12">
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-0">
            <div>
                <h5 class="alert-heading mb-1">
                    <i class="bi bi-link-45deg"></i> Variante de:
                    <a href="{{ route('producto', [encrypt($producto->padre->idProducto)]) }}" class="fw-bold text-decoration-underline text-dark">
                        {{ $producto->padre->nombreProducto }} ({{ $producto->padre->modelo }})
                    </a>
                </h5>
                <div class="mt-2 d-flex align-items-center gap-2">
                    <span>Color configurado:</span>
                    <span class="d-inline-block rounded-circle border shadow-sm" style="width: 18px; height: 18px; background-color: {{ optional($producto->DetalleProducto)->codigo_color_hex ?? '#6c757d' }};"></span>
                    <strong>{{ optional($producto->DetalleProducto)->valor_variante ?: 'Sin color' }}</strong>
                    <span class="text-muted">({{ optional($producto->DetalleProducto)->codigo_color_hex ?: '#------' }})</span>
                </div>
            </div>
            <div>
                <form action="{{ route('producto.desvincular_variante', [encrypt($producto->idProducto)]) }}" method="POST" onsubmit="return confirm('¿Seguro de desvincular este producto de su contenedor padre?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm shadow-sm">
                        <i class="bi bi-unlink"></i> Desvincular del Padre
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- CASO 3: ES PRODUCTO INDIVIDUAL (SIMPLE) --}}
    @else
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded border">
            <div>
                <p class="mb-0 text-muted">
                    Este producto no esta asociado a ninguna familia de variantes. Puedes vincularlo como hijo a un producto padre existente.
                </p>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalVincularAPadre">
                <i class="bi bi-link me-1"></i> Vincular a un Contenedor Padre
            </button>
        </div>
    </div>
    @endif
</div>

{{-- MODAL PARA VINCULAR VARIANTE (DESDE LA VISTA DEL PADRE) --}}
@if ($producto->es_padre)
<div class="modal fade" id="modalVincularVariante" tabindex="-1" aria-labelledby="modalVincularVarianteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('producto.vincular_variante', [encrypt($producto->idProducto)]) }}" method="POST">
            @csrf
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalVincularVarianteLabel">
                        <i class="bi bi-diagram-3 me-1"></i> Vincular Variante al Padre {{ $producto->modelo }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">1. Buscar Producto Hijo (por Modelo o Nombre):</label>
                        <input type="text" class="form-control form-control-lg" id="inputBuscarHijo" placeholder="Escribe el modelo (ej. TE-8038N)..." autocomplete="off" oninput="buscarProductoParaVariante(this.value, 'listaSugerenciasHijo', 'idProductoHijoSeleccionado', 'textoHijoSeleccionado')">
                        <ul class="list-group shadow w-100 position-absolute" id="listaSugerenciasHijo" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></ul>
                    </div>

                    <div class="alert alert-secondary py-2 mb-3">
                        <span class="text-muted">Producto Seleccionado:</span>
                        <strong id="textoHijoSeleccionado" class="text-primary d-block">Ninguno seleccionado</strong>
                        <input type="hidden" name="idProductoHijo" id="idProductoHijoSeleccionado" required>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">2. Nombre del Color / Variante:</label>
                            <input type="text" name="color" id="inputColorModalHijo" class="form-control" placeholder="Ej. Negro, Gris, Rose Gold, Blanco..." required oninput="autoDetectarColorPorTexto(this.value, 'pickerColorHex', 'inputColorHex')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">3. Color de la bolita:</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" class="form-control form-control-color p-1 shadow-xs" id="pickerColorHex" value="#1a1a1a" style="width: 44px; height: 38px; cursor: pointer;" title="Haz clic para afinar el tono visual" oninput="document.getElementById('inputColorHex').value = this.value">
                                <input type="hidden" name="color_hex" id="inputColorHex" value="#1a1a1a" required>
                                <small class="text-muted" style="font-size: 0.72rem; line-height: 1.1;">Clic para afinar tono</small>
                            </div>
                        </div>

                        {{-- Selector Rápido de Colores (1 clic) --}}
                        <div class="col-12 mt-1">
                            <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Colores sugeridos (1 clic):</small>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Negro', '#1a1a1a', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #1a1a1a; border: 1px solid #fff;"></span> Negro
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Blanco', '#ffffff', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ffffff; border: 1px solid #ccc;"></span> Blanco
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Gris', '#6c757d', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #6c757d; border: 1px solid #fff;"></span> Gris
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Plata', '#c0c0c0', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #c0c0c0; border: 1px solid #aaa;"></span> Plata
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Azul', '#0d6efd', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #0d6efd; border: 1px solid #fff;"></span> Azul
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Rojo', '#dc3545', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #dc3545; border: 1px solid #fff;"></span> Rojo
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Dorado', '#d4af37', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #d4af37; border: 1px solid #fff;"></span> Dorado
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Rose Gold', '#e83e8c', 'inputColorModalHijo', 'pickerColorHex', 'inputColorHex')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #e83e8c; border: 1px solid #fff;"></span> Rose Gold
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4" id="btnConfirmarVincularHijo" disabled>
                        <i class="bi bi-check2-circle me-1"></i> Vincular como Variante
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

{{-- MODAL PARA VINCULAR ESTE PRODUCTO A UN PADRE (DESDE LA VISTA INDIVIDUAL) --}}
@if (!$producto->es_padre && !$producto->idProductoPadre)
<div class="modal fade" id="modalVincularAPadre" tabindex="-1" aria-labelledby="modalVincularAPadreLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="" id="formVincularAPadre" method="POST">
            @csrf
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalVincularAPadreLabel">
                        <i class="bi bi-diagram-3 me-1"></i> Vincular {{ $producto->modelo }} a un Contenedor Padre
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="idProductoHijo" value="{{ $producto->idProducto }}">

                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">1. Buscar Contenedor Padre (por Modelo o Nombre):</label>
                        <input type="text" class="form-control form-control-lg" id="inputBuscarPadre" placeholder="Escribe el modelo padre..." autocomplete="off" oninput="buscarPadreParaVariante(this.value)">
                        <ul class="list-group shadow w-100 position-absolute" id="listaSugerenciasPadre" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></ul>
                    </div>

                    <div class="alert alert-secondary py-2 mb-3">
                        <span class="text-muted">Padre Seleccionado:</span>
                        <strong id="textoPadreSeleccionado" class="text-primary d-block">Ninguno seleccionado</strong>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">2. Nombre del Color para este producto:</label>
                            <input type="text" name="color" id="inputColorModalPadre" class="form-control" placeholder="Ej. Negro, Titanio, Azul..." required oninput="autoDetectarColorPorTexto(this.value, 'pickerColorHexPadre', 'inputColorHexPadre')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">3. Color de la bolita:</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" class="form-control form-control-color p-1 shadow-xs" id="pickerColorHexPadre" value="#1a1a1a" style="width: 44px; height: 38px; cursor: pointer;" title="Haz clic para afinar el tono visual" oninput="document.getElementById('inputColorHexPadre').value = this.value">
                                <input type="hidden" name="color_hex" id="inputColorHexPadre" value="#1a1a1a" required>
                                <small class="text-muted" style="font-size: 0.72rem; line-height: 1.1;">Clic para afinar tono</small>
                            </div>
                        </div>

                        {{-- Selector Rápido de Colores (1 clic) --}}
                        <div class="col-12 mt-1">
                            <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Colores sugeridos (1 clic):</small>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Negro', '#1a1a1a', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #1a1a1a; border: 1px solid #fff;"></span> Negro
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Blanco', '#ffffff', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ffffff; border: 1px solid #ccc;"></span> Blanco
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Gris', '#6c757d', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #6c757d; border: 1px solid #fff;"></span> Gris
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Plata', '#c0c0c0', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #c0c0c0; border: 1px solid #aaa;"></span> Plata
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Azul', '#0d6efd', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #0d6efd; border: 1px solid #fff;"></span> Azul
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Rojo', '#dc3545', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #dc3545; border: 1px solid #fff;"></span> Rojo
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Dorado', '#d4af37', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #d4af37; border: 1px solid #fff;"></span> Dorado
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Rose Gold', '#e83e8c', 'inputColorModalPadre', 'pickerColorHexPadre', 'inputColorHexPadre')">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #e83e8c; border: 1px solid #fff;"></span> Rose Gold
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4" id="btnConfirmarVincularPadre" disabled>
                        <i class="bi bi-check2-circle me-1"></i> Vincular a este Padre
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<script>
    if (typeof DICCIONARIO_COLORES_RAPIDOS === 'undefined') {
        window.DICCIONARIO_COLORES_RAPIDOS = {
            'negro': '#1a1a1a',
            'black': '#1a1a1a',
            'blanco': '#ffffff',
            'white': '#ffffff',
            'gris': '#6c757d',
            'gray': '#6c757d',
            'plata': '#c0c0c0',
            'silver': '#c0c0c0',
            'azul': '#0d6efd',
            'blue': '#0d6efd',
            'rojo': '#dc3545',
            'red': '#dc3545',
            'verde': '#198754',
            'green': '#198754',
            'dorado': '#d4af37',
            'gold': '#d4af37',
            'rosa': '#e83e8c',
            'pink': '#e83e8c',
            'rose': '#e83e8c',
            'amarillo': '#ffc107',
            'yellow': '#ffc107',
            'naranja': '#fd7e14',
            'orange': '#fd7e14',
            'morado': '#6f42c1',
            'purpura': '#6f42c1',
            'titanio': '#878681'
        };
    }

    if (typeof aplicarColorRapido === 'undefined') {
        window.aplicarColorRapido = function(nombre, hex, inputNombreId, pickerId, hiddenHexId) {
            const inputNombre = document.getElementById(inputNombreId);
            const picker = document.getElementById(pickerId);
            const hiddenHex = document.getElementById(hiddenHexId);
            if (inputNombre) inputNombre.value = nombre;
            if (picker) picker.value = hex;
            if (hiddenHex) hiddenHex.value = hex;
        };
    }

    if (typeof autoDetectarColorPorTexto === 'undefined') {
        window.autoDetectarColorPorTexto = function(texto, pickerId, hiddenHexId) {
            const clean = texto.toLowerCase().trim();
            for (const [key, hex] of Object.entries(window.DICCIONARIO_COLORES_RAPIDOS)) {
                if (clean.includes(key)) {
                    const picker = document.getElementById(pickerId);
                    const hiddenHex = document.getElementById(hiddenHexId);
                    if (picker) picker.value = hex;
                    if (hiddenHex) hiddenHex.value = hex;
                    break;
                }
            }
        };
    }

    function buscarProductoParaVariante(query, ulId, hiddenId, textId) {
        let ul = document.getElementById(ulId);
        if (!ul) return;
        if (query.length < 2) {
            ul.innerHTML = '';
            return;
        }
        fetch(`/productos/searchmodelproduct?query=${encodeURIComponent(query)}&vendibles=1`)
            .then(r => r.json())
            .then(data => {
                ul.innerHTML = '';
                const currentId = {{ (int) $producto->idProducto }};
                data.forEach(item => {
                    if (item.idProducto === currentId) return;
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'list-group-item-action', 'cursor-pointer');
                    li.innerHTML = `<strong>${item.modelo}</strong> - <small class="text-muted">${item.nombreProducto || ''}</small>`;
                    li.onclick = function() {
                        document.getElementById(hiddenId).value = item.idProducto;
                        document.getElementById(textId).textContent = `${item.modelo} - ${item.nombreProducto || ''} (ID: ${item.idProducto})`;
                        document.getElementById('btnConfirmarVincularHijo').disabled = false;
                        ul.innerHTML = '';
                    };
                    ul.appendChild(li);
                });
            });
    }

    function buscarPadreParaVariante(query) {
        let ul = document.getElementById('listaSugerenciasPadre');
        if (!ul) return;
        if (query.length < 2) {
            ul.innerHTML = '';
            return;
        }
        fetch(`/productos/searchmodelproduct?query=${encodeURIComponent(query)}&solo_padres=1`)
            .then(r => r.json())
            .then(data => {
                ul.innerHTML = '';
                const currentId = {{ (int) $producto->idProducto }};
                data.forEach(item => {
                    if (item.idProducto === currentId) return;
                    let li = document.createElement('li');
                    li.classList.add('list-group-item', 'list-group-item-action', 'cursor-pointer');
                    li.innerHTML = `<strong>${item.modelo}</strong> ${item.es_padre ? '<span class="badge bg-warning text-dark ms-1">PADRE</span>' : ''} - <small class="text-muted">${item.nombreProducto || ''}</small>`;
                    li.onclick = function() {
                        document.getElementById('formVincularAPadre').action = `/producto/${encodeURIComponent(item.idProducto)}/vincular-variante`;
                        document.getElementById('textoPadreSeleccionado').textContent = `${item.modelo} - ${item.nombreProducto || ''} (ID: ${item.idProducto})`;
                        document.getElementById('btnConfirmarVincularPadre').disabled = false;
                        ul.innerHTML = '';
                    };
                    ul.appendChild(li);
                });
            });
    }

    document.addEventListener('click', function(e) {
        const listHijo = document.getElementById('listaSugerenciasHijo');
        const inputHijo = document.getElementById('inputBuscarHijo');
        if (listHijo && inputHijo && !listHijo.contains(e.target) && e.target !== inputHijo) {
            listHijo.innerHTML = '';
        }
        const listPadre = document.getElementById('listaSugerenciasPadre');
        const inputPadre = document.getElementById('inputBuscarPadre');
        if (listPadre && inputPadre && !listPadre.contains(e.target) && e.target !== inputPadre) {
            listPadre.innerHTML = '';
        }
    });
</script>