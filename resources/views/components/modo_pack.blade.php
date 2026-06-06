{{-- Modo Pack --}}
@if($producto->GrupoProducto && $producto->GrupoProducto->nombreGrupo === 'Cabezales' && $producto->GrupoProducto->CategoriaProducto && $producto->GrupoProducto->CategoriaProducto->nombreCategoria === 'Impresoras')
<div class="row border shadow rounded-3 pt-3 pb-3 mb-3 mt-3">
    <div class="col-12 mb-2">
        <h3>📦 Configuración de Componentes (Modo Pack)</h3>
    </div>
    <div class="col-12">
        <p class="text-muted">Si este producto se vende o divide como un "Pack", agrega sus componentes aquí. Cuando ingreses este producto en el futuro, podrás dividirlo según esta configuración.</p>
        
        <div class="row align-items-end mb-3">
            <div class="col-md-5 position-relative">
                <label class="form-label">Buscar producto componente:</label>
                <input type="text" class="form-control" id="search-component-input" placeholder="Escribe el modelo o código y selecciona de la lista...">
                <div id="search-component-results" class="list-group position-absolute w-100 shadow" style="z-index: 1000; max-height: 200px; overflow-y: auto; display: none;">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Costo (%):</label>
                <input type="number" class="form-control" id="porcentaje-costo-input" value="0" min="0" max="100" step="0.01">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cantidad por pack:</label>
                <input type="number" class="form-control" id="cantidad-component-input" value="1" min="1">
            </div>
            <div class="col-md-2 text-end">
                <input type="hidden" id="selected-component-id">
                <button type="button" class="btn btn-success w-100" id="btn-add-component" onclick="addPackComponent()" disabled>Agregar <i class="bi bi-plus-circle"></i></button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Componente Hijo</th>
                        <th width="120" class="text-center">% Costo</th>
                        <th width="150" class="text-center">Cant. por Pack</th>
                        <th width="100" class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="pack-components-list">
                    <tr>
                        <td colspan="3" class="text-center text-muted">Cargando componentes...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
{{-- Fin Modo Pack --}}
