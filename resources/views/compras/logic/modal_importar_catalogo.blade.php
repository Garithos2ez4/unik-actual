<script>
    // Variables de Estado del Catálogo Masivo Paginado
    let catalogoCandidatos = [];
    let gruposCatalogoGlobales = [];
    let paginaCatalogoActual = 1;
    let totalPaginasCatalogo = 1;
    let totalRegistrosCatalogo = 0;
    let filtroQueryCatalogo = '';
    let soloConStockCatalogo = false;
    let timerDebounceCatalogo = null;

    let importacionCatalogoEnCurso = false;
    let importacionCatalogoPausada = false;
    let totalCatalogoExitoCount = 0;
    let totalCatalogoErrorCount = 0;
    const CHUNK_SIZE_CATALOGO = 5; // Micro-lotes de 5 productos

    function abrirModalImportacionCatalogo() {
        const modalEl = document.getElementById('modalImportarCatalogo');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        if (catalogoCandidatos.length === 0) {
            cargarCandidatosCatalogo(1);
        }
    }

    function onInputFiltroCatalogo(val) {
        clearTimeout(timerDebounceCatalogo);
        timerDebounceCatalogo = setTimeout(() => {
            filtroQueryCatalogo = val.trim();
            cargarCandidatosCatalogo(1);
        }, 350);
    }

    function limpiarFiltroCatalogo() {
        document.getElementById('filtroTextoCatalogo').value = '';
        filtroQueryCatalogo = '';
        cargarCandidatosCatalogo(1);
    }

    function cambiarFiltroStock(checked) {
        soloConStockCatalogo = checked;
        cargarCandidatosCatalogo(1);
    }

    function irAPagina(pag) {
        if (pag < 1 || pag > totalPaginasCatalogo || pag === paginaCatalogoActual) return;
        cargarCandidatosCatalogo(pag);
    }

    function cargarCandidatosCatalogo(pagina = 1) {
        paginaCatalogoActual = pagina;
        document.getElementById('importarCatalogoCargando').classList.remove('d-none');
        document.getElementById('importarCatalogoError').classList.add('d-none');
        document.getElementById('importarCatalogoContenido').classList.add('d-none');

        const params = new URLSearchParams({
            page: pagina,
            query: filtroQueryCatalogo,
            solo_con_stock: soloConStockCatalogo ? '1' : '0'
        });

        fetch(`{{ route("compras.deltron.catalogo.candidatos") }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('importarCatalogoCargando').classList.add('d-none');
            if (data.status !== 'ok') {
                document.getElementById('importarCatalogoError').classList.remove('d-none');
                document.getElementById('importarCatalogoErrorMsg').innerText = data.message || 'Error al obtener candidatos';
                return;
            }

            catalogoCandidatos = data.items || [];
            gruposCatalogoGlobales = data.grupos || [];
            paginaCatalogoActual = data.page || 1;
            totalPaginasCatalogo = data.last_page || 1;
            totalRegistrosCatalogo = data.total || 0;
            if (typeof data.fantasmas_disponibles !== 'undefined') {
                const elFant = document.getElementById('conteoFantasmasCatalogo');
                if (elFant) elFant.innerText = data.fantasmas_disponibles;
            }

            renderizarPaginacionCatalogo();
            renderizarTablaCatalogo(catalogoCandidatos);
            document.getElementById('importarCatalogoContenido').classList.remove('d-none');
        })
        .catch(err => {
            document.getElementById('importarCatalogoCargando').classList.add('d-none');
            document.getElementById('importarCatalogoError').classList.remove('d-none');
            document.getElementById('importarCatalogoErrorMsg').innerText = 'Fallo de conexión: ' + err.message;
        });
    }

    function renderizarPaginacionCatalogo() {
        document.getElementById('textoPaginacion').innerText = `Pág. ${paginaCatalogoActual} de ${totalPaginasCatalogo}`;
        document.getElementById('badgeTotalCatalogo').innerText = `${totalRegistrosCatalogo.toLocaleString()} productos disponibles`;

        document.getElementById('btnPaginaPrimera').disabled = (paginaCatalogoActual <= 1);
        document.getElementById('btnPaginaAnterior').disabled = (paginaCatalogoActual <= 1);
        document.getElementById('btnPaginaSiguiente').disabled = (paginaCatalogoActual >= totalPaginasCatalogo);
        document.getElementById('btnPaginaUltima').disabled = (paginaCatalogoActual >= totalPaginasCatalogo);
    }

    function renderizarTablaCatalogo(items) {
        const tbody = document.getElementById('tbodyCandidatosCatalogo');
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                        No se encontraron productos candidatos para importar con los filtros seleccionados.
                    </td>
                </tr>
            `;
            document.getElementById('btnEjecutarImportacionCatalogo').disabled = true;
            document.getElementById('conteoSeleccionadosCatalogo').innerText = '0';
            document.getElementById('conteoBotonImportar').innerText = '0';
            return;
        }

        document.getElementById('btnEjecutarImportacionCatalogo').disabled = false;

        const gruposOptionsHtml = gruposCatalogoGlobales.map(g => 
            `<option value="${g.idGrupo}">${g.nombreGrupo} (${g.nombreCategoria})</option>`
        ).join('');

        items.forEach((it, idx) => {
            const tr = document.createElement('tr');
            tr.id = `fila-catalogo-${idx}`;
            tr.setAttribute('data-sku', it.sku);

            const promoBadge = it.price_promo ? 
                `<span class="badge bg-danger rounded-pill px-2 py-0 ms-1" style="font-size: 0.72rem;">Promo: $${Number(it.price_promo).toFixed(2)}</span>` : '';

            const stockLimaBadge = it.lima_hay_mas ? 
                `<span class="badge bg-success rounded-pill px-2 py-1"><i class="bi bi-plus-circle me-1"></i>+100 (Lima)</span>` : 
                `<span class="badge ${it.lima_stock > 0 ? 'bg-success' : 'bg-secondary'} rounded-pill px-2 py-1">${it.lima_stock} en Lima</span>`;

            const varianteBadge = it.es_variante ? 
                `<span class="badge bg-info text-dark rounded-pill px-2 py-0 ms-1" style="font-size: 0.72rem;" title="Detectado como variante de ${it.modelo_base}">
                    <i class="bi bi-palette me-1"></i>Variante: ${it.valor_variante}
                 </span>` : '';

            const fotoPreview = (it.foto1_url && String(it.foto1_url).trim() !== '' && !String(it.foto1_url).includes('blanco.jpg'))
                ? `<img src="${it.foto1_url}" alt="Foto" class="rounded border shadow-xs" style="width: 46px; height: 46px; object-fit: contain; background: #fff;" onerror="this.onerror=null; this.src='{{ asset('storage/noimagen.webp') }}';">`
                : `<img src="{{ asset('storage/noimagen.webp') }}" alt="Foto" class="rounded border shadow-xs" style="width: 46px; height: 46px; object-fit: contain; opacity: 0.6;">`;

            tr.innerHTML = `
                <td class="text-center">
                    <input class="form-check-input check-candidato-catalogo" type="checkbox" data-idx="${idx}" checked onchange="actualizarConteoCatalogo()">
                </td>
                <td class="text-center">
                    ${fotoPreview}
                </td>
                <td>
                    <div class="fw-bold text-dark d-flex align-items-center flex-wrap gap-1">
                        <span>${it.name}</span>
                        ${varianteBadge}
                    </div>
                    <div class="small text-muted font-monospace mt-1">
                        <span class="text-primary fw-semibold">${it.sku}</span> | 
                        PN: <span class="text-dark">${it.part_number}</span> | 
                        Marca: <span class="badge bg-light text-dark border">${it.brand_name}</span>
                    </div>
                </td>
                <td>
                    <select class="form-select form-select-sm select-grupo-catalogo shadow-xs" data-idx="${idx}" onchange="cambiarGrupoItemCatalogo(${idx}, this.value)">
                        ${gruposOptionsHtml}
                    </select>
                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                        Sugerido: <span class="text-primary fw-semibold">${it.nombreGrupo}</span>
                    </small>
                </td>
                <td class="text-center">
                    <div class="fw-bold text-dark">$${Number(it.price).toFixed(2)}</div>
                    ${promoBadge}
                </td>
                <td class="text-center">
                    ${stockLimaBadge}
                    <div class="text-muted mt-1" style="font-size: 0.72rem;">Total: ${it.total_stock}</div>
                </td>
            `;

            tbody.appendChild(tr);

            const select = tr.querySelector('.select-grupo-catalogo');
            if (select) {
                select.value = it.idGrupo;
            }
        });

        actualizarConteoCatalogo();
    }

    function cambiarGrupoItemCatalogo(idx, nuevoIdGrupo) {
        if (catalogoCandidatos[idx]) {
            catalogoCandidatos[idx].idGrupo = parseInt(nuevoIdGrupo);
            const g = gruposCatalogoGlobales.find(gr => gr.idGrupo === parseInt(nuevoIdGrupo));
            if (g) {
                catalogoCandidatos[idx].nombreGrupo = g.nombreGrupo;
                catalogoCandidatos[idx].idCategoria = g.idCategoria;
                catalogoCandidatos[idx].nombreCategoria = g.nombreCategoria;
            }
        }
    }

    function toggleSeleccionarTodosCatalogo(marcar) {
        document.querySelectorAll('.check-candidato-catalogo').forEach(chk => {
            if (!chk.disabled) chk.checked = marcar;
        });
        actualizarConteoCatalogo();
    }

    function actualizarConteoCatalogo() {
        const seleccionados = document.querySelectorAll('.check-candidato-catalogo:checked').length;
        document.getElementById('conteoSeleccionadosCatalogo').innerText = seleccionados;
        document.getElementById('conteoBotonImportar').innerText = seleccionados;
        document.getElementById('btnEjecutarImportacionCatalogo').disabled = (seleccionados === 0 || importacionCatalogoEnCurso);
    }

    function togglePausarImportacionCatalogo() {
        importacionCatalogoPausada = !importacionCatalogoPausada;
        const btn = document.getElementById('btnPausarImportacionCatalogo');
        if (importacionCatalogoPausada) {
            btn.innerHTML = '<i class="bi bi-play-fill"></i> Reanudar';
            btn.classList.replace('btn-outline-secondary', 'btn-warning');
        } else {
            btn.innerHTML = '<i class="bi bi-pause-fill"></i> Pausar';
            btn.classList.replace('btn-warning', 'btn-outline-secondary');
        }
    }

    // ==============================================================
    // MOTOR DE IMPORTACION POR MICRO-LOTES AJAX
    // ==============================================================
    async function iniciarImportacionMasivaCatalogo() {
        const checks = Array.from(document.querySelectorAll('.check-candidato-catalogo:checked'));
        if (checks.length === 0) {
            Swal.fire('Atención', 'No has seleccionado ningún producto para importar de esta página.', 'warning');
            return;
        }

        const itemsSeleccionados = checks.map(c => {
            const idx = parseInt(c.getAttribute('data-idx'));
            return catalogoCandidatos[idx];
        });

        const totalItems = itemsSeleccionados.length;
        const totalLotes = Math.ceil(totalItems / CHUNK_SIZE_CATALOGO);

        const confirmacion = await Swal.fire({
            title: '¿Iniciar importación masiva?',
            html: `Se importarán <b>${totalItems} productos</b> en <b>${totalLotes} micro-lotes</b>.<br><br>` +
                  `<small class="text-muted">Se descargará la <b>Foto 1</b> convertida a WebP 800x800 y se guardará la <b>descripción completa con especificaciones técnicas</b>.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, Comenzar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-primary rounded-pill px-4',
                cancelButton: 'btn btn-secondary rounded-pill px-4'
            }
        });

        if (!confirmacion.isConfirmed) return;

        // Iniciar interfaz de progreso
        importacionCatalogoEnCurso = true;
        importacionCatalogoPausada = false;
        totalCatalogoExitoCount = 0;
        totalCatalogoErrorCount = 0;

        document.getElementById('panelProgresoImportacionCatalogo').classList.remove('d-none');
        document.getElementById('spinnerProgresoCatalogo')?.classList.remove('d-none');
        document.getElementById('iconoExitoProgresoCatalogo')?.classList.add('d-none');
        document.getElementById('btnPausarImportacionCatalogo')?.classList.remove('d-none');
        
        const contProg = document.getElementById('contenedorEstadoProgresoCatalogo');
        if (contProg) {
            contProg.classList.remove('text-success', 'text-warning');
            contProg.classList.add('text-primary');
        }
        const barraProg = document.getElementById('barraProgresoImportacionCatalogo');
        if (barraProg) {
            barraProg.classList.remove('bg-success', 'bg-warning');
            barraProg.classList.add('progress-bar-striped', 'progress-bar-animated', 'bg-primary');
            barraProg.style.width = '0%';
        }

        document.getElementById('btnEjecutarImportacionCatalogo').disabled = true;
        document.getElementById('btnCerrarModalCatalogo').disabled = true;
        document.getElementById('btnCerrarFooterCatalogo').disabled = true;

        const consola = document.getElementById('consolaImportacionCatalogo');
        consola.innerHTML = `<div>[${new Date().toLocaleTimeString()}] Iniciando procesamiento de ${totalItems} productos en ${totalLotes} lotes de catálogo...</div>`;

        // Procesar en chunks
        for (let i = 0; i < totalLotes; i++) {
            while (importacionCatalogoPausada) {
                await new Promise(r => setTimeout(r, 800));
            }

            const startIdx = i * CHUNK_SIZE_CATALOGO;
            const endIdx = Math.min(startIdx + CHUNK_SIZE_CATALOGO, totalItems);
            const chunkItems = itemsSeleccionados.slice(startIdx, endIdx);

            const numLote = i + 1;
            document.getElementById('loteActualTextoCatalogo').innerText = `${numLote} / ${totalLotes}`;
            document.getElementById('textoEstadoProgresoCatalogo').innerText = `Procesando lote ${numLote} de ${totalLotes} (${chunkItems.length} productos con Foto WebP y Specs)...`;

            try {
                const reciclarFantasmas = document.getElementById('checkReciclarFantasmasCatalogo')?.checked || false;
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
                    totalCatalogoExitoCount += exitoLote;
                    totalCatalogoErrorCount += erroresLote;

                    if (typeof data.data.fantasmas_disponibles !== 'undefined') {
                        const elFant = document.getElementById('conteoFantasmasCatalogo');
                        if (elFant) elFant.innerText = data.data.fantasmas_disponibles;
                    }

                    (data.data.importados || []).forEach(imp => {
                        const row = document.querySelector(`tr[data-sku="${imp.sku}"]`);
                        if (row) {
                            row.classList.add('table-success');
                            const chk = row.querySelector('.check-candidato-catalogo');
                            if (chk) {
                                chk.checked = false;
                                chk.disabled = true;
                            }
                        }
                        const tagModo = imp.reciclado ? `[RECICLADO ID #${imp.idProducto} | Antes: ${imp.codigoAnterior}]` : `[NUEVO ID #${imp.idProducto}]`;
                        consola.innerHTML += `<div class="text-success">${tagModo} SKU: ${imp.sku} -> Código: ${imp.codigoProducto} (Foto: ${imp.foto1})</div>`;
                    });

                    (data.data.errores || []).forEach(err => {
                        consola.innerHTML += `<div class="text-danger">[ERROR] SKU: ${err.sku}: ${err.error}</div>`;
                    });
                } else {
                    totalCatalogoErrorCount += chunkItems.length;
                    consola.innerHTML += `<div class="text-danger">[FALLO LOTE ${numLote}] ${data.message}</div>`;
                }
            } catch (err) {
                totalCatalogoErrorCount += chunkItems.length;
                consola.innerHTML += `<div class="text-danger">[FALLO RED LOTE ${numLote}] ${err.message}</div>`;
            }

            // Actualizar contadores y barra
            document.getElementById('totalImportadosExitoCatalogo').innerText = totalCatalogoExitoCount;
            document.getElementById('totalImportadosErrorCatalogo').innerText = totalCatalogoErrorCount;

            const porcentaje = Math.round(((i + 1) / totalLotes) * 100);
            document.getElementById('barraProgresoImportacionCatalogo').style.width = `${porcentaje}%`;
            document.getElementById('barraProgresoImportacionCatalogo').setAttribute('aria-valuenow', porcentaje);
            document.getElementById('textoPorcentajeProgresoCatalogo').innerText = `${porcentaje}%`;

            consola.scrollTop = consola.scrollHeight;
            actualizarConteoCatalogo();
        }

        // Finalización
        importacionCatalogoEnCurso = false;
        importacionCatalogoPausada = false;

        document.getElementById('spinnerProgresoCatalogo')?.classList.add('d-none');
        document.getElementById('iconoExitoProgresoCatalogo')?.classList.remove('d-none');
        document.getElementById('btnPausarImportacionCatalogo')?.classList.add('d-none');

        const contProgFin = document.getElementById('contenedorEstadoProgresoCatalogo');
        if (contProgFin) {
            contProgFin.classList.remove('text-primary');
            contProgFin.classList.add(totalCatalogoErrorCount === 0 ? 'text-success' : 'text-warning');
        }

        document.getElementById('textoEstadoProgresoCatalogo').innerText = totalCatalogoErrorCount === 0
            ? `¡Importación finalizada con éxito! ${totalCatalogoExitoCount} creados.`
            : `Proceso finalizado: ${totalCatalogoExitoCount} creados, ${totalCatalogoErrorCount} errores.`;

        const barraFin = document.getElementById('barraProgresoImportacionCatalogo');
        if (barraFin) {
            barraFin.classList.remove('progress-bar-animated', 'progress-bar-striped', 'bg-primary');
            barraFin.classList.add(totalCatalogoErrorCount === 0 ? 'bg-success' : 'bg-warning');
            barraFin.style.width = '100%';
        }
        document.getElementById('textoPorcentajeProgresoCatalogo').innerText = '100%';

        document.getElementById('btnCerrarModalCatalogo').disabled = false;
        document.getElementById('btnCerrarFooterCatalogo').disabled = false;
        actualizarConteoCatalogo();

        consola.innerHTML += `<div class="text-success fw-bold mt-1">[✓ FINALIZADO] Todos los lotes han sido procesados al 100%.</div>`;
        consola.scrollTop = consola.scrollHeight;

        Swal.fire({
            title: '¡Importación de Lote Completada!',
            html: `Se crearon exitosamente <b>${totalCatalogoExitoCount} productos</b> en tu catálogo con Foto WebP y especificaciones técnicas.<br>` +
                  (totalCatalogoErrorCount > 0 ? `<span class="text-danger">Hubo ${totalCatalogoErrorCount} errores.</span>` : ''),
            icon: totalCatalogoExitoCount > 0 ? 'success' : 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-arrow-clockwise me-1"></i> Actualizar Página',
            cancelButtonText: 'Seguir en el Catálogo',
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
</script>

