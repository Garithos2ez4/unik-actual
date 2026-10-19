<script>
    // Función para copiar texto al portapapeles con feedback visual
    function copyToClipboard(text, elem) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(function() {
            if (elem) {
                const originalClass = elem.className;
                elem.className = 'bi bi-check-lg text-success';
                setTimeout(() => { elem.className = originalClass; }, 1500);
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Copiado: ' + text,
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        });
    }

    // Filtrador interactivo en vivo de promociones
    document.addEventListener('DOMContentLoaded', function() {
        const promoInput = document.getElementById('promoSearchInput');
        if (promoInput) {
            promoInput.addEventListener('keyup', filterPromociones);
        }

        // Listener para los botones de Ver Almacenes
        document.querySelectorAll('.btn-check-stock').forEach(button => {
            button.addEventListener('click', function() {
                const sku = this.getAttribute('data-sku') || '';
                const pn = this.getAttribute('data-pn') || '';
                const title = this.getAttribute('data-title') || this.getAttribute('data-name') || 'Detalle del Producto';
                const initialPrice = this.getAttribute('data-price') || '';

                abrirModalAlmacenes(sku, pn, title, initialPrice, this);
            });
        });

        // Listener para botones de Ver Combo / Detalle Promoción (API-0000025)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-promo-detail');
            if (btn) {
                const promoId = btn.getAttribute('data-promo-id');
                if (promoId) {
                    abrirModalPromocionDetalle(promoId, false);
                }
            }
        });

        // Listener para los botones de Ver Ficha Técnica (API-0000027)
        document.querySelectorAll('.btn-view-ficha').forEach(button => {
            button.addEventListener('click', function() {
                const sku = this.getAttribute('data-sku') || '';
                const pn = this.getAttribute('data-pn') || '';
                abrirModalFichaSku(sku, pn, false, this);
            });
        });

        // Listener para forzar refresco de ficha técnica desde API Deltron
        const btnForceFicha = document.getElementById('btnForceFichaRefresh');
        if (btnForceFicha) {
            btnForceFicha.addEventListener('click', function() {
                if (window.currentActiveFichaSku) {
                    abrirModalFichaSku(window.currentActiveFichaSku, window.currentActiveFichaPn, true);
                }
            });
        }

        // Listener para botón "Ver Ficha Técnica" desde el modal de almacenes
        const almVerFichaBtn = document.getElementById('almVerFichaBtn');
        if (almVerFichaBtn) {
            almVerFichaBtn.addEventListener('click', function() {
                const modalAlm = bootstrap.Modal.getInstance(document.getElementById('modalAlmacenes'));
                if (modalAlm) modalAlm.hide();
                abrirModalFichaSku(window.almCurrentSku, window.almCurrentPn, false);
            });
        }

        // Listener para forzar refresco de promoción desde API Deltron
        const btnForce = document.getElementById('btnForcePromoRefresh');
        if (btnForce) {
            btnForce.addEventListener('click', function() {
                if (window.currentActivePromoId) {
                    abrirModalPromocionDetalle(window.currentActivePromoId, true);
                }
            });
        }
    });

    function filterPromociones() {
        const term = document.getElementById('promoSearchInput').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#promosTable tbody .promo-row');
        let visible = 0;

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.includes(term)) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        const countEl = document.getElementById('promoVisibleCount');
        if (countEl) countEl.innerText = visible;
    }

    // Función para abrir y consultar almacenes en tiempo real (almacena en Inventario_Proveedor)
    function abrirModalAlmacenes(sku, pn, title, initialPrice, btnTrigger) {
        window.almCurrentSku = sku;
        window.almCurrentPn = pn;

        const modalEl = document.getElementById('modalAlmacenes');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Reset UI
        document.getElementById('almProdTitle').innerText = title;
        document.getElementById('almProdSku').innerText = sku || '-';
        document.getElementById('almProdPn').innerText = pn || '-';
        document.getElementById('almLoading').style.display = 'block';
        document.getElementById('almContent').style.display = 'none';
        document.getElementById('almError').style.display = 'none';

        // Configurar enlace de cotizador
        const cotizarBtn = document.getElementById('almCotizarBtn');
        const queryCotizar = pn && pn !== '-' ? pn : sku;
        cotizarBtn.href = "{{ route('calculadora.cotizador') }}?search=" + encodeURIComponent(queryCotizar);

        modal.show();

        // Hacer petición AJAX a DeltronController::consultarTiempoReal
        const queryParam = sku || pn;
        fetch(`/compras/deltron/consultar-tiempo-real?sku=${encodeURIComponent(queryParam)}&part_number=${encodeURIComponent(pn)}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('almLoading').style.display = 'none';

                if (data.status === 'ok') {
                    document.getElementById('almContent').style.display = 'block';

                    // Calcular precios regulares y de promoción (API-0000030)
                    let precioVal = 0;
                    let precioPromoVal = 0;
                    if (data.precio) {
                        if (data.precio.price) precioVal = parseFloat(data.precio.price);
                        if (data.precio.price_promo) precioPromoVal = parseFloat(data.precio.price_promo);
                    } else if (initialPrice) {
                        precioVal = parseFloat(initialPrice);
                    }

                    const almPrecioUsd = document.getElementById('almPrecioUsd');
                    const almPrecioIgv = document.getElementById('almPrecioIgv');
                    const almCardPromoBox = document.getElementById('almCardPromoBox');
                    const almPrecioPromoUsd = document.getElementById('almPrecioPromoUsd');
                    const almCardPromoSub = document.getElementById('almCardPromoSub');
                    const almPromoAlert = document.getElementById('almPromoAlert');

                    almPrecioUsd.innerText = precioVal > 0 ? '$' + precioVal.toFixed(2) : '-';
                    almPrecioIgv.innerText = precioVal > 0 ? '$' + (precioVal * 1.18).toFixed(2) : '-';

                    if (precioPromoVal > 0 && precioPromoVal < precioVal) {
                        const ahorro = precioVal - precioPromoVal;
                        almPrecioPromoUsd.innerText = '$' + precioPromoVal.toFixed(2);
                        almPrecioPromoUsd.className = 'fs-4 fw-bold text-success';
                        almCardPromoSub.innerHTML = `+ IGV: <span class="fw-semibold text-dark">$${(precioPromoVal * 1.18).toFixed(2)}</span> <span class="badge bg-danger rounded-pill ms-1">Ahorro $${ahorro.toFixed(2)}</span>`;
                        almCardPromoBox.className = 'p-2 border border-success rounded text-center bg-success bg-opacity-10 h-100 shadow-xs';
                        almPrecioUsd.style.textDecoration = 'line-through';
                        almPrecioUsd.className = 'fs-4 fw-bold text-muted';
                        
                        if (almPromoAlert) {
                            almPromoAlert.style.display = 'flex';
                            document.getElementById('almPromoDetails').innerText = `Precio especial $${precioPromoVal.toFixed(2)} USD (sin IGV) activo. Ahorro de $${ahorro.toFixed(2)} USD frente al costo de lista.`;
                            const badgeDesc = document.getElementById('almPromoBadgeDesc');
                            if (badgeDesc) badgeDesc.innerText = `-$${ahorro.toFixed(2)} USD`;
                        }
                    } else {
                        almPrecioPromoUsd.innerText = '-';
                        almPrecioPromoUsd.className = 'fs-4 fw-bold text-muted';
                        almCardPromoSub.innerText = 'Sin promoción vigente';
                        almCardPromoBox.className = 'p-2 border rounded text-center bg-light h-100 shadow-xs';
                        almPrecioUsd.style.textDecoration = 'none';
                        almPrecioUsd.className = 'fs-4 fw-bold text-dark';
                        if (almPromoAlert) almPromoAlert.style.display = 'none';
                    }

                    // Cargar almacenes
                    const tbody = document.getElementById('almTableBody');
                    tbody.innerHTML = '';

                    const stockData = data.stock;
                    let totalStock = 0;

                    if (stockData && Array.isArray(stockData.almacenes) && stockData.almacenes.length > 0) {
                        totalStock = stockData.stock_total || 0;

                        stockData.almacenes.forEach(alm => {
                            const cant = parseInt(alm.cantidad || 0);
                            const tr = document.createElement('tr');
                            if (cant > 0) {
                                tr.className = 'table-success bg-opacity-25';
                            }

                            tr.innerHTML = `
                                <td><span class="badge bg-light text-secondary border font-monospace">${alm.almacen_id || '-'}</span></td>
                                <td>
                                    <span class="fw-semibold text-dark">${alm.almacen_name || 'Almacén'}</span>
                                    ${alm.hay_mas ? '<span class="badge bg-info text-dark ms-1">Stock masivo</span>' : ''}
                                </td>
                                <td class="text-center">
                                    <span class="${cant > 0 ? 'warehouse-badge-positive' : 'warehouse-badge-zero'}">
                                        ${cant > 0 ? '<i class="bi bi-check-circle me-1"></i>' : ''} ${cant} uds.
                                    </span>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle fs-4 d-block mb-1"></i>
                                    No se encontraron almacenes con stock en Deltron para este producto.
                                </td>
                            </tr>
                        `;
                    }

                    document.getElementById('almTotalStock').innerText = totalStock;

                    // Si se guardó en la tabla Inventario_Proveedor, actualizar badge en la fila de la tabla
                    if (data.guardado_en_bd && btnTrigger) {
                        const trParent = btnTrigger.closest('tr');
                        if (trParent) {
                            const badgeStock = trParent.querySelector('.badge.bg-success, .badge.bg-secondary');
                            if (badgeStock) {
                                badgeStock.className = totalStock > 0 ? 'badge rounded-pill bg-success px-3 py-2 fs-6 shadow-xs' : 'badge rounded-pill bg-secondary px-3 py-2';
                                badgeStock.innerHTML = `<i class="bi bi-box-seam me-1"></i> ${totalStock}`;
                            }
                        }
                    }

                } else {
                    document.getElementById('almError').style.display = 'block';
                    document.getElementById('almErrorMsg').innerText = data.message || 'Error al obtener stock desde Deltron.';
                }
            })
            .catch(err => {
                document.getElementById('almLoading').style.display = 'none';
                document.getElementById('almError').style.display = 'block';
                document.getElementById('almErrorMsg').innerText = 'Error de conexión con el servidor o tiempo de espera agotado.';
            });
    }

    // ==============================================================
    // FUNCIÓN PARA CONSULTAR DETALLE DE PROMOCIÓN (API-0000025)
    // ==============================================================
    window.currentActivePromoId = null;

    function abrirModalPromocionDetalle(promoId, forceRefresh = false) {
        if (!promoId) return;
        window.currentActivePromoId = promoId;

        const modalEl = document.getElementById('modalDetallePromocion');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Reset UI
        document.getElementById('promoDetalleIdBadge').innerText = promoId;
        document.getElementById('promoDetalleTitle').innerText = 'Consultando información oficial de Deltron...';
        document.getElementById('promoLoading').style.display = 'block';
        document.getElementById('promoContent').style.display = 'none';
        document.getElementById('promoError').style.display = 'none';

        modal.show();

        const url = `/compras/deltron/promocion-detalle/${encodeURIComponent(promoId)}${forceRefresh ? '?force=1' : ''}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                document.getElementById('promoLoading').style.display = 'none';

                if (res.status === 'ok' && res.data) {
                    const data = res.data;
                    document.getElementById('promoContent').style.display = 'block';

                    // Título y descripción
                    document.getElementById('promoDetalleTitle').innerText = data.promocion_descripcion || 'Promoción Deltron';

                    // Precios
                    const precio = data.precio_promocion ? parseFloat(data.precio_promocion) : 0;
                    document.getElementById('promoDetallePrecioUsd').innerText = precio > 0 ? '$' + precio.toFixed(2) : '-';
                    document.getElementById('promoDetallePrecioIgv').innerText = precio > 0 ? '$' + (precio * 1.18).toFixed(2) : '-';

                    // Vigencia
                    document.getElementById('promoDetalleFechaFin').innerText = data.fecha_fin_formateada || data.fecha_fin || 'No especificada';
                    const vigenciaContainer = document.getElementById('promoDetalleVigenciaBadge');
                    if (data.ha_vencido) {
                        vigenciaContainer.innerHTML = '<span class="badge bg-danger rounded-pill px-2"><i class="bi bi-x-circle me-1"></i> Vencida</span>';
                    } else {
                        vigenciaContainer.innerHTML = '<span class="badge bg-success rounded-pill px-2"><i class="bi bi-check-circle me-1"></i> Vigente</span>';
                    }

                    // Total combos
                    const stockTotal = data.stock_total_promo ?? 0;
                    document.getElementById('promoDetalleStockTotal').innerText = stockTotal;

                    // Origen
                    const origenContainer = document.getElementById('promoDetalleOrigenBadge');
                    if (data.origen === 'base_de_datos') {
                        origenContainer.innerHTML = `
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">
                                <i class="bi bi-database-check me-1"></i> Base de Datos (0 llamadas API)
                            </span>
                        `;
                        document.getElementById('promoDetalleActualizadoAt').innerText = data.actualizado_at ? 'Guardado: ' + data.actualizado_at : 'Reutilizado de BD';
                    } else {
                        origenContainer.innerHTML = `
                            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1">
                                <i class="bi bi-cloud-check me-1"></i> Deltron API en Vivo
                            </span>
                        `;
                        document.getElementById('promoDetalleActualizadoAt').innerText = 'Actualizado y guardado en BD';
                    }

                    // Botón Cotizar Principal
                    const cotizarBtn = document.getElementById('promoCotizarPrincipalBtn');
                    const principalSku = (data.principal && data.principal.sku) ? data.principal.sku : (data.sku || '');
                    cotizarBtn.href = "{{ route('calculadora.cotizador') }}?search=" + encodeURIComponent(principalSku);

                    // Poblar Ítems del Combo
                    const itemsBody = document.getElementById('promoItemsTableBody');
                    itemsBody.innerHTML = '';
                    const items = data.items || [];
                    document.getElementById('promoItemsCountBadge').innerText = `${items.length} ${items.length === 1 ? 'producto' : 'productos'}`;

                    if (items.length > 0) {
                        items.forEach(it => {
                            const tipo = it.tipo_item_descripcion || it.tipo_item || 'PRODUCTO';
                            const sku = it.sku || '-';
                            const desc = it.sku_descripcion || it.descripcion || '-';
                            const cant = it.cantidad || 1;
                            const precioUnit = it.precio_unitario ? parseFloat(it.precio_unitario) : null;

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="ps-3">
                                    <span class="badge ${tipo === 'PRINCIPAL' ? 'bg-primary' : 'bg-warning text-dark'} font-monospace">
                                        ${tipo}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-secondary font-monospace">${sku}</span>
                                        ${sku !== '-' ? `<i class="bi bi-clipboard btn-copy-mini" title="Copiar SKU" onclick="copyToClipboard('${sku}', this)"></i>` : ''}
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">${desc}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-6">${cant}</span>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="fw-bold text-success">${precioUnit !== null ? '$' + precioUnit.toFixed(2) : '-'}</span>
                                </td>
                            `;
                            itemsBody.appendChild(tr);
                        });
                    } else {
                        itemsBody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle fs-4 d-block mb-1"></i>
                                    No se registraron ítems desglosados para este combo.
                                </td>
                            </tr>
                        `;
                    }

                    // Poblar Stock por Almacén
                    const stockBody = document.getElementById('promoStockTableBody');
                    stockBody.innerHTML = '';
                    const almacenes = data.stock_promocion || [];

                    if (almacenes.length > 0) {
                        almacenes.forEach(alm => {
                            const cantAlm = parseInt(alm.cantidad || 0);
                            const hayMas = alm.hay_mas ? true : false;
                            const tr = document.createElement('tr');
                            if (cantAlm > 0) {
                                tr.className = 'table-success bg-opacity-25';
                            }
                            tr.innerHTML = `
                                <td class="ps-3">
                                    <span class="badge bg-light text-secondary border font-monospace">${alm.almacen_id || '-'}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">${alm.almacen_nombre || 'Almacén'}</span>
                                    ${hayMas ? '<span class="badge bg-info text-dark ms-1">Stock masivo</span>' : ''}
                                </td>
                                <td class="text-center pe-3">
                                    <span class="${cantAlm > 0 ? 'warehouse-badge-positive' : 'warehouse-badge-zero'}">
                                        ${cantAlm > 0 ? '<i class="bi bi-check-circle me-1"></i>' : ''} ${cantAlm} combos
                                    </span>
                                </td>
                            `;
                            stockBody.appendChild(tr);
                        });
                    } else {
                        stockBody.innerHTML = `
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle fs-4 d-block mb-1"></i>
                                    No se reportó stock de combos en los almacenes Deltron.
                                </td>
                            </tr>
                        `;
                    }

                    // Actualizar en caliente la fila de la tabla de promociones en modal_ofertas
                    const mainRow = document.querySelector(`.promo-row[data-promo-id="${promoId}"]`);
                    if (mainRow) {
                        // Badge BD
                        const tdId = mainRow.querySelector('td:nth-child(1)');
                        if (tdId && !tdId.querySelector('.bi-check2')) {
                            const badgeBd = document.createElement('span');
                            badgeBd.className = 'badge bg-success bg-opacity-25 text-success rounded-pill ms-1';
                            badgeBd.title = 'Detalle sincronizado en base de datos';
                            badgeBd.innerHTML = '<i class="bi bi-check2"></i> BD';
                            tdId.appendChild(badgeBd);
                        }

                        // Badges de precio, fin y combos
                        const divBadges = mainRow.querySelector('td:nth-child(2) div');
                        if (divBadges) {
                            divBadges.innerHTML = `
                                ${precio > 0 ? `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-tag-fill me-1"></i>Precio Promo: $${precio.toFixed(2)}</span>` : ''}
                                ${data.fecha_fin ? `<span class="badge bg-light text-secondary border"><i class="bi bi-calendar-event me-1"></i>Fin: ${data.fecha_fin.split(' ')[0]}</span>` : ''}
                                <span class="badge ${stockTotal > 0 ? 'bg-primary' : 'bg-secondary'} bg-opacity-10 ${stockTotal > 0 ? 'text-primary' : 'text-muted'} border border-primary border-opacity-25"><i class="bi bi-box-seam me-1"></i>${stockTotal} combos disponibles</span>
                            `;
                        }
                    }

                } else {
                    document.getElementById('promoError').style.display = 'block';
                    document.getElementById('promoErrorMsg').innerText = res.message || 'Error al obtener detalle de la promoción.';
                }
            })
            .catch(err => {
                document.getElementById('promoLoading').style.display = 'none';
                document.getElementById('promoError').style.display = 'block';
                document.getElementById('promoErrorMsg').innerText = 'Error de conexión con el servidor o tiempo de espera agotado.';
            });
    }

    // ==============================================================
    // FUNCIONES PARA FICHA TÉCNICA POR SKU DELTRON (API-0000027)
    // ==============================================================
    window.currentActiveFichaSku = null;
    window.currentActiveFichaPn = null;

    function abrirModalConsultaSkuPuntual() {
        const modalEl = document.getElementById('modalFichaSku');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        document.getElementById('fichaLoading').style.display = 'none';
        document.getElementById('fichaContent').style.display = 'none';
        document.getElementById('fichaError').style.display = 'none';

        const inp = document.getElementById('fichaSkuInput');
        if (inp) {
            inp.value = '';
            setTimeout(() => inp.focus(), 400);
        }

        modal.show();
    }

    function consultarFichaDesdeInput() {
        const inp = document.getElementById('fichaSkuInput');
        const val = inp ? inp.value.trim() : '';
        if (!val) return;
        abrirModalFichaSku(val, '', false);
    }

    function abrirModalFichaSku(sku, pn, forceRefresh = false, btnTrigger = null) {
        if (!sku && !pn) return;

        window.currentActiveFichaSku = sku || pn;
        window.currentActiveFichaPn = pn || '';

        const modalEl = document.getElementById('modalFichaSku');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Reset UI
        const inp = document.getElementById('fichaSkuInput');
        if (inp) inp.value = sku || pn;

        document.getElementById('fichaLoading').style.display = 'block';
        document.getElementById('fichaContent').style.display = 'none';
        document.getElementById('fichaError').style.display = 'none';

        const btnForce = document.getElementById('btnForceFichaRefresh');
        if (btnForce) {
            btnForce.disabled = forceRefresh;
            btnForce.innerHTML = forceRefresh 
                ? '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Consultando Deltron API...'
                : '<i class="bi bi-arrow-clockwise me-1"></i> Actualizar desde Deltron API';
        }

        // Seleccionar por defecto la pestaña 1 (Especificaciones)
        const firstTab = document.getElementById('tab-specs-btn');
        if (firstTab && typeof bootstrap !== 'undefined') {
            const tabInstance = bootstrap.Tab.getOrCreateInstance(firstTab);
            tabInstance.show();
        }

        modal.show();

        const queryParam = sku || pn;
        const url = `/compras/deltron/ficha-sku/${encodeURIComponent(queryParam)}?part_number=${encodeURIComponent(pn || '')}${forceRefresh ? '&force=1' : ''}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                document.getElementById('fichaLoading').style.display = 'none';
                if (btnForce) {
                    btnForce.disabled = false;
                    btnForce.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Actualizar desde Deltron API';
                }

                if (res.status === 'ok' && res.data) {
                    const data = res.data;
                    document.getElementById('fichaContent').style.display = 'block';

                    // 1. Identificación
                    document.getElementById('fichaProdName').innerText = data.name || 'Producto Deltron';
                    document.getElementById('fichaProdSku').innerText = data.sku || '-';
                    document.getElementById('fichaProdPn').innerText = data.part_number || '-';

                    const miniBox = document.getElementById('fichaMiniCodeBox');
                    if (data.mini_code) {
                        miniBox.style.display = 'inline';
                        document.getElementById('fichaProdMiniCode').innerText = data.mini_code;
                    } else {
                        miniBox.style.display = 'none';
                    }

                    const brandBox = document.getElementById('fichaBrandBox');
                    if (data.brand_name) {
                        brandBox.style.display = 'inline';
                        document.getElementById('fichaProdBrand').innerText = data.brand_name;
                    } else {
                        brandBox.style.display = 'none';
                    }

                    const catBox = document.getElementById('fichaCategoryBox');
                    if (data.category_name) {
                        catBox.style.display = 'inline';
                        document.getElementById('fichaProdCategory').innerText = data.category_name + (data.subcategory_name ? ' / ' + data.subcategory_name : '');
                    } else {
                        catBox.style.display = 'none';
                    }

                    const warBox = document.getElementById('fichaWarrantyBox');
                    if (data.warranty) {
                        warBox.style.display = 'inline';
                        document.getElementById('fichaProdWarranty').innerText = data.warranty;
                    } else {
                        warBox.style.display = 'none';
                    }

                    // Estado Vinculación BD
                    const bdStatusBox = document.getElementById('fichaBdStatusBox');
                    if (data.vinculado_bd) {
                        bdStatusBox.innerHTML = `
                            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                                <i class="bi bi-database-check me-1"></i> Sincronizado en BD
                            </span>
                        `;
                    } else {
                        bdStatusBox.innerHTML = `
                            <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill">
                                <i class="bi bi-info-circle me-1"></i> No registrado en BD Unike
                            </span>
                        `;
                    }

                    // Origen y Fecha
                    const origenBox = document.getElementById('fichaOrigenBadge');
                    if (data.origen === 'base_de_datos') {
                        origenBox.innerHTML = `
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">
                                <i class="bi bi-database-check me-1"></i> Base de Datos (0 llamadas API)
                            </span>
                        `;
                    } else {
                        origenBox.innerHTML = `
                            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1">
                                <i class="bi bi-cloud-check me-1"></i> Deltron API en Vivo
                            </span>
                        `;
                    }
                    document.getElementById('fichaTimestamp').innerHTML = `<i class="bi bi-clock me-1"></i> ${data.actualizado_at || 'Hoy'}`;

                    // 2. Precios
                    const precio = data.price ? parseFloat(data.price) : 0;
                    const precioPromo = data.price_promo ? parseFloat(data.price_promo) : null;
                    const tienePromo = (precioPromo !== null && precioPromo < precio);

                    const elUsd = document.getElementById('fichaPrecioUsd');
                    const elIgv = document.getElementById('fichaPrecioIgv');
                    const elPromoUsd = document.getElementById('fichaPrecioPromoUsd');
                    const elPromoSub = document.getElementById('fichaCardPromoSub');
                    const cardPromoBox = document.getElementById('fichaCardPromoBox');

                    elUsd.innerText = precio > 0 ? '$' + precio.toFixed(2) : '-';
                    elIgv.innerText = precio > 0 ? '$' + (precio * 1.18).toFixed(2) : '-';

                    if (tienePromo) {
                        const ahorro = precio - precioPromo;
                        elPromoUsd.innerText = '$' + precioPromo.toFixed(2);
                        elPromoUsd.className = 'fs-4 fw-bold text-success';
                        elPromoSub.innerHTML = `+ IGV: <span class="fw-semibold text-dark">$${(precioPromo * 1.18).toFixed(2)}</span> <span class="badge bg-danger rounded-pill ms-1">Ahorro $${ahorro.toFixed(2)}</span>`;
                        cardPromoBox.className = 'p-3 border border-success rounded-3 bg-success bg-opacity-10 shadow-xs text-center h-100';
                        elUsd.style.textDecoration = 'line-through';
                        elUsd.className = 'fs-4 fw-bold text-muted';
                    } else {
                        elPromoUsd.innerText = '-';
                        elPromoUsd.className = 'fs-4 fw-bold text-muted';
                        elPromoSub.innerText = 'Sin promoción vigente';
                        cardPromoBox.className = 'p-3 border rounded-3 bg-white shadow-xs text-center h-100';
                        elUsd.style.textDecoration = 'none';
                        elUsd.className = 'fs-4 fw-bold text-dark';
                    }

                    // 3. Stock y Tránsito
                    const stockTotal = data.stock_total || 0;
                    document.getElementById('fichaStockTotal').innerText = stockTotal;

                    const transTotal = data.transito_total || 0;
                    document.getElementById('fichaTransitoTotal').innerText = transTotal;
                    document.getElementById('fichaTransitoSub').innerText = transTotal > 0 ? `${transTotal} unidades en despacho/mar` : 'Sin pedidos pendientes';

                    // 4. Pestaña Especificaciones
                    const descContainer = document.getElementById('fichaDescContainer');
                    const descText = data.descripcion_larga || data.descripcion_corta || '';
                    if (descText) {
                        descContainer.style.display = 'block';
                        document.getElementById('fichaDescText').innerText = descText;
                    } else {
                        descContainer.style.display = 'none';
                    }

                    const specsEl = document.getElementById('fichaSpecsHtml');
                    if (data.specs_html && data.specs_html.trim() !== '') {
                        specsEl.innerHTML = data.specs_html;
                    } else {
                        specsEl.innerHTML = `
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-card-text fs-3 d-block mb-2 opacity-50"></i>
                                Sin tabla de especificaciones técnicas detalladas proporcionada por Deltron.
                            </div>
                        `;
                    }

                    // 5. Pestaña Galería de Imágenes
                    const imgGrid = document.getElementById('fichaImagesGrid');
                    imgGrid.innerHTML = '';
                    const imagesList = [];

                    if (data.images && data.images.listado) {
                        imagesList.push({ url: data.images.listado, title: 'Imagen Principal de Catálogo' });
                    }
                    if (data.images && Array.isArray(data.images.carrusel)) {
                        data.images.carrusel.forEach((u, i) => {
                            if (u && u !== data.images.listado) {
                                imagesList.push({ url: u, title: `Vista Detallada ${i + 1}` });
                            }
                        });
                    }

                    document.getElementById('fichaImagesCountBadge').innerText = imagesList.length;

                    if (imagesList.length > 0) {
                        imagesList.forEach(im => {
                            const col = document.createElement('div');
                            col.className = 'col-6 col-md-4 col-lg-3';
                            col.innerHTML = `
                                <div class="deltron-img-card text-center p-2 shadow-xs">
                                    <a href="${im.url}" target="_blank" title="Abrir imagen en tamaño completo">
                                        <img src="${im.url}" alt="${data.name}" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_18a7d3b5b5c%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3Avar(--bs-font-sans-serif)%2C%20sans-serif%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_18a7d3b5b5c%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.5%22%20y%3D%22104.5%22%3ENo%20Img%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E'">
                                    </a>
                                    <small class="text-muted d-block text-truncate mt-1">${im.title}</small>
                                </div>
                            `;
                            imgGrid.appendChild(col);
                        });
                    } else {
                        imgGrid.innerHTML = `
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="bi bi-image fs-1 d-block mb-2 opacity-25"></i>
                                No hay imágenes disponibles para este producto en Deltron.
                            </div>
                        `;
                    }

                    // 6. Pestaña Stock por Almacén
                    const whTable = document.getElementById('fichaWarehousesTableBody');
                    whTable.innerHTML = '';
                    const almacenes = data.almacenes || [];

                    if (almacenes.length > 0) {
                        almacenes.forEach(alm => {
                            const cant = parseInt(alm.cantidad || 0);
                            const tr = document.createElement('tr');
                            if (cant > 0) {
                                tr.className = 'table-success bg-opacity-25';
                            }
                            tr.innerHTML = `
                                <td class="ps-3"><span class="badge bg-light text-secondary border font-monospace">${alm.almacen_id || '-'}</span></td>
                                <td>
                                    <span class="fw-semibold text-dark">${alm.almacen_name || 'Almacén'}</span>
                                    ${alm.hay_mas ? '<span class="badge bg-info text-dark ms-1">Stock masivo</span>' : ''}
                                </td>
                                <td class="text-center pe-3">
                                    <span class="${cant > 0 ? 'warehouse-badge-positive' : 'warehouse-badge-zero'}">
                                        ${cant > 0 ? '<i class="bi bi-check-circle me-1"></i>' : ''} ${cant} uds.
                                    </span>
                                </td>
                            `;
                            whTable.appendChild(tr);
                        });
                    } else {
                        whTable.innerHTML = `
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle fs-4 d-block mb-1"></i>
                                    No se reportó stock en ningún almacén de Deltron para este SKU.
                                </td>
                            </tr>
                        `;
                    }

                    // 7. Pestaña Tránsito y Promos
                    const transTable = document.getElementById('fichaTransitoTableBody');
                    transTable.innerHTML = '';
                    const transItems = data.transito_items || [];

                    if (transItems.length > 0) {
                        transItems.forEach(trItem => {
                            const cantTr = parseInt(trItem.cantidad || trItem.cant || 0);
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="ps-3"><span class="badge bg-info-subtle text-info border font-monospace">${trItem.tipo_transporte || 'MARITIMO'}</span></td>
                                <td><i class="bi bi-calendar-event me-1 text-muted"></i> ${trItem.fecha || trItem.fecha_llegada || 'Por confirmar'}</td>
                                <td><span class="text-secondary small font-monospace">${trItem.pedido || trItem.guia || '-'}</span></td>
                                <td class="text-center pe-3"><span class="fw-bold text-info">${cantTr} uds.</span></td>
                            `;
                            transTable.appendChild(tr);
                        });
                    } else {
                        transTable.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">
                                    No hay mercadería en tránsito reportada para este producto.
                                </td>
                            </tr>
                        `;
                    }

                    const promosTable = document.getElementById('fichaPromosTableBody');
                    promosTable.innerHTML = '';
                    const promos = data.promociones || [];

                    if (promos.length > 0) {
                        promos.forEach(p => {
                            const pId = p.promocion_id || '-';
                            const pDesc = p.promocion_descripcion || 'Promoción Deltron';
                            const pPrice = p.precio ? parseFloat(p.precio) : null;
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="ps-3"><span class="badge bg-warning text-dark font-monospace fw-bold">${pId}</span></td>
                                <td><span class="fw-semibold text-dark">${pDesc}</span></td>
                                <td class="text-end pe-3">
                                    <span class="fw-bold text-success">${pPrice ? '$' + pPrice.toFixed(2) : '-'}</span>
                                </td>
                            `;
                            promosTable.appendChild(tr);
                        });
                    } else {
                        promosTable.innerHTML = `
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted">
                                    No hay promociones activas para este SKU en este momento.
                                </td>
                            </tr>
                        `;
                    }

                    // 8. Botón Cotizar
                    const cotizarBtn = document.getElementById('fichaCotizarBtn');
                    const cotizarQuery = data.part_number && data.part_number !== '-' ? data.part_number : data.sku;
                    cotizarBtn.href = "{{ route('calculadora.cotizador') }}?search=" + encodeURIComponent(cotizarQuery);

                    // 9. Si el producto se actualizó en la BD y el trigger provino de una fila de la tabla
                    if (btnTrigger && data.vinculado_bd) {
                        const trParent = btnTrigger.closest('tr');
                        if (trParent) {
                            const badgeStock = trParent.querySelector('.badge.bg-success, .badge.bg-secondary');
                            if (badgeStock) {
                                badgeStock.className = stockTotal > 0 ? 'badge rounded-pill bg-success px-3 py-2 fs-6 shadow-xs' : 'badge rounded-pill bg-secondary px-3 py-2';
                                badgeStock.innerHTML = `<i class="bi bi-box-seam me-1"></i> ${stockTotal}`;
                            }
                        }
                    }

                } else {
                    document.getElementById('fichaError').style.display = 'block';
                    document.getElementById('fichaErrorMsg').innerText = res.message || 'No se pudo obtener la ficha del producto.';
                }
            })
            .catch(err => {
                document.getElementById('fichaLoading').style.display = 'none';
                if (btnForce) {
                    btnForce.disabled = false;
                    btnForce.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Actualizar desde Deltron API';
                }
                document.getElementById('fichaError').style.display = 'block';
                document.getElementById('fichaErrorMsg').innerText = 'Error de conexión con el servidor o tiempo de espera agotado.';
            });
    }

    // Confirmación y animación para sincronización masiva oficial de Deltron
    function confirmarSyncDeltron(e, tipo, formElem) {
        if (typeof Swal !== 'undefined') {
            e.preventDefault();
            const esCatalogo = tipo === 'catalogo';
            const titulo = esCatalogo ? '¿Descargar Catálogo Completo de Deltron?' : '¿Sincronizar Inicio de Día?';
            const desc = esCatalogo 
                ? 'Esta acción descargará todo el catálogo de Deltron (25,000+ ítems), mapeará los SKUs oficiales, Mini Códigos y stock de tus productos. Puede tardar entre 20 y 40 segundos.'
                : 'Esta acción actualizará el stock, precios vigentes y promociones del corte de hoy. Tarda unos 5 a 10 segundos.';

            Swal.fire({
                title: titulo,
                text: desc,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, sincronizar ahora',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sincronizando con Deltron B2B...',
                        html: '<div class="text-muted small mt-2">Conectando con la API oficial y procesando productos en la base de datos.<br>Por favor no cierres ni recargues la página.</div>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                            formElem.submit();
                        }
                    });
                }
            });
        } else {
            if (tipo === 'catalogo') {
                const sp = document.getElementById('catSpinner');
                if (sp) sp.style.display = 'inline-block';
            } else {
                const sp = document.getElementById('syncSpinner');
                if (sp) sp.style.display = 'inline-block';
                const btn = document.getElementById('btnSyncMain');
                if (btn) btn.disabled = true;
            }
        }
    }
</script>
