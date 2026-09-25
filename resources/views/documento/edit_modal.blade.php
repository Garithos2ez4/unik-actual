        <!-- Modal Editar Comprobante -->
        @if(!$validate)
        <form action="{{ route('editcomprobante') }}" method="POST">
            @csrf
            <input type="hidden" name="idComprobante" value="{{ $documento->idComprobante }}">
            <div class="modal fade" id="editComprobanteModal" tabindex="-1" aria-labelledby="editComprobanteModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="editComprobanteModalLabel"><i class="bi bi-pencil-square"></i> Editar Comprobante</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Moneda del Comprobante:</label>
                                    <select class="form-select" name="moneda" required>
                                        <option value="SOL" {{ $documento->moneda == 'SOL' ? 'selected' : '' }}>Soles (SOL)</option>
                                        <option value="DOLAR" {{ $documento->moneda == 'DOLAR' ? 'selected' : '' }}>Dólares (DOLAR)</option>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="row align-items-center mb-2">
                                <div class="col-8">
                                    <h6 class="fw-bold mb-0">Detalle de Productos (Precio Unitario - SIN IGV)</h6>
                                    <p class="text-secondary small mb-0">Actualiza el precio unitario. El precio total se calculará automáticamente.</p>
                                </div>
                                <div class="col-4 text-end">
                                    <button type="button" class="btn btn-sm btn-info text-white fw-bold shadow-sm" id="btnToggleProrrateo" onclick="document.getElementById('panel-prorrateo').classList.toggle('d-none')">
                                        <i class="bi bi-magic"></i> Prorratear
                                    </button>
                                    <div class="form-check form-switch d-inline-block ms-2">
                                        <input class="form-check-input" type="checkbox" id="addIgvCheckbox">
                                        <label class="form-check-label text-primary fw-bold" for="addIgvCheckbox">+ 18% IGV</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Calculadora de Prorrateo -->
                            <div id="panel-prorrateo" class="d-none bg-light p-3 border rounded mb-3 shadow-sm">
                                <h6 class="text-primary fw-bold"><i class="bi bi-calculator"></i> Prorrateo Inteligente</h6>
                                <p class="small text-muted mb-2">Ingresa el total exacto pagado en la factura y los precios referenciales de cada ítem. El sistema calculará el peso de cada producto y asignará su costo final unitario.</p>
                                <div class="row align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Total Factura (Sin IGV):</label>
                                        <input type="number" id="prorrateoTotalPagado" class="form-control fw-bold text-success border-success" placeholder="Ej: 15150.00" step="0.01">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-primary w-100 fw-bold" onclick="calcularProrrateo()">
                                            <i class="bi bi-check2-all"></i> Calcular y Aplicar
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered bg-white">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th width="80" class="text-center">Cant.</th>
                                                <th width="120">Precio Ref. ({{ $documento->moneda }})</th>
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
                                                // Obtener ultimo precio referencial
                                                $ultimoDetalle = \App\Models\Ventas\DetalleComprobante::where('idProducto', $detalle->idProducto)
                                                    ->where('idDetalleComprobante', '!=', $detalle->idDetalleComprobante)
                                                    ->orderBy('idDetalleComprobante', 'desc')
                                                    ->first();
                                                
                                                $precioRef = 0;
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
                            
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const checkbox = document.getElementById('addIgvCheckbox');
                                    if (checkbox) {
                                        checkbox.addEventListener('change', function () {
                                            const inputs = document.querySelectorAll('input[name^="detalles"][name$="[precioUnitario]"]');
                                            inputs.forEach(input => {
                                                if (input.value) {
                                                    let val = parseFloat(input.value);
                                                    if (this.checked) {
                                                        input.dataset.original = val;
                                                        input.value = (val * 1.18).toFixed(2);
                                                    } else {
                                                        if (input.dataset.original) {
                                                            input.value = parseFloat(input.dataset.original).toFixed(2);
                                                        } else {
                                                            input.value = (val / 1.18).toFixed(2);
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    }
                                });

                                function calcularProrrateo() {
                                    let totalPagado = parseFloat(document.getElementById('prorrateoTotalPagado').value);
                                    if (!totalPagado || totalPagado <= 0) {
                                        alert("Por favor ingresa el monto total de la factura.");
                                        return;
                                    }

                                    let items = [];
                                    let costoTeoricoTotal = 0;

                                    document.querySelectorAll('.prorrateo-ref').forEach(input => {
                                        let index = input.getAttribute('data-index');
                                        let refPrice = parseFloat(input.value) || 0;
                                        let spanCant = document.querySelector(`.prorrateo-cant[data-index="${index}"]`);
                                        let qty = parseFloat(spanCant.innerText) || 0;

                                        let rowTeorico = refPrice * qty;
                                        costoTeoricoTotal += rowTeorico;

                                        items.push({
                                            index: index,
                                            refPrice: refPrice,
                                            qty: qty,
                                            rowTeorico: rowTeorico
                                        });
                                    });

                                    if (costoTeoricoTotal <= 0) {
                                        alert("Los precios referenciales no pueden ser cero en todos los productos.");
                                        return;
                                    }

                                    items.forEach(item => {
                                        let porcentaje = item.rowTeorico / costoTeoricoTotal;
                                        let badge = document.getElementById(`peso-${item.index}`);
                                        badge.innerText = (porcentaje * 100).toFixed(2) + '%';
                                        badge.classList.remove('bg-secondary');
                                        badge.classList.add('bg-success');

                                        let costoProrrateadoFila = totalPagado * porcentaje;
                                        let costoUnitarioFinal = (costoProrrateadoFila / item.qty) || 0;

                                        let mainInput = document.querySelector(`input[name="detalles[${item.index}][precioUnitario]"]`);
                                        if (mainInput) {
                                            mainInput.value = costoUnitarioFinal.toFixed(2);
                                            
                                            let checkbox = document.getElementById('addIgvCheckbox');
                                            if(checkbox && checkbox.checked) {
                                                mainInput.dataset.original = costoUnitarioFinal.toFixed(2);
                                                mainInput.value = (costoUnitarioFinal * 1.18).toFixed(2);
                                            }
                                        }
                                    });
                                }
                            </script>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-center" width="100">Cant.</th>
                                            <th width="150">Precio Unit. ({{ $documento->moneda }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($documento->DetalleComprobante as $index => $detalle)
                                        <tr>
                                            <td class="align-middle">{{ $detalle->Producto->nombreProducto }}</td>
                                            <td class="align-middle text-center">
                                                @php
                                                    $validCount = 0;
                                                    foreach($detalle->RegistroProducto as $reg) {
                                                        if($reg->estado != 'INVALIDO') $validCount++;
                                                    }
                                                @endphp
                                                {{ $validCount }}
                                            </td>
                                            <td>
                                                <input type="hidden" name="detalles[{{ $index }}][idDetalleComprobante]" value="{{ $detalle->idDetalleComprobante }}">
                                                <input type="number" step="0.01" min="0" class="form-control" name="detalles[{{ $index }}][precioUnitario]" value="{{ $detalle->precioUnitario }}" required>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning"><i class="bi bi-floppy-fill"></i> Guardar Cambios</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endif
