<script>
    // Variables de Estado del Asistente Masivo
    let candidatosGlobales = [];
    let gruposGlobales = [];
    let importacionEnCurso = false;
    let importacionPausada = false;
    let totalImportadosExitoCount = 0;
    let totalImportadosErrorCount = 0;
    const CHUNK_SIZE = 5; // Micro-lotes de 5 productos para evitar caidas

    function abrirModalImportacionOfertas() {
        const modalEl = document.getElementById('modalImportarOfertas');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        if (candidatosGlobales.length === 0) {
            cargarCandidatosOfertas();
        }
    }

    function cargarCandidatosOfertas() {
        document.getElementById('importarOfertasCargando').classList.remove('d-none');
        document.getElementById('importarOfertasError').classList.add('d-none');
        document.getElementById('importarOfertasContenido').classList.add('d-none');

        fetch('{{ route("compras.deltron.ofertas.candidatas") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('importarOfertasCargando').classList.add('d-none');
            if (data.status !== 'ok') {
                document.getElementById('importarOfertasError').classList.remove('d-none');
                document.getElementById('importarOfertasErrorMsg').innerText = data.message || 'Error al obtener candidatos';
                return;
            }

            candidatosGlobales = data.items || [];
            gruposGlobales = data.grupos || [];
            if (typeof data.fantasmas_disponibles !== 'undefined') {
                const elFant = document.getElementById('conteoFantasmasOfertas');
                if (elFant) elFant.innerText = data.fantasmas_disponibles;
            }

            renderizarTablaCandidatos(candidatosGlobales);
            document.getElementById('importarOfertasContenido').classList.remove('d-none');
        })
        .catch(err => {
            document.getElementById('importarOfertasCargando').classList.add('d-none');
            document.getElementById('importarOfertasError').classList.remove('d-none');
            document.getElementById('importarOfertasErrorMsg').innerText = 'Fallo de conexión: ' + err.message;
        });
    }

    function esColorClaro(hex) {
        if (!hex || hex.length < 6) return false;
        hex = hex.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return (yiq >= 170);
    }

    function renderizarTablaCandidatos(items) {
        const tbody = document.getElementById('tbodyCandidatosOfertas');
        tbody.innerHTML = '';

        document.getElementById('badgeTotalCandidatos').innerText = `${items.length} productos nuevos`;
        document.getElementById('conteoSeleccionados').innerText = items.length;

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                        No hay productos nuevos con oferta y stock masivo pendientes de importar.
                    </td>
                </tr>
            `;
            document.getElementById('btnEjecutarImportacion').disabled = true;
            return;
        }

        document.getElementById('btnEjecutarImportacion').disabled = false;

        // Opciones para el select de grupos
        let opcionesGruposHtml = '';
        gruposGlobales.forEach(g => {
            opcionesGruposHtml += `<option value="${g.idGrupo}" data-cat="${g.nombreCategoria}">${g.nombreGrupo} (${g.nombreCategoria})</option>`;
        });

        items.forEach((item, idx) => {
            const fotoPreview = (item.foto1_url && String(item.foto1_url).trim() !== '' && !String(item.foto1_url).includes('blanco.jpg'))
                ? `<img src="${item.foto1_url}" class="rounded border shadow-xs" style="width: 48px; height: 48px; object-fit: contain; background: #fff;" onerror="this.onerror=null; this.src='{{ asset('storage/noimagen.webp') }}';">`
                : `<img src="{{ asset('storage/noimagen.webp') }}" class="rounded border shadow-xs" style="width: 48px; height: 48px; object-fit: contain; opacity: 0.6;">`;

            const precioBase = parseFloat(item.price || 0).toFixed(2);
            const precioPromo = item.price_promo ? parseFloat(item.price_promo).toFixed(2) : null;

            const tr = document.createElement('tr');
            tr.className = 'candidato-row';
            tr.id = `cand-row-${idx}`;
            tr.setAttribute('data-sku', item.sku);

            tr.innerHTML = `
                <td class="text-center">
                    <input type="checkbox" class="form-check-input check-candidato" data-idx="${idx}" checked onchange="actualizarConteoSeleccionados()">
                </td>
                <td class="text-center">
                    ${fotoPreview}
                </td>
                <td>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 340px;" title="${item.name}">${item.name}</div>
                    ${item.deltron_name && item.deltron_name !== item.name ? `<div class="text-muted text-truncate" style="font-size: 0.74rem;" title="${item.deltron_name}">${item.deltron_name}</div>` : ''}
                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                        <span class="badge bg-secondary font-monospace">${item.sku}</span>
                        <span class="text-muted font-monospace" style="font-size: 0.75rem;">PN: ${item.part_number || '-'}</span>
                        <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">${item.brand_name || 'Teros'}</span>
                        ${item.es_variante && item.valor_variante ? `
                            <span class="badge rounded-pill border" style="background-color: ${item.codigo_color_hex || '#495057'}; color: ${esColorClaro(item.codigo_color_hex) ? '#000' : '#fff'}; font-size: 0.72rem;">
                                <i class="bi bi-palette-fill me-1"></i>${item.valor_variante}
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;" title="Se agrupará como variante de: ${item.nombre_padre}">
                                <i class="bi bi-diagram-3 me-1"></i>Variante: ${item.part_number_base}
                            </span>
                        ` : ''}
                    </div>
                </td>
                <td>
                    <select class="form-select form-select-sm select-grupo-candidato py-1" data-idx="${idx}" onchange="cambiarGrupoFila(${idx}, this)">
                        ${opcionesGruposHtml}
                    </select>
                    <div class="mt-1 small text-muted d-flex align-items-center">
                        <span class="badge bg-light text-secondary border me-1" id="cat-label-${idx}">
                            ${item.nombreCategoria || 'Perifericos'}
                        </span>
                        <span class="text-success" style="font-size: 0.72rem;"><i class="bi bi-magic"></i> Sugerido por título</span>
                    </div>
                </td>
                <td class="text-center">
                    <div class="fw-bold text-dark fs-6" title="Precio Lista B2B">$${precioBase}</div>
                    ${precioPromo ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;" title="Oferta en Deltron: $${precioPromo}">Oferta: $${precioPromo}</span>` : ''}
                </td>
                <td class="text-center">
                    <span class="badge bg-success rounded-pill px-2 py-1">
                        <i class="bi bi-box-seam me-1"></i> ${item.lima_stock} uds.
                    </span>
                    ${item.lima_hay_mas ? `<span class="badge bg-warning text-dark rounded-pill ms-1" title="Deltron indica que hay mas stock (+20 por almacen)">+20</span>` : ''}
                    <small class="d-block text-muted mt-1" style="font-size: 0.7rem;">Total Perú: ${item.total_stock}</small>
                </td>
            `;

            tbody.appendChild(tr);

            // Pre-seleccionar el grupo sugerido en el select
            const selectEl = tr.querySelector('.select-grupo-candidato');
            if (selectEl && item.idGrupo) {
                selectEl.value = item.idGrupo;
            }
        });
    }

    function cambiarGrupoFila(idx, selectEl) {
        const idGrupo = parseInt(selectEl.value);
        const grupoObj = gruposGlobales.find(g => g.idGrupo === idGrupo);
        if (grupoObj) {
            candidatosGlobales[idx].idGrupo = idGrupo;
            candidatosGlobales[idx].nombreGrupo = grupoObj.nombreGrupo;
            candidatosGlobales[idx].idCategoria = grupoObj.idCategoria;
            candidatosGlobales[idx].nombreCategoria = grupoObj.nombreCategoria;

            const labelEl = document.getElementById(`cat-label-${idx}`);
            if (labelEl) {
                labelEl.innerText = grupoObj.nombreCategoria;
            }
        }
    }

    function toggleSeleccionarTodos(checked) {
        document.querySelectorAll('.check-candidato').forEach(chk => {
            // Solo marcar los que estan visibles segun el filtro
            if (chk.closest('tr').style.display !== 'none') {
                chk.checked = checked;
            }
        });
        actualizarConteoSeleccionados();
    }

    function actualizarConteoSeleccionados() {
        const checks = document.querySelectorAll('.check-candidato:checked');
        document.getElementById('conteoSeleccionados').innerText = checks.length;
        document.getElementById('btnEjecutarImportacion').disabled = (checks.length === 0);
    }

    function filtrarTablaCandidatos() {
        const query = document.getElementById('filtroTablaCandidatos').value.toUpperCase().trim();
        const rows = document.querySelectorAll('#tbodyCandidatosOfertas tr.candidato-row');

        rows.forEach(r => {
            const text = r.innerText.toUpperCase();
            if (!query || text.includes(query)) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
        actualizarConteoSeleccionados();
    }

    // ==============================================================
    // MOTOR DE IMPORTACION POR MICRO-LOTES AJAX
    // ==============================================================
    async function iniciarImportacionMasiva() {
        const checks = Array.from(document.querySelectorAll('.check-candidato:checked'));
        if (checks.length === 0) {
            Swal.fire('Atención', 'No has seleccionado ningún producto para importar.', 'warning');
            return;
        }

        const itemsSeleccionados = checks.map(c => {
            const idx = parseInt(c.getAttribute('data-idx'));
            return candidatosGlobales[idx];
        });

        const totalItems = itemsSeleccionados.length;
        const totalLotes = Math.ceil(totalItems / CHUNK_SIZE);

        const confirmacion = await Swal.fire({
            title: '¿Iniciar importación masiva?',
            html: `Se importarán <b>${totalItems} productos</b> en <b>${totalLotes} micro-lotes</b>.<br><br>` +
                  `<small class="text-muted">Se descargará la <b>Foto 1</b> convertida a WebP 800x800 para cada producto.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, Comenzar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-danger rounded-pill px-4',
                cancelButton: 'btn btn-secondary rounded-pill px-4'
            }
        });

        if (!confirmacion.isConfirmed) return;

        // Iniciar interfaz de progreso
        importacionEnCurso = true;
        importacionPausada = false;
        totalImportadosExitoCount = 0;
        totalImportadosErrorCount = 0;

        document.getElementById('panelProgresoImportacion').classList.remove('d-none');
        document.getElementById('spinnerProgresoOfertas')?.classList.remove('d-none');
        document.getElementById('iconoExitoProgresoOfertas')?.classList.add('d-none');
        document.getElementById('btnPausarImportacion')?.classList.remove('d-none');

        const contProgOf = document.getElementById('contenedorEstadoProgreso');
        if (contProgOf) {
            contProgOf.classList.remove('text-success', 'text-warning');
            contProgOf.classList.add('text-danger');
        }
        const barraProgOf = document.getElementById('barraProgresoImportacion');
        if (barraProgOf) {
            barraProgOf.classList.remove('bg-success', 'bg-warning');
            barraProgOf.classList.add('progress-bar-striped', 'progress-bar-animated', 'bg-danger');
            barraProgOf.style.width = '0%';
        }

        document.getElementById('btnEjecutarImportacion').disabled = true;
        document.getElementById('btnCerrarModalImportar').disabled = true;
        document.getElementById('btnCerrarFooter').disabled = true;

        const consola = document.getElementById('consolaImportacion');
        consola.innerHTML = `<div>[${new Date().toLocaleTimeString()}] Iniciando procesamiento de ${totalItems} productos en ${totalLotes} lotes...</div>`;

        // Procesar en chunks
        for (let i = 0; i < totalLotes; i++) {
            // Verificar si fue pausado
            while (importacionPausada) {
                await new Promise(r => setTimeout(r, 800));
            }

            const startIdx = i * CHUNK_SIZE;
            const endIdx = Math.min(startIdx + CHUNK_SIZE, totalItems);
            const chunkItems = itemsSeleccionados.slice(startIdx, endIdx);

            const numLote = i + 1;
            document.getElementById('loteActualTexto').innerText = `${numLote} / ${totalLotes}`;
            document.getElementById('textoEstadoProgreso').innerText = `Procesando lote ${numLote} de ${totalLotes} (${chunkItems.length} productos con Foto 1 WebP)...`;

            try {
                const reciclarFantasmas = document.getElementById('checkReciclarFantasmasOfertas')?.checked || false;
                const res = await fetch('{{ route("compras.deltron.importar.lote") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ items: chunkItems, reciclar_fantasmas: reciclarFantasmas })
                });

                const data = await res.json();

                if (data.status === 'ok') {
                    const exitoLote = data.data.importados_count || 0;
                    const erroresLote = (data.data.errores || []).length;
                    totalImportadosExitoCount += exitoLote;
                    totalImportadosErrorCount += erroresLote;

                    if (typeof data.data.fantasmas_disponibles !== 'undefined') {
                        const elFant = document.getElementById('conteoFantasmasOfertas');
                        if (elFant) elFant.innerText = data.data.fantasmas_disponibles;
                    }

                    (data.data.importados || []).forEach(imp => {
                        const row = document.querySelector(`tr[data-sku="${imp.sku}"]`);
                        if (row) {
                            row.classList.add('table-success');
                            const chk = row.querySelector('.check-candidato');
                            if (chk) chk.disabled = true;
                        }
                        const tagModo = imp.reciclado ? `[RECICLADO ID #${imp.idProducto} | Antes: ${imp.codigoAnterior}]` : `[NUEVO ID #${imp.idProducto}]`;
                        consola.innerHTML += `<div class="text-success">${tagModo} SKU: ${imp.sku} -> Código: ${imp.codigoProducto} (Foto: ${imp.foto1})</div>`;
                    });

                    (data.data.errores || []).forEach(err => {
                        consola.innerHTML += `<div class="text-danger">[ERROR] SKU: ${err.sku}: ${err.error}</div>`;
                    });
                } else {
                    totalImportadosErrorCount += chunkItems.length;
                    consola.innerHTML += `<div class="text-danger">[FALLO LOTE ${numLote}] ${data.message}</div>`;
                }
            } catch (err) {
                totalImportadosErrorCount += chunkItems.length;
                consola.innerHTML += `<div class="text-danger">[FALLO RED LOTE ${numLote}] ${err.message}</div>`;
            }

            // Actualizar contadores y barra
            document.getElementById('totalImportadosExito').innerText = totalImportadosExitoCount;
            document.getElementById('totalImportadosError').innerText = totalImportadosErrorCount;

            const porcentaje = Math.round(((i + 1) / totalLotes) * 100);
            document.getElementById('barraProgresoImportacion').style.width = `${porcentaje}%`;
            document.getElementById('barraProgresoImportacion').setAttribute('aria-valuenow', porcentaje);
            document.getElementById('textoPorcentajeProgreso').innerText = `${porcentaje}%`;

            consola.scrollTop = consola.scrollHeight;
        }

        // Finalización
        importacionEnCurso = false;
        importacionPausada = false;

        document.getElementById('spinnerProgresoOfertas')?.classList.add('d-none');
        document.getElementById('iconoExitoProgresoOfertas')?.classList.remove('d-none');
        document.getElementById('btnPausarImportacion')?.classList.add('d-none');

        const contProgFinOf = document.getElementById('contenedorEstadoProgreso');
        if (contProgFinOf) {
            contProgFinOf.classList.remove('text-danger');
            contProgFinOf.classList.add(totalImportadosErrorCount === 0 ? 'text-success' : 'text-warning');
        }

        document.getElementById('textoEstadoProgreso').innerText = totalImportadosErrorCount === 0
            ? `¡Importación masiva completada con éxito! ${totalImportadosExitoCount} creados.`
            : `Proceso finalizado: ${totalImportadosExitoCount} creados, ${totalImportadosErrorCount} errores.`;

        const barraFinOf = document.getElementById('barraProgresoImportacion');
        if (barraFinOf) {
            barraFinOf.classList.remove('progress-bar-animated', 'progress-bar-striped', 'bg-danger');
            barraFinOf.classList.add(totalImportadosErrorCount === 0 ? 'bg-success' : 'bg-warning');
            barraFinOf.style.width = '100%';
        }
        document.getElementById('textoPorcentajeProgreso').innerText = '100%';

        document.getElementById('btnCerrarModalImportar').disabled = false;
        document.getElementById('btnCerrarFooter').disabled = false;
        if (typeof actualizarConteoSeleccionados === 'function') {
            actualizarConteoSeleccionados();
        }

        consola.innerHTML += `<div class="text-success fw-bold mt-1">[✓ FINALIZADO] Todos los lotes han sido procesados al 100%.</div>`;
        consola.scrollTop = consola.scrollHeight;

        Swal.fire({
            title: '¡Importación Completada!',
            html: `Se procesaron todos los lotes.<br><br>` +
                  `<span class="badge bg-success fs-6 px-3 py-2 me-1">${totalImportadosExitoCount} Productos Creados</span>` +
                  (totalImportadosErrorCount > 0 ? `<span class="badge bg-danger fs-6 px-3 py-2">${totalImportadosErrorCount} Fallos</span>` : ''),
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-arrow-clockwise me-1"></i> Aceptar y Actualizar',
            cancelButtonText: 'Seguir en esta ventana',
            customClass: {
                confirmButton: 'btn btn-primary rounded-pill px-4',
                cancelButton: 'btn btn-outline-secondary rounded-pill px-3'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    }

    function togglePausarImportacion() {
        importacionPausada = !importacionPausada;
        const btn = document.getElementById('btnPausarImportacion');
        if (importacionPausada) {
            btn.innerHTML = '<i class="bi bi-play-fill"></i> Reanudar';
            btn.classList.replace('btn-outline-secondary', 'btn-outline-danger');
            document.getElementById('textoEstadoProgreso').innerText = '[Pausado por usuario] Haz clic en Reanudar para continuar.';
        } else {
            btn.innerHTML = '<i class="bi bi-pause-fill"></i> Pausar';
            btn.classList.replace('btn-outline-danger', 'btn-outline-secondary');
        }
    }
</script>

