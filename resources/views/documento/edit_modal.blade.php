<!-- Modal Editar Comprobante -->
<form action="{{ route('editcomprobante') }}" method="POST" id="form-edit-comprobante">
    @csrf
    <input type="hidden" name="idComprobante" value="{{ $documento->idComprobante }}">
    <div class="modal fade" id="editComprobanteModal" tabindex="-1" aria-labelledby="editComprobanteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold text-dark" id="editComprobanteModalLabel">
                        <i class="bi bi-pencil-square me-2"></i> Editar Comprobante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <!-- Sección 1: Todos los Campos de Cabecera del Comprobante -->
                    <div class="card border-0 bg-light mb-4 shadow-sm">
                        <div class="card-header bg-white border-bottom-0 py-3">
                            <h6 class="fw-bold mb-0 text-primary">
                                <i class="bi bi-file-earmark-text me-1"></i> 1. Campos de la Cabecera del Comprobante
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Proveedor -->
                                <div class="col-md-6 col-lg-5">
                                    <label class="form-label fw-bold">Proveedor (idProveedor):</label>
                                    <select class="form-select" name="idProveedor" id="edit-comprobante-proveedor" required>
                                        @foreach($proveedores ?? [] as $prov)
                                            <option value="{{ $prov->idProveedor }}" {{ $documento->idProveedor == $prov->idProveedor ? 'selected' : '' }}>
                                                {{ $prov->razSocialProveedor ?: $prov->nombreProveedor }} (RUC: {{ $prov->rucProveedor }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tipo de Comprobante -->
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold">Tipo Comprobante (idTipoComprobante):</label>
                                    <select class="form-select" name="idTipoComprobante" required>
                                        @foreach($tiposComprobante ?? [] as $tipo)
                                            <option value="{{ $tipo->idTipoComprobante }}" {{ $documento->idTipoComprobante == $tipo->idTipoComprobante ? 'selected' : '' }}>
                                                {{ $tipo->descripcion }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- N° de Comprobante -->
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label fw-bold">N° Comprobante (numeroComprobante):</label>
                                    <input type="text" name="numeroComprobante" class="form-control fw-bold" value="{{ $documento->numeroComprobante }}" required placeholder="Ej: F001-00012345">
                                </div>

                                <!-- Fecha de Emisión / Registro -->
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label fw-bold">Fecha de Registro (fechaRegistro):</label>
                                    <input type="date" name="fechaRegistro" class="form-control" value="{{ $documento->fechaRegistro ? $documento->fechaRegistro->format('Y-m-d') : '' }}" required>
                                </div>

                                <!-- Moneda -->
                                <div class="col-md-4 col-lg-2">
                                    <label class="form-label fw-bold">Moneda (moneda):</label>
                                    <select class="form-select" name="moneda" id="edit-comprobante-moneda" required onchange="cambiarSimboloMoneda(this.value)">
                                        <option value="SOL" {{ $documento->moneda == 'SOL' ? 'selected' : '' }}>Soles (SOL)</option>
                                        <option value="DOLAR" {{ $documento->moneda == 'DOLAR' ? 'selected' : '' }}>Dólares (DOLAR)</option>
                                    </select>
                                </div>

                                <!-- Adquisición -->
                                <div class="col-md-4 col-lg-2">
                                    <label class="form-label fw-bold">Adquisición (adquisicion):</label>
                                    <select class="form-select" name="adquisicion">
                                        <option value="NORMAL" {{ ($documento->adquisicion ?? 'NORMAL') == 'NORMAL' ? 'selected' : '' }}>Normal</option>
                                        <option value="OFERTA" {{ ($documento->adquisicion ?? '') == 'OFERTA' ? 'selected' : '' }}>Oferta</option>
                                    </select>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-4 col-lg-2">
                                    <label class="form-label fw-bold">Estado (estado):</label>
                                    <select class="form-select" name="estado">
                                        @foreach($estadosComprobante ?? ['REGISTRADO', 'PENDIENTE', 'INVALIDO', 'ELIMINADO'] as $est)
                                            <option value="{{ $est }}" {{ $documento->estado == $est ? 'selected' : '' }}>
                                                {{ $est }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Usuario Responsable -->
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label fw-bold">Usuario (idUser):</label>
                                    <select class="form-select" name="idUser">
                                        @foreach($usuarios ?? [] as $usr)
                                            <option value="{{ $usr->idUser }}" {{ $documento->idUser == $usr->idUser ? 'selected' : '' }}>
                                                {{ $usr->user }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Total Compra -->
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold text-success">Total Compra (totalCompra):</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white fw-bold span-simbolo-moneda">{{ $documento->moneda == 'DOLAR' ? '$' : 'S/' }}</span>
                                        <input type="number" step="0.01" min="0" name="totalCompra" id="edit-comprobante-total" class="form-control fw-bold text-success fs-5" value="{{ number_format($documento->totalCompra, 2, '.', '') }}">
                                    </div>
                                    <small class="text-muted">Se actualiza según los productos o puedes digitarlo directamente.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($documento->DetalleComprobante && $documento->DetalleComprobante->count() > 0)
                    <!-- Sección 2: Detalle de Productos y Precios -->
                    <div class="card border-0 bg-light shadow-sm">
                        <div class="card-header bg-white border-bottom-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-primary">
                                    <i class="bi bi-boxes me-1"></i> 2. Detalle de Productos (Precio Unitario - Sin IGV)
                                </h6>
                                <p class="text-secondary small mb-0">Al cambiar los precios unitarios, el total se recalculará automáticamente.</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-info text-white fw-bold shadow-sm" id="btnToggleProrrateo" onclick="document.getElementById('panel-prorrateo').classList.toggle('d-none')">
                                    <i class="bi bi-magic"></i> Prorratear
                                </button>
                                <div class="form-check form-switch ms-2 m-0">
                                    <input class="form-check-input" type="checkbox" id="addIgvCheckbox">
                                    <label class="form-check-label text-primary fw-bold small" for="addIgvCheckbox">+ 18% IGV</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            
                            <!-- Calculadora de Prorrateo -->
                            <div id="panel-prorrateo" class="d-none bg-white p-3 border rounded mb-3 shadow-sm">
                                <h6 class="text-primary fw-bold"><i class="bi bi-calculator"></i> Prorrateo Inteligente</h6>
                                <p class="small text-muted mb-2">Ingresa el total pagado en la factura y los precios referenciales de cada ítem para distribuir el costo automáticamente.</p>
                                <div class="row align-items-end mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">Total Factura (Sin IGV):</label>
                                        <div class="input-group">
                                            <span class="input-group-text span-simbolo-moneda">{{ $documento->moneda == 'DOLAR' ? '$' : 'S/' }}</span>
                                            <input type="number" id="prorrateoTotalPagado" class="form-control fw-bold text-success border-success" placeholder="Ej: 15150.00" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-primary w-100 fw-bold" onclick="calcularProrrateo()">
                                            <i class="bi bi-check2-all"></i> Calcular y Aplicar
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered bg-light">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th width="80" class="text-center">Cant.</th>
                                                <th width="140">Precio Ref. (<span class="span-simbolo-moneda">{{ $documento->moneda == 'DOLAR' ? '$' : 'S/' }}</span>)</th>
                                                <th width="100" class="text-center">% Peso</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-prorrateo">
                                            @foreach ($documento->DetalleComprobante as $index => $detalle)
                                            @php
                                                $validCount = 0;
                                                foreach($detalle->RegistroProducto as $reg) {
                                                    if($reg->estado != 'INVALIDO') $validCount++;
                                                }
                                                $ultimoDetalle = \App\Models\Ventas\DetalleComprobante::where('idProducto', $detalle->idProducto)
                                                    ->where('idDetalleComprobante', '!=', $detalle->idDetalleComprobante)
                                                    ->orderBy('idDetalleComprobante', 'desc')
                                                    ->first();
                                                
                                                if ($ultimoDetalle) {
                                                    $precioRef = $ultimoDetalle->precioUnitario;
                                                } else {
                                                    $precioRef = $documento->moneda == 'DOLAR' ? ($detalle->Producto->precioDolar ?? 0) : (($detalle->Producto->precioDolar ?? 0) * 3.8);
                                                }
                                            @endphp
                                            <tr>
                                                <td><small class="text-truncate d-inline-block" style="max-width:250px;" title="{{ $detalle->Producto->nombreProducto }}">{{ $detalle->Producto->nombreProducto }}</small></td>
                                                <td class="text-center align-middle"><span class="prorrateo-cant" data-index="{{ $index }}">{{ $validCount }}</span></td>
                                                <td>
                                                    <input type="number" step="0.01" class="form-control form-control-sm prorrateo-ref text-end" data-index="{{ $index }}" value="{{ number_format($precioRef, 2, '.', '') }}">
                                                </td>
                                                <td class="text-center align-middle"><span class="prorrateo-peso badge bg-secondary" id="peso-{{ $index }}">0%</span></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Tabla de Precios Unitarios de Productos -->
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-center" width="90">Cant.</th>
                                            <th width="160">P. Unit. (<span class="span-simbolo-moneda">{{ $documento->moneda == 'DOLAR' ? '$' : 'S/' }}</span>)</th>
                                            <th width="150" class="text-end">Subtotal (<span class="span-simbolo-moneda">{{ $documento->moneda == 'DOLAR' ? '$' : 'S/' }}</span>)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($documento->DetalleComprobante as $index => $detalle)
                                        @php
                                            $validCount = 0;
                                            foreach($detalle->RegistroProducto as $reg) {
                                                if($reg->estado != 'INVALIDO') $validCount++;
                                            }
                                            $rowTotal = $detalle->precioUnitario * $validCount;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $detalle->Producto->nombreProducto }}</div>
                                                <small class="text-muted">SKU: {{ $detalle->Producto->sku ?? '—' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">{{ $validCount }}</span>
                                            </td>
                                            <td>
                                                <input type="hidden" name="detalles[{{ $index }}][idDetalleComprobante]" value="{{ $detalle->idDetalleComprobante }}">
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm input-detalle-pu text-end" 
                                                       name="detalles[{{ $index }}][precioUnitario]" 
                                                       value="{{ number_format($detalle->precioUnitario, 2, '.', '') }}" 
                                                       data-qty="{{ $validCount }}" 
                                                       data-index="{{ $index }}"
                                                       oninput="recalcularFilaYTotal(this)" required>
                                            </td>
                                            <td class="text-end fw-bold">
                                                <span id="subtotal-row-{{ $index }}">{{ number_format($rowTotal, 2) }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold shadow-sm">
                        <i class="bi bi-floppy-fill me-1"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@include('documento.logic.edit_modal')
