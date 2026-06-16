{{-- Restricciones Shalom: Alerta visual + Validación al guardar --}}
<script>
    // Cache global de restricciones Shalom
    let shalomRestricciones = null;
    let restriccionActual = null;

    // Cargar restricciones al iniciar (solo una vez)
    async function cargarRestriccionesShalom() {
        if (shalomRestricciones) return shalomRestricciones;

        try {
            const res = await fetch("{{ url('/envios-provincias/shalom-restricciones') }}");
            const json = await res.json();
            if (json.success && json.data) {
                shalomRestricciones = json.data;
                verificarRestriccionesShalom();
                return shalomRestricciones;
            }
        } catch (e) {
            console.error('Error al cargar restricciones Shalom:', e);
        }
        return null;
    }

    // Buscar la restricción que coincida con la subagencia seleccionada
    function buscarRestriccionPorNombre(nombreSubagencia) {
        if (!shalomRestricciones || !nombreSubagencia) return null;

        const nombreNorm = nombreSubagencia.trim().toUpperCase();

        for (const [id, terminal] of Object.entries(shalomRestricciones)) {
            const terminalNorm = terminal.nombre_terminal?.trim().toUpperCase();
            if (!terminalNorm) continue;

            // Match exacto o parcial
            if (terminalNorm === nombreNorm || nombreNorm.includes(terminalNorm) || terminalNorm.includes(nombreNorm)) {
                return {
                    id,
                    ...terminal
                };
            }
        }
        return null;
    }

    // Mostrar/Ocultar alerta de restricciones cuando cambia la subagencia
    function verificarRestriccionesShalom() {
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        const selectedAgencia = selectAgencia.options[selectAgencia.selectedIndex];
        const nombreAgencia = selectedAgencia ? selectedAgencia.text.trim().toUpperCase() : '';

        const alertaDiv = document.getElementById('alerta_restricciones_shalom');
        if (!alertaDiv) return;

        // Solo para Shalom
        if (nombreAgencia !== 'SHALOM') {
            alertaDiv.innerHTML = '';
            alertaDiv.classList.add('d-none');
            restriccionActual = null;
            return;
        }

        const selectSub = document.getElementById('select-subagencia');
        if (!selectSub || !selectSub.value) {
            alertaDiv.innerHTML = '';
            alertaDiv.classList.add('d-none');
            restriccionActual = null;
            return;
        }

        const selectedSub = selectSub.options[selectSub.selectedIndex];
        const nombreCompleto = selectedSub ? selectedSub.text : '';
        let nombreOficina = nombreCompleto;
        if (nombreCompleto.includes(' - ')) {
            nombreOficina = nombreCompleto.split(' - ')[0].trim();
        } else if (nombreCompleto.includes(' (')) {
            nombreOficina = nombreCompleto.split(' (')[0].trim();
        } else {
            nombreOficina = nombreCompleto.trim();
        }

        const restriccion = buscarRestriccionPorNombre(nombreOficina);
        restriccionActual = restriccion;

        if (!restriccion) {
            alertaDiv.innerHTML = `
                <div class="alert alert-info border-info d-flex align-items-center py-2 mb-0" style="font-size: 0.85rem;">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <div>
                        <strong>Terminal "${nombreOficina}"</strong> no encontrada en las restricciones de Shalom.
                        Se permitirá el registro sin validación de límites.
                    </div>
                </div>`;
            alertaDiv.classList.remove('d-none');
            return;
        }

        // Verificar si la terminal puede recibir
        const puedeRecibir = restriccion.recibe && !Array.isArray(restriccion.recibe) && restriccion.recibe.hasta;

        if (!puedeRecibir) {
            alertaDiv.innerHTML = `
                <div class="alert alert-danger border-danger d-flex align-items-center py-2 mb-0" style="font-size: 0.85rem;">
                    <i class="bi bi-x-octagon-fill me-2 fs-4 text-danger"></i>
                    <div>
                        <strong>🚫 ${restriccion.nombre_terminal}</strong> — Esta terminal <strong>NO puede recibir paquetes</strong>.
                        <br><span class="text-muted">Tipo: ${restriccion.tipo_agencia || 'N/A'}</span>
                    </div>
                </div>`;
            alertaDiv.classList.remove('d-none');
            return;
        }

        // Construir info de restricciones para recibir
        const pesoMax = parseFloat(restriccion.recibe.hasta.kg) || 0;
        const volMax = parseFloat(restriccion.recibe.hasta.m3) || 0;
        const dimRecibe = restriccion.dimensiones?.recibe || {};
        const largoMax = parseFloat(dimRecibe.largo) || 0;
        const altoMax = parseFloat(dimRecibe.alto) || 0;
        const anchoMax = parseFloat(dimRecibe.ancho) || 0;

        let detallesHtml = `<div class="d-flex flex-wrap gap-3 mt-1">`;
        detallesHtml += `<span class="badge bg-primary bg-opacity-10 text-white border border-primary px-2 py-1"><i class="bi bi-building"></i> ${restriccion.tipo_agencia}</span>`;

        if (pesoMax > 0) {
            detallesHtml += `<span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-2 py-1">⚖️ Máx: <strong>${pesoMax} KG</strong></span>`;
        }
        if (volMax > 0) {
            detallesHtml += `<span class="badge bg-info bg-opacity-10 text-dark border border-info px-2 py-1">📦 Máx: <strong>${volMax} m³</strong></span>`;
        }
        if (largoMax > 0 && altoMax > 0 && anchoMax > 0) {
            detallesHtml += `<span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary px-2 py-1">📐 Dim: <strong>${largoMax}×${anchoMax}×${altoMax} m</strong></span>`;
        }
        if (restriccion.reparto == 1) {
            detallesHtml += `<span class="badge bg-success bg-opacity-10 text-dark border border-success px-2 py-1">🚚 Con Reparto</span>`;
        }
        detallesHtml += `</div>`;

        alertaDiv.innerHTML = `
            <div class="alert alert-warning border-warning d-flex align-items-start py-2 mb-0" style="font-size: 0.85rem;">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-warning mt-1"></i>
                <div>
                    <strong>⚠️ Restricciones de "${restriccion.nombre_terminal}" (Recepción)</strong>
                    ${detallesHtml}
                </div>
            </div>`;
        alertaDiv.classList.remove('d-none');
    }

    // Validar peso y dimensiones contra restricciones antes de guardar
    function validarRestriccionesAntesDeGuardar() {
        if (!restriccionActual) return true; // Sin restricción, permitir

        const puedeRecibir = restriccionActual.recibe && !Array.isArray(restriccionActual.recibe) && restriccionActual.recibe.hasta;

        if (!puedeRecibir) {
            Swal.fire({
                icon: 'error',
                title: '🚫 Terminal no recibe paquetes',
                html: `<strong>${restriccionActual.nombre_terminal}</strong> no puede recibir envíos.<br>Selecciona otra sucursal.`,
                confirmButtonColor: '#dc3545'
            });
            return false;
        }

        const peso = parseFloat(document.getElementById('input_peso')?.value) || 0;
        const largo = parseFloat(document.getElementById('input_largo')?.value) || 0;
        const ancho = parseFloat(document.getElementById('input_ancho')?.value) || 0;
        const alto = parseFloat(document.getElementById('input_alto')?.value) || 0;

        const pesoMax = parseFloat(restriccionActual.recibe.hasta.kg) || 0;
        const volMax = parseFloat(restriccionActual.recibe.hasta.m3) || 0;
        const dimRecibe = restriccionActual.dimensiones?.recibe || {};
        const largoMax = parseFloat(dimRecibe.largo) || 0;
        const altoMax = parseFloat(dimRecibe.alto) || 0;
        const anchoMax = parseFloat(dimRecibe.ancho) || 0;

        let errores = [];

        if (pesoMax > 0 && peso > pesoMax) {
            errores.push(`⚖️ <strong>Peso:</strong> ${peso} KG excede el máximo de <strong>${pesoMax} KG</strong>`);
        }

        if (volMax > 0 && largo > 0 && ancho > 0 && alto > 0) {
            const volumen = largo * ancho * alto;
            if (volumen > volMax) {
                errores.push(`📦 <strong>Volumen:</strong> ${volumen.toFixed(3)} m³ excede el máximo de <strong>${volMax} m³</strong>`);
            }
        }

        if (largoMax > 0 && largo > largoMax) {
            errores.push(`📐 <strong>Largo:</strong> ${largo} m excede el máximo de <strong>${largoMax} m</strong>`);
        }
        if (anchoMax > 0 && ancho > anchoMax) {
            errores.push(`📐 <strong>Ancho:</strong> ${ancho} m excede el máximo de <strong>${anchoMax} m</strong>`);
        }
        if (altoMax > 0 && alto > altoMax) {
            errores.push(`📐 <strong>Alto:</strong> ${alto} m excede el máximo de <strong>${altoMax} m</strong>`);
        }

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: '⛔ Excede límites de Shalom',
                html: `
                    <p class="mb-2">La terminal <strong>${restriccionActual.nombre_terminal}</strong> (${restriccionActual.tipo_agencia}) no acepta estos valores:</p>
                    <div class="text-start" style="font-size:0.95rem;">
                        ${errores.map(e => `<div class="mb-1">• ${e}</div>`).join('')}
                    </div>
                    <hr>
                    <p class="text-muted" style="font-size:0.85rem;">Ajusta las medidas o selecciona otra sucursal que soporte más capacidad.</p>
                `,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        return true;
    }

    // Override verificarClaveYGuardar para validar restricciones primero
    const _originalVerificarClave = typeof verificarClaveYGuardar === 'function' ? verificarClaveYGuardar : null;

    function verificarClaveYGuardar() {
        // Solo validar si la agencia es Shalom
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        const selectedAgencia = selectAgencia.options[selectAgencia.selectedIndex];
        const nombreAgencia = selectedAgencia ? selectedAgencia.text.trim().toUpperCase() : '';

        if (nombreAgencia === 'SHALOM') {
            // Validar dimensiones máximas (1.5m)
            const MAX_DIM = 1.5;
            const largo = parseFloat(document.getElementById('input_largo')?.value) || 0;
            const ancho = parseFloat(document.getElementById('input_ancho')?.value) || 0;
            const alto = parseFloat(document.getElementById('input_alto')?.value) || 0;

            let dimErrores = [];
            if (largo > MAX_DIM) dimErrores.push(`Largo: ${largo}m`);
            if (ancho > MAX_DIM) dimErrores.push(`Ancho: ${ancho}m`);
            if (alto > MAX_DIM) dimErrores.push(`Alto: ${alto}m`);

            if (dimErrores.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: '📐 Dimensiones fuera de rango',
                    html: `
                        <p>Las dimensiones no pueden superar <strong>${MAX_DIM} metros</strong>:</p>
                        <div class="text-start" style="font-size:0.95rem;">
                            ${dimErrores.map(e => `<div class="mb-1">• <strong>${e}</strong> (máx ${MAX_DIM}m)</div>`).join('')}
                        </div>
                    `,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Corregir'
                });
                return;
            }

            // Validar restricciones de la API de Shalom
            if (!validarRestriccionesAntesDeGuardar()) {
                return; // Bloquear guardado
            }
        }

        // Continuar con el flujo original
        let clave = document.getElementById('input-clave').value.trim();
        if (clave !== '') {
            var myModal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
            myModal.show();
        } else {
            document.getElementById('form-create-envio').submit();
        }
    }

    // Listener para el select de subagencia (change event)
    document.addEventListener('DOMContentLoaded', function() {
        // Pre-cargar restricciones
        cargarRestriccionesShalom();

        // Observar cambios en el select de subagencia (se llena dinámicamente)
        const selectSub = document.getElementById('select-subagencia');
        if (selectSub) {
            selectSub.addEventListener('change', verificarRestriccionesShalom);

            // Observar cuando se recargan las opciones (MutationObserver)
            const observer = new MutationObserver(() => {
                verificarRestriccionesShalom();
            });
            observer.observe(selectSub, {
                childList: true
            });
        }
    });
</script>