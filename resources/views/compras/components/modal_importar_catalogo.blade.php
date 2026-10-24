<!-- ============================================================== -->
<!-- MODAL: IMPORTACION MASIVA DEL CATALOGO DELTRON (100 EN 100)   -->
<!-- ============================================================== -->
<div class="modal fade" id="modalImportarCatalogo" tabindex="-1" aria-labelledby="modalImportarCatalogoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 94%;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0aa2c0 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 me-3">
                        <i class="bi bi-collection-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalImportarCatalogoLabel">
                            Importación Masiva del Catálogo Deltron (Lotes de 100)
                        </h5>
                        <small class="text-white-70">
                            Explora todo el catálogo de Deltron sin congelar el navegador. Filtra, asigna grupos y crea productos con Foto WebP y especificaciones técnicas.
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarModalCatalogo"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 bg-light">
                <!-- Estado 1: Cargando Candidatos -->
                <div id="importarCatalogoCargando" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5 class="fw-bold text-dark">Consultando Catálogo Deltron (Paginación Ligera)...</h5>
                    <p class="text-muted small mb-0">
                        Cargando lote de 100 productos candidatos optimizados en memoria para máxima velocidad.
                    </p>
                </div>

                <!-- Estado 2: Error al Cargar -->
                <div id="importarCatalogoError" class="alert alert-danger d-none my-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-octagon-fill fs-3 me-3 text-danger"></i>
                        <div>
                            <strong id="importarCatalogoErrorMsg">Ocurrió un error al consultar el catálogo.</strong>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="cargarCandidatosCatalogo(paginaCatalogoActual)">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado 3: Contenido Principal (Tabla de Candidatos + Progreso) -->
                <div id="importarCatalogoContenido" class="d-none">
                    <!-- Banner Informativo de Arquitectura Liviana -->
                    <div class="alert alert-primary border-0 shadow-xs py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center small">
                            <i class="bi bi-cpu fs-5 text-primary me-2"></i>
                            <div>
                                <strong>Arquitectura de Bajo Consumo:</strong> Se cargan exactamente <strong>100 productos por página</strong> para no saturar memoria RAM ni el navegador.
                                La creación masiva procesa en micro-lotes de <strong>5 productos</strong> con Foto 1 WebP 800x800 y descripción técnica completa.
                            </div>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-1 font-monospace" id="badgeTotalCatalogo">
                            0 productos disponibles
                        </span>
                    </div>

                    <!-- Panel de Ejecución de Progreso (Oculto al inicio) -->
                    <div id="panelProgresoImportacionCatalogo" class="card border-primary border-2 shadow-sm mb-3 d-none">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-primary d-flex align-items-center">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    <span id="textoEstadoProgresoCatalogo">Iniciando importación masiva...</span>
                                </span>
                                <span class="fw-bold text-dark font-monospace" id="textoPorcentajeProgresoCatalogo">0%</span>
                            </div>
                            <div class="progress mb-2" style="height: 16px;">
                                <div id="barraProgresoImportacionCatalogo" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <div>
                                    Lote actual: <strong id="loteActualTextoCatalogo">0 / 0</strong> | 
                                    Importados con éxito: <strong class="text-success" id="totalImportadosExitoCatalogo">0</strong> | 
                                    Errores: <strong class="text-danger" id="totalImportadosErrorCatalogo">0</strong>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnPausarImportacionCatalogo" onclick="togglePausarImportacionCatalogo()">
                                        <i class="bi bi-pause-fill"></i> Pausar
                                    </button>
                                </div>
                            </div>
                            <!-- Consola de registro en vivo -->
                            <div id="consolaImportacionCatalogo" class="bg-dark text-white-50 p-2 rounded mt-2 font-monospace small" style="max-height: 110px; overflow-y: auto; font-size: 0.78rem;">
                                <div>[Sistema] Esperando inicio de procesamiento de lotes...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Filtros y Paginación Superior -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <!-- Checkbox Seleccionar Todos y Solo Stock -->
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check ms-2 mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkSeleccionarTodosCatalogo" checked onchange="toggleSeleccionarTodosCatalogo(this.checked)">
                                    <label class="form-check-label fw-bold small text-dark" for="checkSeleccionarTodosCatalogo">
                                        Seleccionar Visibles (<span id="conteoSeleccionadosCatalogo">0</span>)
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkSoloConStock" onchange="cambiarFiltroStock(this.checked)">
                                    <label class="form-check-label small text-muted" for="checkSoloConStock">
                                        <i class="bi bi-box-seam text-success me-1"></i> Solo con stock disponible
                                    </label>
                                </div>
                            </div>

                            <!-- Buscador en tiempo real con debounce -->
                            <div class="d-flex align-items-center gap-2" style="max-width: 380px; width: 100%;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="filtroTextoCatalogo" class="form-control" placeholder="Buscar por modelo, SKU, marca..." oninput="onInputFiltroCatalogo(this.value)">
                                    <button class="btn btn-outline-secondary" type="button" onclick="limpiarFiltroCatalogo()">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Paginador de 100 en 100 -->
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaPrimera" onclick="irAPagina(1)" title="Primera página">
                                    <i class="bi bi-chevron-double-left"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaAnterior" onclick="irAPagina(paginaCatalogoActual - 1)" title="Página anterior">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span class="px-2 small fw-semibold text-secondary" id="textoPaginacion">
                                    Pág. 1 de 1
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaSiguiente" onclick="irAPagina(paginaCatalogoActual + 1)" title="Página siguiente">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPaginaUltima" onclick="irAPagina(totalPaginasCatalogo)" title="Última página">
                                    <i class="bi bi-chevron-double-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Candidatos (100 filas) -->
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="table-responsive" style="max-height: 480px;">
                            <table class="table table-hover table-striped align-middle mb-0" id="tablaCandidatosCatalogo">
                                <thead class="table-light sticky-top small text-uppercase text-secondary">
                                    <tr>
                                        <th width="4%" class="text-center">#</th>
                                        <th width="8%" class="text-center">Foto 1</th>
                                        <th width="32%">Producto Deltron</th>
                                        <th width="28%">Grupo / Categoría Sugerido</th>
                                        <th width="14%" class="text-center">Precios (US$)</th>
                                        <th width="14%" class="text-center">Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyCandidatosCatalogo" class="small">
                                    <!-- Inyectado dinamicamente via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-2 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    Puedes cambiar el grupo de cualquier producto antes de ejecutar la importación.
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" id="btnCerrarFooterCatalogo">
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-xs" id="btnEjecutarImportacionCatalogo" onclick="iniciarImportacionMasivaCatalogo()">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Iniciar Importación (<span id="conteoBotonImportar">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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

            tr.innerHTML = `
                <td class="text-center">
                    <input class="form-check-input check-candidato-catalogo" type="checkbox" data-idx="${idx}" checked onchange="actualizarConteoCatalogo()">
                </td>
                <td class="text-center">
                    <img src="${it.foto1_url}" alt="Foto" class="rounded border shadow-xs" style="width: 46px; height: 46px; object-fit: contain; background: #fff;" onerror="this.src='/storage/images/noimagen.webp'">
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
                const res = await fetch('{{ route("compras.deltron.importar.lote") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ items: chunkItems })
                });

                const data = await res.json();

                if (data.status === 'ok') {
                    const exitoLote = data.data.importados_count || 0;
                    const erroresLote = (data.data.errores || []).length;
                    totalCatalogoExitoCount += exitoLote;
                    totalCatalogoErrorCount += erroresLote;

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
                        consola.innerHTML += `<div class="text-success">[OK] SKU: ${imp.sku} -> Código: ${imp.codigoProducto} (Foto: ${imp.foto1})</div>`;
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
        document.getElementById('textoEstadoProgresoCatalogo').innerText = `Proceso finalizado: ${totalCatalogoExitoCount} creados, ${totalCatalogoErrorCount} errores.`;
        document.getElementById('barraProgresoImportacionCatalogo').classList.remove('progress-bar-animated');
        document.getElementById('btnCerrarModalCatalogo').disabled = false;
        document.getElementById('btnCerrarFooterCatalogo').disabled = false;
        document.getElementById('btnEjecutarImportacionCatalogo').disabled = false;

        Swal.fire({
            title: '¡Importación de Lote Completada!',
            html: `Se crearon exitosamente <b>${totalCatalogoExitoCount} productos</b> en tu catálogo con Foto WebP y especificaciones técnicas.<br>` +
                  (totalCatalogoErrorCount > 0 ? `<span class="text-danger">Hubo ${totalCatalogoErrorCount} errores.</span>` : ''),
            icon: totalCatalogoExitoCount > 0 ? 'success' : 'warning',
            confirmButtonText: 'Entendido'
        });
    }
</script>
