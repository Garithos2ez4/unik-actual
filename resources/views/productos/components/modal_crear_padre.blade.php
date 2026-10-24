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

{{-- Modal Crear / Convertir Producto Padre --}}
<div class="modal fade text-start" id="modalCrearPadreComponent" tabindex="-1" aria-labelledby="modalCrearPadreComponentLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="modalCrearPadreComponentLabel">
                    <i class="bi bi-diagram-3-fill me-1"></i> Gestion de Contenedores Padre (Variantes)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                {{-- Navegacion por pestañas --}}
                <ul class="nav nav-pills nav-fill mb-3" id="pills-padre-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="pills-nuevo-padre-tab" data-bs-toggle="pill" data-bs-target="#pills-nuevo-padre" type="button" role="tab" aria-selected="true">
                            <i class="bi bi-plus-circle me-1"></i> 1. Crear Nuevo Contenedor Padre
                        </button>
                    </li>
                    @if (!$producto->es_padre)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="pills-convertir-padre-tab" data-bs-toggle="pill" data-bs-target="#pills-convertir-padre" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-arrow-repeat me-1"></i> 2. Convertir este Producto en Padre
                        </button>
                    </li>
                    @endif
                </ul>

                <div class="tab-content pt-2" id="pills-padre-tabContent">
                    
                    {{-- PESTAÑA 1: CREAR NUEVO PRODUCTO PADRE --}}
                    <div class="tab-pane fade show active" id="pills-nuevo-padre" role="tabpanel">
                        <form action="{{ route('producto.crear_padre') }}" method="POST">
                            @csrf
                            <input type="hidden" name="idProductoActual" value="{{ $producto->idProducto }}">
                            <input type="hidden" name="imagenProducto1" value="{{ $producto->imagenProducto1 }}">
                            <input type="hidden" name="precioDolar" value="{{ $producto->precioDolar }}">

                            <div class="alert alert-light border small text-muted mb-3 py-2">
                                <i class="bi bi-info-circle text-primary me-1"></i>
                                Un <strong>Producto Padre</strong> es un contenedor virtual que agrupa variantes de color/modelo. No recibe stock fisico directo; el stock y series pertenecen a sus variantes hijas.
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Titulo del Producto Padre (Generico):</label>
                                    <input type="text" name="nombreProducto" class="form-control" value="{{ $producto->nombreProducto }}" required maxlength="255">
                                    <small class="text-muted">Recomendado: Quitar el color especifico del titulo para que aplique a todas sus variantes.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Modelo Base (Sin sufijo de color):</label>
                                    <input type="text" name="modelo" class="form-control" value="{{ $producto->modelo }}" required maxlength="100">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Part Number Base:</label>
                                    <input type="text" name="partNumber" class="form-control" value="{{ $producto->partNumber ?: $producto->modelo }}" maxlength="100">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Marca:</label>
                                    <select name="idMarca" class="form-select" required>
                                        @foreach($marcas as $marca)
                                            <option value="{{ $marca['idMarca'] }}" {{ $producto->idMarca == $marca['idMarca'] ? 'selected' : '' }}>
                                                {{ $marca['nombreMarca'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Grupo / Categoria:</label>
                                    <select name="idGrupo" class="form-select" required>
                                        @foreach($grupos as $grupo)
                                            <option value="{{ $grupo['idGrupoProducto'] }}" {{ $producto->idGrupo == $grupo['idGrupoProducto'] ? 'selected' : '' }}>
                                                {{ $grupo['nombreGrupo'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Garantia:</label>
                                    <select name="garantia" class="form-select">
                                        <option value="12 meses" {{ $producto->garantia == '12 meses' ? 'selected' : '' }}>12 meses</option>
                                        <option value="24 meses" {{ $producto->garantia == '24 meses' ? 'selected' : '' }}>24 meses</option>
                                        <option value="36 meses" {{ $producto->garantia == '36 meses' ? 'selected' : '' }}>36 meses</option>
                                        <option value="6 meses" {{ $producto->garantia == '6 meses' ? 'selected' : '' }}>6 meses</option>
                                        <option value="3 meses" {{ $producto->garantia == '3 meses' ? 'selected' : '' }}>3 meses</option>
                                        <option value="No tiene" {{ $producto->garantia == 'No tiene' ? 'selected' : '' }}>No tiene</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Foto Inicial de Catalogo:</label>
                                    <input type="text" class="form-control bg-light" value="Hereda foto del producto actual" disabled readonly>
                                </div>
                            </div>

                            @if (!$producto->es_padre)
                            <hr class="my-3 text-muted">

                            {{-- Vincular producto actual --}}
                            <div class="bg-light p-3 rounded border">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="vincular_actual" id="checkVincularActual" value="1" checked onchange="toggleConfigVarianteActual(this.checked)">
                                    <label class="form-check-label fw-bold text-dark" for="checkVincularActual">
                                        Vincular inmediatamente este producto ({{ $producto->modelo }}) como su primera variante hija
                                    </label>
                                </div>

                                <div class="row g-2 mt-2 align-items-end" id="bloqueConfigVarianteActual">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold text-muted mb-1">Nombre del Color / Variante:</label>
                                        <input type="text" name="color_actual" id="inputNombreColorActual" class="form-control" placeholder="Ej. Negro, Gris, Blanco..." value="{{ optional($producto->DetalleProducto)->valor_variante ?: '' }}" oninput="autoDetectarColorPorTexto(this.value, 'pickerHexActual', 'inputHexActual')">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted mb-1">Color de la bolita:</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color p-1 shadow-xs" id="pickerHexActual" value="{{ optional($producto->DetalleProducto)->codigo_color_hex ?: '#1a1a1a' }}" style="width: 44px; height: 38px; cursor: pointer;" title="Haz clic para afinar el tono visual" oninput="document.getElementById('inputHexActual').value = this.value">
                                            <input type="hidden" name="color_hex_actual" id="inputHexActual" value="{{ optional($producto->DetalleProducto)->codigo_color_hex ?: '#1a1a1a' }}">
                                            <small class="text-muted" style="font-size: 0.72rem; line-height: 1.1;">Clic para afinar tono</small>
                                        </div>
                                    </div>

                                    {{-- Selector Rápido de Colores (1 clic) --}}
                                    <div class="col-12 mt-2">
                                        <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Colores sugeridos (1 clic):</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Negro', '#1a1a1a', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #1a1a1a; border: 1px solid #fff;"></span> Negro
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Blanco', '#ffffff', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ffffff; border: 1px solid #ccc;"></span> Blanco
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Gris', '#6c757d', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #6c757d; border: 1px solid #fff;"></span> Gris
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Plata', '#c0c0c0', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #c0c0c0; border: 1px solid #aaa;"></span> Plata
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Azul', '#0d6efd', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #0d6efd; border: 1px solid #fff;"></span> Azul
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Rojo', '#dc3545', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #dc3545; border: 1px solid #fff;"></span> Rojo
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Dorado', '#d4af37', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #d4af37; border: 1px solid #fff;"></span> Dorado
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" onclick="aplicarColorRapido('Rose Gold', '#e83e8c', 'inputNombreColorActual', 'pickerHexActual', 'inputHexActual')">
                                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #e83e8c; border: 1px solid #fff;"></span> Rose Gold
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success px-4 shadow">
                                    <i class="bi bi-check2-circle me-1"></i> Crear Contenedor Padre
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- PESTAÑA 2: CONVERTIR ESTE PRODUCTO EN PADRE --}}
                    @if (!$producto->es_padre)
                    <div class="tab-pane fade" id="pills-convertir-padre" role="tabpanel">
                        <div class="p-4 bg-light rounded border text-center">
                            <i class="bi bi-exclamation-circle text-warning fs-1 d-block mb-3"></i>
                            <h5 class="fw-bold">¿Convertir {{ $producto->modelo }} en Contenedor Padre?</h5>
                            <p class="text-muted small mb-4">
                                Al convertir este producto en contenedor padre, pasara a tener <code>es_padre = 1</code>.
                                Sera el agrupador de variantes en la tienda WebUnik y no admitira ingresos directos de inventario fisico en Logunk.
                                Despues podras vincularle sus variantes hijas de color.
                            </p>
                            <form action="{{ route('producto.convertir_en_padre', [encrypt($producto->idProducto)]) }}" method="POST">
                                @csrf
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-warning px-4 fw-bold shadow">
                                        <i class="bi bi-arrow-repeat me-1"></i> Si, Convertir en Contenedor Padre
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                </div>

            </div>
        </div>
    </div>
</div>

<script>
const DICCIONARIO_COLORES_RAPIDOS = {
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

function aplicarColorRapido(nombre, hex, inputNombreId, pickerId, hiddenHexId) {
    const inputNombre = document.getElementById(inputNombreId);
    const picker = document.getElementById(pickerId);
    const hiddenHex = document.getElementById(hiddenHexId);
    if (inputNombre) inputNombre.value = nombre;
    if (picker) picker.value = hex;
    if (hiddenHex) hiddenHex.value = hex;
}

function autoDetectarColorPorTexto(texto, pickerId, hiddenHexId) {
    const clean = texto.toLowerCase().trim();
    for (const [key, hex] of Object.entries(DICCIONARIO_COLORES_RAPIDOS)) {
        if (clean.includes(key)) {
            const picker = document.getElementById(pickerId);
            const hiddenHex = document.getElementById(hiddenHexId);
            if (picker) picker.value = hex;
            if (hiddenHex) hiddenHex.value = hex;
            break;
        }
    }
}

function toggleConfigVarianteActual(checked) {
    let bloque = document.getElementById('bloqueConfigVarianteActual');
    if (bloque) {
        bloque.style.display = checked ? 'flex' : 'none';
    }
}
</script>
