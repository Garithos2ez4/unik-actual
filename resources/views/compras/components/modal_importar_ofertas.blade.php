<!-- ============================================================== -->
<!-- MODAL: IMPORTACION MASIVA DE OFERTAS DELTRON (+100 STOCK LIMA) -->
<!-- ============================================================== -->
<div class="modal fade" id="modalImportarOfertas" tabindex="-1" aria-labelledby="modalImportarOfertasLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 92%;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #b02a37 0%, #d63384 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 me-3">
                        <i class="bi bi-box-arrow-in-down fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalImportarOfertasLabel">
                            Importación Masiva de Ofertas Deltron (+100 Stock Lima)
                        </h5>
                        <small class="text-white-50">
                            Detecta automáticamente ofertas con stock masivo y asigna Grupos, Categorías y Foto 1 en WebP 800x800
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCerrarModalImportar"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 bg-light">
                <!-- Estado 1: Cargando Candidatos -->
                <div id="importarOfertasCargando" class="text-center py-5">
                    <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5 class="fw-bold text-dark">Analizando Catálogo Deltron...</h5>
                    <p class="text-muted small mb-0">
                        Cruzando productos en oferta y stock masivo con tu catálogo para filtrar únicamente los que no existen.
                    </p>
                </div>

                <!-- Estado 2: Error al Cargar -->
                <div id="importarOfertasError" class="alert alert-danger d-none my-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-octagon-fill fs-3 me-3 text-danger"></i>
                        <div>
                            <strong id="importarOfertasErrorMsg">Ocurrió un error al consultar las ofertas.</strong>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="cargarCandidatosOfertas()">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado 3: Contenido Principal (Tabla de Candidatos + Progreso) -->
                <div id="importarOfertasContenido" class="d-none">
                    <!-- Banner Informativo de Arquitectura Liviana -->
                    <div class="alert alert-info border-0 shadow-xs py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center small">
                            <i class="bi bi-shield-check fs-5 text-primary me-2"></i>
                            <div>
                                <strong>Arquitectura de Importación Segura:</strong> Se procesa en micro-lotes de <strong>5 productos</strong>.
                                Solo descarga la <strong>Foto 1 (Principal)</strong> optimizada a WebP (800x800) y libera RAM en cada lote para no saturar el servidor.
                            </div>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3 py-1 font-monospace" id="badgeTotalCandidatos">
                            0 productos nuevos
                        </span>
                    </div>

                    <!-- Panel de Ejecución de Progreso (Oculto al inicio) -->
                    <div id="panelProgresoImportacion" class="card border-danger border-2 shadow-sm mb-3 d-none">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-danger d-flex align-items-center">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    <span id="textoEstadoProgreso">Iniciando importación masiva...</span>
                                </span>
                                <span class="fw-bold text-dark font-monospace" id="textoPorcentajeProgreso">0%</span>
                            </div>
                            <div class="progress mb-2" style="height: 16px;">
                                <div id="barraProgresoImportacion" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <div>
                                    Lote actual: <strong id="loteActualTexto">0 / 0</strong> | 
                                    Importados con éxito: <strong class="text-success" id="totalImportadosExito">0</strong> | 
                                    Errores: <strong class="text-danger" id="totalImportadosError">0</strong>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnPausarImportacion" onclick="togglePausarImportacion()">
                                        <i class="bi bi-pause-fill"></i> Pausar
                                    </button>
                                </div>
                            </div>
                            <!-- Consola de registro en vivo -->
                            <div id="consolaImportacion" class="bg-dark text-white-50 p-2 rounded mt-2 font-monospace small" style="max-height: 110px; overflow-y: auto; font-size: 0.78rem;">
                                <div>[Sistema] Esperando inicio de procesamiento de lotes...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros Rápidos de la Tabla -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check ms-2">
                                    <input class="form-check-input" type="checkbox" id="checkSeleccionarTodos" checked onchange="toggleSeleccionarTodos(this.checked)">
                                    <label class="form-check-label fw-bold small text-dark" for="checkSeleccionarTodos">
                                        Seleccionar Todos (<span id="conteoSeleccionados">0</span>)
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="max-width: 400px; width: 100%;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="filtroTablaCandidatos" class="form-control" placeholder="Buscar por título, SKU, marca o grupo..." oninput="filtrarTablaCandidatos()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('filtroTablaCandidatos').value=''; filtrarTablaCandidatos();">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Candidatos -->
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="table-responsive" style="max-height: 480px;">
                            <table class="table table-hover table-striped align-middle mb-0" id="tablaCandidatosOfertas">
                                <thead class="table-light sticky-top small text-uppercase text-secondary">
                                    <tr>
                                        <th width="4%" class="text-center">#</th>
                                        <th width="8%" class="text-center">Foto 1</th>
                                        <th width="32%">Producto Deltron</th>
                                        <th width="28%">Grupo / Categoría Sugerido</th>
                                        <th width="14%" class="text-center">Precios (US$)</th>
                                        <th width="14%" class="text-center">Stock Lima</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyCandidatosOfertas" class="small">
                                    <!-- Se inyecta dinamicamente via JS -->
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
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" id="btnCerrarFooter">
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold shadow-xs" id="btnEjecutarImportacion" onclick="iniciarImportacionMasiva()">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Iniciar Importación Masiva
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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
            const fotoPreview = item.foto1_url 
                ? `<img src="${item.foto1_url}" class="rounded border shadow-xs" style="width: 48px; height: 48px; object-fit: contain; background: #fff;" onerror="this.src='{{ asset('storage/noimagen.webp') }}'">`
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
                    totalImportadosExitoCount += exitoLote;
                    totalImportadosErrorCount += erroresLote;

                    (data.data.importados || []).forEach(imp => {
                        const row = document.querySelector(`tr[data-sku="${imp.sku}"]`);
                        if (row) {
                            row.classList.add('table-success');
                            const chk = row.querySelector('.check-candidato');
                            if (chk) chk.disabled = true;
                        }
                        consola.innerHTML += `<div class="text-success">[OK] SKU: ${imp.sku} -> Código: ${imp.codigoProducto} (Foto: ${imp.foto1})</div>`;
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
        document.getElementById('btnCerrarModalImportar').disabled = false;
        document.getElementById('btnCerrarFooter').disabled = false;
        document.getElementById('textoEstadoProgreso').innerText = '¡Importación masiva completada con éxito!';

        Swal.fire({
            title: '¡Importación Completada!',
            html: `Se procesaron todos los lotes.<br><br>` +
                  `<span class="badge bg-success fs-6 px-3 py-2 me-1">${totalImportadosExitoCount} Productos Creados</span>` +
                  (totalImportadosErrorCount > 0 ? `<span class="badge bg-danger fs-6 px-3 py-2">${totalImportadosErrorCount} Fallos</span>` : ''),
            icon: 'success',
            confirmButtonText: 'Aceptar y Actualizar',
            customClass: {
                confirmButton: 'btn btn-primary rounded-pill px-4'
            }
        }).then(() => {
            window.location.reload();
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
