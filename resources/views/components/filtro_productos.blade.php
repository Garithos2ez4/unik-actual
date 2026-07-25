@props(['marcas' => [], 'estados' => [], 'almacenes' => []])

<form action="{{ request()->url() }}" method="get" id="form-filtro-componente">
    @foreach(request()->except('filtro') as $key => $value)
        @if(is_array($value))
            @foreach($value as $k => $v)
                <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <div class="row mb-2 mt-3">
        <div class="col-4 col-lg-2">
            <small>Marca</small>
            <select class="form-select form-select-sm filtro-componente" name="filtro[marca]">
                <option value="">Todos</option>
                @foreach ($marcas as $marca)
                <option value="{{ data_get($marca, 'idMarca') ?? data_get($marca, 'id') ?? '' }}">
                    {{ data_get($marca, 'MarcaProducto.nombreMarca') ?? data_get($marca, 'nombreMarca') ?? data_get($marca, 'nombre') ?? '' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-lg-2">
            <small>Estado</small>
            <select class="form-select form-select-sm filtro-componente" name="filtro[estado]"
                {{ request()->input('filtro.estado') ? 'data-selected="'.request()->input('filtro.estado').'"' : '' }}>
                <option value="">Todos</option>
                <option value="ACTIVO" {{ request()->input('filtro.estado') == 'ACTIVO' ? 'selected' : '' }}
                    style="font-weight:bold;color:#198754;">✔ Activos (con stock)</option>
                <option value="INACTIVO" {{ request()->input('filtro.estado') == 'INACTIVO' ? 'selected' : '' }}
                    style="font-weight:bold;color:#dc3545;">✖ Inactivos (agotado/descont.)</option>
                @if(count($estados) > 0)
                <option disabled>── Estados específicos ──</option>
                @endif
                @foreach ($estados as $estado)
                <option value="{{ data_get($estado, 'estadoProductoWeb') ?? data_get($estado, 'id') ?? '' }}"
                    {{ request()->input('filtro.estado') == (data_get($estado, 'estadoProductoWeb') ?? data_get($estado, 'id') ?? '') ? 'selected' : '' }}>
                    {{ data_get($estado, 'estadoProductoWeb') ?? data_get($estado, 'nombre') ?? '' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-lg-2">
            <small>Almacén</small>
            <select class="form-select form-select-sm filtro-componente" name="filtro[almacen]" id="select-filtro-almacen">
                <option value="">Todos</option>
                @foreach ($almacenes as $almacen)
                <option value="{{ $almacen->idAlmacen }}" {{ request()->input('filtro.almacen') == $almacen->idAlmacen ? 'selected' : '' }}>
                    {{ $almacen->descripcion }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-lg-2" id="div-filtro-rack" style="display: {{ request()->input('filtro.almacen') ? 'block' : 'none' }};">
            <small>Rack/Estante</small>
            <select class="form-select form-select-sm filtro-componente" name="filtro[rack]" id="select-filtro-rack">
                <option value="">Todos los Racks</option>
                @foreach ($almacenes as $almacen)
                    @foreach ($almacen->Ubicaciones as $rack)
                        <option value="{{ $rack->idUbicacion }}" data-almacen="{{ $almacen->idAlmacen }}" style="display: none;" {{ request()->input('filtro.rack') == $rack->idUbicacion ? 'selected' : '' }}>
                            {{ $rack->nombre }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAlmacen = document.getElementById('select-filtro-almacen');
        const selectRack = document.getElementById('select-filtro-rack');
        const divRack = document.getElementById('div-filtro-rack');

        function updateRacks() {
            const almacenId = selectAlmacen.value;
            
            // Mostrar u ocultar el div del Rack
            if (almacenId) {
                divRack.style.display = 'block';
            } else {
                divRack.style.display = 'none';
                selectRack.value = "";
            }

            // Filtrar las opciones del Rack
            let hasValidOptions = false;
            Array.from(selectRack.options).forEach(option => {
                if (option.value === "") return; // Option "Todos"
                
                if (option.getAttribute('data-almacen') === almacenId) {
                    option.style.display = 'block';
                    hasValidOptions = true;
                } else {
                    option.style.display = 'none';
                    // Si la opción seleccionada actualmente se oculta, resetear a "Todos"
                    if (option.selected) {
                        selectRack.value = "";
                    }
                }
            });
        }

        if (selectAlmacen) {
            // No agregamos 'change' a filterSubmit aquí, lo dejamos a filtro_componente.js
            // Solo escuchamos el evento para cambiar la UI antes del submit
            selectAlmacen.addEventListener('change', updateRacks);
            
            // Inicializar al cargar
            updateRacks();
        }
    });
</script>