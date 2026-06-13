<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
    .ts-dropdown {
        z-index: 9999 !important;
    }
</style>

<!-- Modal Cotizador Shalom -->
<div class="modal fade" id="modalCotizadorShalom" tabindex="-1" aria-labelledby="modalCotizadorShalomLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalCotizadorShalomLabel"><i class="bi bi-calculator"></i> Cotizador Oficial Shalom</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-warning py-2 mb-3" style="font-size: 13px;">
                    <i class="bi bi-info-circle-fill"></i> El precio calculado es exacto al sistema de Shalom (Sujeto a verificación en agencia).
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Punto de Partida (Origen)</label>
                    <select id="cotizador_origen" class="form-select border-danger">
                        <option value="">Cargando terminales...</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Punto de Llegada (Destino)</label>
                    <select id="cotizador_destino" class="form-select border-danger">
                        <option value="">Cargando terminales...</option>
                    </select>
                    <small class="text-muted" style="font-size: 11px;">Intenta seleccionar la misma agencia elegida en el formulario.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de Paquete</label>
                    <select id="cotizador_preset" class="form-select border-danger" onchange="aplicarPresetShalom()">
                        <option value="custom">📦 Otra Medida (Personalizado)</option>
                        <option value="sobre" data-w="0.15" data-h="0.1" data-l="0.1" data-wt="0.25">✉️ Sobre (Máx 0.25 kg)</option>
                        <option value="cajapaquetexxs" data-w="0.15" data-h="0.1" data-l="0.1" data-wt="0.25">📦 Caja Paquete XXS</option>
                        <option value="cajapaquetexs" data-w="0.15" data-h="0.2" data-l="0.2" data-wt="0.5">📦 Caja Paquete XS</option>
                        <option value="cajapaquetes" data-w="0.2" data-h="0.12" data-l="0.3" data-wt="2">📦 Caja Paquete S</option>
                        <option value="cajapaquetem" data-w="0.24" data-h="0.3" data-l="0.2" data-wt="5">📦 Caja Paquete M</option>
                        <option value="cajapaquetel" data-w="0.42" data-h="0.23" data-l="0.3" data-wt="10">📦 Caja Paquete L</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Peso Total (KG) <span class="text-danger">*</span></label>
                        <input type="number" id="cotizador_peso" class="form-control" value="1" min="0.1" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Largo (m)</label>
                        <input type="number" id="cotizador_largo" class="form-control" value="0" min="0" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Ancho (m)</label>
                        <input type="number" id="cotizador_ancho" class="form-control" value="0" min="0" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted">Alto (m)</label>
                        <input type="number" id="cotizador_alto" class="form-control" value="0" min="0" step="0.01">
                    </div>
                </div>

                <div id="cotizador_resultado" class="text-center mt-3 d-none">
                    <div class="p-3 border rounded" style="background-color: #fff3f3; border-color: #f5c6cb !important;">
                        <h6 class="text-danger fw-bold mb-1">Precio Estimado</h6>
                        <h2 class="text-danger fw-black mb-0" style="font-weight: 900;" id="cotizador_precio">S/ 0.00</h2>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger fw-bold" onclick="calcularTarifaShalom()" id="btnCalcularShalom">
                    <i class="bi bi-magic"></i> Cotizar Envío
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let shalomTerminalsLoaded = false;
    let tomOrigen = null;
    let tomDestino = null;

    function abrirCotizadorShalom() {
        const modal = new bootstrap.Modal(document.getElementById('modalCotizadorShalom'));
        modal.show();

        if (!shalomTerminalsLoaded) {
            cargarTerminalesShalom();
        } else {
            preseleccionarDestinoShalom();
        }
    }

    function cargarTerminalesShalom() {
        fetch('{{ url("/envios-provincias/shalom-terminals") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const selectOrigen = document.getElementById('cotizador_origen');
                    const selectDestino = document.getElementById('cotizador_destino');

                    let html = '<option value="">Seleccione terminal...</option>';
                    data.data.forEach(t => {
                        html += `<option value="${t.id}">${t.nombre}</option>`;
                    });

                    selectOrigen.innerHTML = html;
                    selectDestino.innerHTML = html;

                    if (tomOrigen) tomOrigen.destroy();
                    if (tomDestino) tomDestino.destroy();

                    tomOrigen = new TomSelect("#cotizador_origen", {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        },
                        placeholder: "Escriba para buscar origen..."
                    });

                    tomDestino = new TomSelect("#cotizador_destino", {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        },
                        placeholder: "Escriba para buscar destino..."
                    });

                    // Seleccionar origen por defecto: La Victoria / Raymondi
                    const defaultOrigen = Object.values(tomOrigen.options).find(o => o.text.includes('RAYMONDI'));
                    if (defaultOrigen) {
                        tomOrigen.setValue(defaultOrigen.value);
                    }

                    shalomTerminalsLoaded = true;
                    preseleccionarDestinoShalom();
                } else {
                    Swal.fire('Error', 'No se pudieron cargar los terminales de Shalom: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error(error);
                Swal.fire('Error', 'Problema de conexión al obtener terminales.', 'error');
            });
    }

    function preseleccionarDestinoShalom() {
        // Intentar adivinar el destino seleccionado en el formulario
        const selectSubAgencia = document.getElementById('select-subagencia');
        if (selectSubAgencia && selectSubAgencia.selectedIndex > 0 && tomDestino) {
            const nombreOficina = selectSubAgencia.options[selectSubAgencia.selectedIndex].text.split('(')[0].trim().toUpperCase();

            // Buscar en las opciones de TomSelect
            for (const [key, option] of Object.entries(tomDestino.options)) {
                if (option.text.toUpperCase() === nombreOficina) {
                    tomDestino.setValue(key);
                    break;
                }
            }
        }
    }

    function aplicarPresetShalom() {
        const select = document.getElementById('cotizador_preset');
        const val = select.value;

        if (val === 'custom') {
            document.getElementById('cotizador_peso').value = 1;
            document.getElementById('cotizador_largo').value = 0;
            document.getElementById('cotizador_ancho').value = 0;
            document.getElementById('cotizador_alto').value = 0;
        } else {
            const selectedOption = select.options[select.selectedIndex];
            document.getElementById('cotizador_peso').value = selectedOption.getAttribute('data-wt');
            document.getElementById('cotizador_largo').value = selectedOption.getAttribute('data-l');
            document.getElementById('cotizador_ancho').value = selectedOption.getAttribute('data-w');
            document.getElementById('cotizador_alto').value = selectedOption.getAttribute('data-h');
        }
    }

    function calcularTarifaShalom() {
        const origen = document.getElementById('cotizador_origen').value;
        const destino = document.getElementById('cotizador_destino').value;
        const peso = document.getElementById('cotizador_peso').value;
        const largo = parseFloat(document.getElementById('cotizador_largo').value) || 0;
        const ancho = parseFloat(document.getElementById('cotizador_ancho').value) || 0;
        const alto = parseFloat(document.getElementById('cotizador_alto').value) || 0;

        if (!origen || !destino) {
            Swal.fire('Atención', 'Debe seleccionar punto de partida y llegada.', 'warning');
            return;
        }

        if (!peso || peso <= 0 || peso > 1500) {
            Swal.fire('Atención', 'El peso debe ser mayor a 0 y menor a 1500 KG.', 'warning');
            return;
        }

        // Validación de dimensiones máximas (1.5 metros)
        if (largo > 1.5 || ancho > 1.5 || alto > 1.5) {
            Swal.fire('Atención', 'Las dimensiones exceden el límite permitido por la agencia (Máx 1.5 metros).', 'warning');
            return;
        }

        const btn = document.getElementById('btnCalcularShalom');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Calculando...';
        btn.disabled = true;

        document.getElementById('cotizador_resultado').classList.add('d-none');

        fetch('{{ url("/envios-provincias/shalom-cotizar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    origin: origen,
                    destiny: destino,
                    weight: peso,
                    length: largo,
                    width: ancho,
                    height: alto
                })
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;

                if (data.success) {
                    // 1. Obtenemos qué eligió el usuario en el select
                    const tipoSeleccionado = document.getElementById('cotizador_preset').value;
                    let precioFinal = 0;

                    // 2. Lógica de extracción de precio
                    if (tipoSeleccionado !== 'custom' && data.tariff && data.tariff[tipoSeleccionado]) {
                        // Si eligió "sobre", buscamos data.tariff["sobre"], que nos dará 8.00
                        precioFinal = data.tariff[tipoSeleccionado];
                    } else {
                        // Si es personalizado, usamos el cálculo matemático estándar del servidor
                        precioFinal = data.price;
                    }

                    // 3. Pintamos el resultado
                    document.getElementById('cotizador_precio').innerText = 'S/ ' + parseFloat(precioFinal).toFixed(2);
                    document.getElementById('cotizador_resultado').classList.remove('d-none');
                } else {
                    Swal.fire('Error de Cálculo', data.message, 'error');
                }
            })
            .catch(error => {
                console.error(error);
                btn.innerHTML = originalText;
                btn.disabled = false;
                Swal.fire('Error', 'Fallo de conexión al calcular tarifa.', 'error');
            });
    }
</script>