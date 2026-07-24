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
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" id="addIgvCheckbox">
                                        <label class="form-check-label text-primary fw-bold" for="addIgvCheckbox">+ 18% IGV</label>
                                    </div>
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
