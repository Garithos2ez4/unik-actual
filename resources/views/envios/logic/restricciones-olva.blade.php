{{-- Restricciones Olva Courier: Alerta visual y Validación global --}}
<script>
    // Mostrar/Ocultar alerta de restricciones cuando cambia la agencia a OLVA
    function verificarRestriccionesOlva() {
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        const selectedAgencia = selectAgencia ? selectAgencia.options[selectAgencia.selectedIndex] : null;
        const nombreAgencia = selectedAgencia ? selectedAgencia.text.trim().toUpperCase() : '';

        // Buscar el contenedor de alertas, usaremos el mismo contenedor o crearemos uno si no existe
        // Como ya tienes un div 'alerta_restricciones_shalom', podemos crear uno para olva o reutilizar
        let alertaDiv = document.getElementById('alerta_restricciones_olva');
        
        // Si no existe, lo inyectamos al vuelo cerca del select de subagencia
        if (!alertaDiv) {
            const container = document.getElementById('select-subagencia')?.closest('.col-12, .col-md-6, .mb-3')?.parentElement;
            if (container) {
                const div = document.createElement('div');
                div.id = 'alerta_restricciones_olva';
                div.className = 'col-12 mt-2 d-none';
                container.appendChild(div);
                alertaDiv = div;
            }
        }

        if (!alertaDiv) return;

        // Solo para OLVA
        if (nombreAgencia !== 'OLVA') {
            alertaDiv.innerHTML = '';
            alertaDiv.classList.add('d-none');
            return;
        }

        // Mostrar restricciones globales de Olva
        let detallesHtml = `<div class="d-flex flex-wrap gap-3 mt-2">`;
        detallesHtml += `<span class="badge bg-primary text-white border border-primary px-3 py-2 fs-6"><i class="bi bi-box-seam"></i> Paquetería Regular</span>`;
        detallesHtml += `<span class="badge bg-warning text-dark border border-warning px-3 py-2 fs-6">⚖️ Máx Global: <strong>25 KG</strong></span>`;
        detallesHtml += `<span class="badge bg-secondary text-white border border-secondary px-3 py-2 fs-6">📐 Dim Máx: <strong>1.20 m por lado</strong></span>`;
        detallesHtml += `</div>`;

        alertaDiv.innerHTML = `
            <div class="alert alert-info border-info d-flex align-items-center py-3 mb-0" style="font-size: 1.15rem;">
                <i class="bi bi-info-circle-fill me-3 fs-1 text-info"></i>
                <div>
                    <strong class="fs-5">ℹ️ Restricciones Generales de "Olva Courier"</strong>
                    <div class="mt-1" style="font-size:0.95rem;">Olva Courier aplica estas restricciones en casi toda su red a nivel nacional.</div>
                    ${detallesHtml}
                </div>
            </div>`;
        alertaDiv.classList.remove('d-none');
    }

    // Validar peso y dimensiones contra restricciones globales de Olva antes de guardar
    function validarRestriccionesOlvaAntesDeGuardar() {
        const peso = parseFloat(document.getElementById('input_peso')?.value) || 0;
        const largo = parseFloat(document.getElementById('input_largo')?.value) || 0;
        const ancho = parseFloat(document.getElementById('input_ancho')?.value) || 0;
        const alto = parseFloat(document.getElementById('input_alto')?.value) || 0;

        const pesoMax = 25; // KG
        const dimMax = 1.2; // Metros

        let errores = [];

        if (peso > pesoMax) {
            errores.push(`⚖️ <strong>Peso:</strong> ${peso} KG excede el máximo permitido de <strong>${pesoMax} KG</strong> en la red de Olva.`);
        }
        if (largo > dimMax) {
            errores.push(`📐 <strong>Largo:</strong> ${largo} m excede el máximo de <strong>${dimMax} m</strong>.`);
        }
        if (ancho > dimMax) {
            errores.push(`📐 <strong>Ancho:</strong> ${ancho} m excede el máximo de <strong>${dimMax} m</strong>.`);
        }
        if (alto > dimMax) {
            errores.push(`📐 <strong>Alto:</strong> ${alto} m excede el máximo de <strong>${dimMax} m</strong>.`);
        }

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: '⛔ Excede límites de Olva Courier',
                html: `
                    <p class="mb-2">La red de paquetería de <strong>Olva Courier</strong> no acepta estos valores:</p>
                    <div class="text-start" style="font-size:0.95rem;">
                        ${errores.map(e => `<div class="mb-1">• ${e}</div>`).join('')}
                    </div>
                    <hr>
                    <p class="text-muted" style="font-size:0.85rem;">Si tu envío excede estas medidas globales, te recomendamos enviarlo por Shalom o Marvisur que aceptan carga pesada.</p>
                `,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        return true;
    }

    // Interceptar la validación en el flujo general (esto se integra con el de Shalom)
    document.addEventListener('DOMContentLoaded', function() {
        const selectAgencia = document.querySelector('select[name="idAgencia"]');
        if (selectAgencia) {
            // Escuchar cambios de agencia
            selectAgencia.addEventListener('change', function() {
                verificarRestriccionesOlva();
                // Si tienes la función de shalom, llámala también para limpiar
                if (typeof verificarRestriccionesShalom === 'function') {
                    verificarRestriccionesShalom();
                }
            });
            // Ejecutar al cargar
            verificarRestriccionesOlva();
        }

        // Modificamos el verificador de guardado si existe el de Shalom
        if (typeof verificarClaveYGuardar === 'function') {
            const originalVerificarClaveYGuardar = verificarClaveYGuardar;
            
            // Re-escribimos la función global (esto es un parche dinámico para que conviva con Shalom)
            window.verificarClaveYGuardar = function() {
                const selectAgencia = document.querySelector('select[name="idAgencia"]');
                const nombreAgencia = selectAgencia ? selectAgencia.options[selectAgencia.selectedIndex].text.trim().toUpperCase() : '';

                if (nombreAgencia === 'OLVA') {
                    if (!validarRestriccionesOlvaAntesDeGuardar()) {
                        return; // Bloquea si no pasa Olva
                    }
                    
                    const form = document.getElementById('form-create-envio');
                    if (form && !form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    // Si pasó Olva, continúa con el guardado normal (saltándose las reglas de Shalom)
                    let clave = document.getElementById('input-clave')?.value.trim();
                    if (clave !== '' && document.getElementById('confirmSaveModal')) {
                        var myModal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
                        myModal.show();
                    } else if (form) {
                        form.submit();
                    }
                    return;
                }
                
                // Si no es Olva, corre la función original (que ya tiene la lógica de Shalom)
                originalVerificarClaveYGuardar();
            };
        }
    });
</script>
