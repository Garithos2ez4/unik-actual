document.addEventListener('DOMContentLoaded', function() {
    // Estado de la cotización
    let modoCombo = false;
    let comboItems = [];
    let itemIdCounter = 1;
    let currentIndividualProduct = null;

    // Elementos DOM
    const searchInput = document.getElementById('search-producto');
    const searchResults = document.getElementById('search-results');
    const btnClearSearch = document.getElementById('btn-clear-search');
    const tableBody = document.getElementById('combo-body');
    const emptyRow = document.getElementById('empty-combo-row');
    const btnClearCombo = document.getElementById('btn-clear-combo');
    const totalesBox = document.getElementById('totales-box');
    
    // Totales DOM
    const spanTotalUsd = document.getElementById('total-usd');
    const spanTotalSoles = document.getElementById('total-soles');
    const spanTotalFlete = document.getElementById('total-flete');
    const spanTotalGanancia = document.getElementById('total-ganancia');
    const spanTotalBaseSinComis = document.getElementById('total-base-sin-comision');
    
    // Simulador DOM
    const simuladorEmpty = document.getElementById('simulador-empty');
    const simuladorPlataformas = document.getElementById('simulador-plataformas');

    // Elementos DOM Modo
    const switchModoCombo = document.getElementById('switch-modo-combo');
    const panelIndividual = document.getElementById('modo-individual');
    const panelCombo = document.getElementById('modo-combo');

    // DOM Individual
    const indivNombreProducto = document.getElementById('indiv-nombre-producto');
    const indivDetallesProducto = document.getElementById('indiv-detalles-producto');
    const indivMoneda = document.getElementById('indiv-moneda');
    const indivCosto = document.getElementById('indiv-costo');
    const indivFlete = document.getElementById('indiv-flete');
    const indivGanancia = document.getElementById('indiv-ganancia');
    const indivTotalSoles = document.getElementById('indiv-total-soles');
    const indivTotalBase = document.getElementById('indiv-total-base');
    const inputsIndiv = document.querySelectorAll('.input-indiv');
    const inputTasaCambio = document.getElementById('input-tasa-cambio');
    const btnRemoverProdIndiv = document.getElementById('btn-remover-prod-indiv');

    // Inicializar la vista será llamado al final del script

    // ---------------------------------------------------------
    // CAMBIO DE MODO
    // ---------------------------------------------------------
    switchModoCombo.addEventListener('change', function() {
        modoCombo = this.checked;
        if (modoCombo) {
            panelIndividual.classList.add('d-none');
            panelCombo.classList.remove('d-none');
            renderCombo();
        } else {
            panelCombo.classList.add('d-none');
            panelIndividual.classList.remove('d-none');
            window.recalcularCotizacion();
        }
    });

    // Eventos Individuales
    inputsIndiv.forEach(input => {
        input.addEventListener('input', () => window.recalcularCotizacion());
        input.addEventListener('focus', function() { this.select(); });
    });
    
    let prevMonedaIndiv = indivMoneda ? indivMoneda.value : 'USD';
    if (indivMoneda) {
        indivMoneda.addEventListener('change', function() {
            const val = parseFloat(indivCosto.value) || 0;
            const newMoneda = this.value;
            if (val > 0 && window.APP_DATA.tasaCambio > 0) {
                if (prevMonedaIndiv === 'USD' && newMoneda === 'PEN') {
                    indivCosto.value = (val * window.APP_DATA.tasaCambio).toFixed(2);
                } else if (prevMonedaIndiv === 'PEN' && newMoneda === 'USD') {
                    indivCosto.value = (val / window.APP_DATA.tasaCambio).toFixed(2);
                }
            }
            prevMonedaIndiv = newMoneda;
            window.recalcularCotizacion();
        });
    }

    // Eventos TC
    if (inputTasaCambio) {
        inputTasaCambio.addEventListener('input', function() {
            let val = parseFloat(this.value);
            if (!isNaN(val) && val > 0) {
                window.APP_DATA.tasaCambio = val;
                window.recalcularCotizacion();
            }
        });
    }

    // Remover selección individual
    if (btnRemoverProdIndiv) {
        btnRemoverProdIndiv.addEventListener('click', function() {
            currentIndividualProduct = null;
            indivNombreProducto.textContent = 'Cotización Manual';
            indivDetallesProducto.textContent = 'Ingresa el costo manualmente o busca un producto arriba.';
            this.classList.add('d-none');
            window.recalcularCotizacion();
        });
    }

    // ---------------------------------------------------------
    // BÚSQUEDA DE PRODUCTOS (AUTOCOMPLETE)
    // ---------------------------------------------------------
    let searchTimeout = null;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        // Indicador de carga
        searchResults.innerHTML = '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Buscando...</div>';
        searchResults.style.display = 'block';
        
        searchTimeout = setTimeout(() => {
            fetch(`/egresos/search-producto-ajax?query=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    renderSearchResults(data);
                })
                .catch(error => {
                    console.error('Error buscando productos:', error);
                    searchResults.innerHTML = '<div class="p-3 text-center text-danger">Error al buscar. Intente de nuevo.</div>';
                });
        }, 500);
    });

    btnClearSearch.addEventListener('click', function() {
        searchInput.value = '';
        searchResults.style.display = 'none';
        searchInput.focus();
    });

    // Cerrar autocomplete al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    function renderSearchResults(data) {
        if (!data || data.length === 0) {
            searchResults.innerHTML = '<div class="p-3 text-center text-muted">No se encontraron productos.</div>';
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        data.forEach(prod => {
            // El backend devuelve costoComprobante (de DetalleComprobante) y monedaComprobante
            const moneda = prod.monedaComprobante || 'USD';
            const costo = parseFloat(prod.costoComprobante !== undefined ? prod.costoComprobante : (prod.precioDolar || 0));
            const marca = prod.nombreMarca ? prod.nombreMarca : '';
            const imgUrl = prod.imagenProducto1 ? `/storage/${prod.imagenProducto1}` : '/images/no-image.png';
            
            // Fallback para evitar errores si no hay imagenProducto1
            const imgSrc = prod.imagenProducto1 ? imgUrl : 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_18a7d3b5b5c%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3Avar(--bs-font-sans-serif)%2C%20sans-serif%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_18a7d3b5b5c%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.5%22%20y%3D%22104.5%22%3ENo%20Img%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';

            // Escapar strings para JSON y asegurarnos de escapar comillas simples para el atributo onclick
            const prodDataStr = JSON.stringify({
                id: prod.idProducto,
                nombre: prod.nombreProducto,
                modelo: prod.modelo,
                marca: marca,
                costo: costo,
                moneda: moneda,
                comprobante: prod.numeroComprobante || null,
                idGrupo: prod.idGrupo || null,
                idCategoria: prod.idCategoria || null
            });
            // encodeURIComponent no escapa comillas simples, lo hacemos manualmente
            const prodData = encodeURIComponent(prodDataStr).replace(/'/g, "%27");

            const badgeCosto = moneda === 'PEN' 
                ? `S/ ${costo.toFixed(2)}` 
                : `$ ${costo.toFixed(2)} (S/ ${(costo * window.APP_DATA.tasaCambio).toFixed(2)})`;
            const infoComp = prod.numeroComprobante ? `<small class="text-secondary ms-1">(${prod.numeroComprobante})</small>` : '';

            html += `
                <a href="javascript:void(0)" class="list-group-item list-group-item-action product-result-item" onclick="agregarAlCombo('${prodData}')">
                    <div class="d-flex align-items-center">
                        <img src="${imgSrc}" style="width: 40px; height: 40px; object-fit: contain;" class="me-3 border rounded" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_18a7d3b5b5c%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3Avar(--bs-font-sans-serif)%2C%20sans-serif%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_18a7d3b5b5c%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.5%22%20y%3D%22104.5%22%3ENo%20Img%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E'">
                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="mb-0 text-truncate">${prod.nombreProducto}</h6>
                            <small class="text-muted">${marca} | Mod: ${prod.modelo}</small>
                            ${infoComp}
                        </div>
                        <div class="ms-2 text-end" style="min-width: 110px;">
                            <span class="badge bg-success">${badgeCosto}</span>
                        </div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
        searchResults.innerHTML = html;
    }

    // ---------------------------------------------------------
    // GESTIÓN DEL COMBO
    // ---------------------------------------------------------

    window.agregarAlCombo = function(prodDataEncoded) {
        const prod = JSON.parse(decodeURIComponent(prodDataEncoded));
        
        // Limpiar búsqueda
        searchInput.value = '';
        searchResults.style.display = 'none';

        if (modoCombo) {
            // Añadir ítem a la lista combo
            // En combo se guarda costoUsd (sin IGV) para que (costoUsd * igvFactor * tasaCambio) dé el subtotal en soles
            const costoUsd = prod.moneda === 'PEN' 
                ? (window.APP_DATA.tasaCambio > 0 ? (prod.costo / window.APP_DATA.igvFactor) / window.APP_DATA.tasaCambio : prod.costo)
                : (prod.costo / window.APP_DATA.igvFactor);

            comboItems.push({
                uid: itemIdCounter++,
                idProducto: prod.id,
                nombre: prod.nombre,
                modelo: prod.modelo,
                marca: prod.marca,
                cantidad: 1,
                costoUsd: parseFloat(costoUsd.toFixed(2)),
                fleteSoles: 0,
                gananciaSoles: 0,
                idGrupo: prod.idGrupo || null,
                idCategoria: prod.idCategoria || null
            });
            renderCombo();
        } else {
            // Establecer en individual
            currentIndividualProduct = prod;
            indivNombreProducto.textContent = prod.nombre;
            const extraComp = prod.comprobante ? ` | Comp: ${prod.comprobante}` : '';
            indivDetallesProducto.textContent = `${prod.marca} | Mod: ${prod.modelo}${extraComp}`;
            if (btnRemoverProdIndiv) btnRemoverProdIndiv.classList.remove('d-none');
            
            indivMoneda.value = prod.moneda || 'USD';
            prevMonedaIndiv = indivMoneda.value;
            indivCosto.value = parseFloat(prod.costo).toFixed(2);
            indivFlete.value = '0.00';
            indivGanancia.value = '0.00';
            window.recalcularCotizacion();
        }
    };

    function renderCombo() {
        if (comboItems.length === 0) {
            emptyRow.style.display = 'table-row';
            btnClearCombo.classList.add('d-none');
            totalesBox.classList.add('d-none');
            simuladorEmpty.classList.remove('d-none');
            simuladorPlataformas.classList.add('d-none');
            tableBody.innerHTML = '';
            tableBody.appendChild(emptyRow);
            return;
        }

        emptyRow.style.display = 'none';
        btnClearCombo.classList.remove('d-none');
        totalesBox.classList.remove('d-none');
        simuladorEmpty.classList.add('d-none');
        simuladorPlataformas.classList.remove('d-none');
        
        let html = '';
        comboItems.forEach((item, index) => {
            // Calcular costo en soles ( USD * IGV * TC ) * cantidad
            const costoSoles = (item.costoUsd * window.APP_DATA.igvFactor * window.APP_DATA.tasaCambio) * item.cantidad;
            
            html += `
                <tr>
                    <td class="text-center align-middle">
                        <i class="bi bi-x-circle remove-row fs-5" onclick="eliminarDelCombo(${item.uid})" title="Eliminar"></i>
                    </td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">${item.nombre}</div>
                        <small class="text-muted">${item.marca} | ${item.modelo}</small>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-center input-update" data-uid="${item.uid}" data-field="cantidad" value="${item.cantidad}" min="1">
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control input-update" data-uid="${item.uid}" data-field="costoUsd" value="${item.costoUsd}" step="0.01" min="0">
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">S/</span>
                            <input type="number" class="form-control input-update text-secondary" data-uid="${item.uid}" data-field="fleteSoles" value="${item.fleteSoles}" step="1" min="0">
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">S/</span>
                            <input type="number" class="form-control input-update text-success" data-uid="${item.uid}" data-field="gananciaSoles" value="${item.gananciaSoles}" step="1" min="0">
                        </div>
                    </td>
                    <td class="text-end fw-bold align-middle bg-light">
                        S/ ${costoSoles.toFixed(2)}
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;

        // Añadir event listeners a los nuevos inputs
        document.querySelectorAll('.input-update').forEach(input => {
            input.addEventListener('change', handleInputChange);
            input.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    handleInputChange.call(this, e);
                    this.blur();
                }
            });
            input.addEventListener('focus', function() {
                this.select();
            });
        });

        recalcularCotizacion();
    }

    function handleInputChange(e) {
        const uid = parseInt(this.dataset.uid);
        const field = this.dataset.field;
        let val = parseFloat(this.value);

        if (isNaN(val) || val < 0) val = 0;
        if (field === 'cantidad' && val < 1) val = 1;

        // Encontrar el item y actualizarlo
        const item = comboItems.find(i => i.uid === uid);
        if (item) {
            item[field] = val;
            
            // Volver a renderizar para actualizar el subtotal en la fila
            // Guardamos el foco para no perderlo si el usuario usó las flechas (opcional)
            renderCombo();
        }
    }

    window.eliminarDelCombo = function(uid) {
        comboItems = comboItems.filter(item => item.uid !== uid);
        renderCombo();
    };

    btnClearCombo.addEventListener('click', function() {
        Swal.fire({
            title: '¿Limpiar cotización?',
            text: "Se eliminarán todos los productos del combo.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                comboItems = [];
                renderCombo();
            }
        });
    });

    // ---------------------------------------------------------
    // CÁLCULOS PRINCIPALES
    // ---------------------------------------------------------

    window.recalcularCotizacion = function() {
        let totalBaseSinComision = 0;

        if (modoCombo) {
            if (comboItems.length === 0) {
                simuladorEmpty.classList.remove('d-none');
                simuladorPlataformas.classList.add('d-none');
                return;
            }
            
            let sumaUsdSinIgv = 0;
            let sumaSolesConIgv = 0;
            let sumaFlete = 0;
            let sumaGanancia = 0;

            comboItems.forEach(item => {
                const usdSubtotal = item.costoUsd * item.cantidad;
                const solesConIgvSubtotal = usdSubtotal * window.APP_DATA.igvFactor * window.APP_DATA.tasaCambio;
                
                sumaUsdSinIgv += usdSubtotal;
                sumaSolesConIgv += solesConIgvSubtotal;
                sumaFlete += parseFloat(item.fleteSoles) * item.cantidad; // Asumimos flete por unidad
                sumaGanancia += parseFloat(item.gananciaSoles) * item.cantidad; // Asumimos ganancia por unidad
            });

            // Actualizar UI Totales
            spanTotalUsd.textContent = sumaUsdSinIgv.toFixed(2);
            spanTotalSoles.textContent = sumaSolesConIgv.toFixed(2);
            spanTotalFlete.textContent = sumaFlete.toFixed(2);
            spanTotalGanancia.textContent = sumaGanancia.toFixed(2);
            
            // Total Base Sin Comisión = Costo S/ + Flete + Ganancia
            totalBaseSinComision = sumaSolesConIgv + sumaFlete + sumaGanancia;
            spanTotalBaseSinComis.textContent = `S/ ${totalBaseSinComision.toFixed(2)}`;
            
        } else {
            // MODO INDIVIDUAL
            const costo = parseFloat(indivCosto.value) || 0;
            const flete = parseFloat(indivFlete.value) || 0;
            const ganancia = parseFloat(indivGanancia.value) || 0;
            const esUsd = indivMoneda.value === 'USD';
            
            // Costo ya incluye IGV, solo convertimos a soles si es USD
            let costoSolesConIgv = 0;
            if (esUsd) {
                costoSolesConIgv = costo * window.APP_DATA.tasaCambio;
            } else {
                costoSolesConIgv = costo;
            }
            
            indivTotalSoles.textContent = costoSolesConIgv.toFixed(2);
            totalBaseSinComision = costoSolesConIgv + flete + ganancia;
            indivTotalBase.textContent = `S/ ${totalBaseSinComision.toFixed(2)}`;
        }

        if (totalBaseSinComision > 0) {
            simuladorEmpty.classList.add('d-none');
            simuladorPlataformas.classList.remove('d-none');
            actualizarSimulacionPlataformas(totalBaseSinComision);
        } else {
            simuladorEmpty.classList.remove('d-none');
            simuladorPlataformas.classList.add('d-none');
        }
    };

    // ---------------------------------------------------------
    // SIMULACIÓN PLATAFORMAS
    // ---------------------------------------------------------

    function renderPlataformasTemplate() {
        // En base a los data-attributes inyectados con Blade, construimos la UI de comisiones
        const plataformas = document.querySelectorAll('.plataforma-data');
        
        plataformas.forEach(plataforma => {
            const pid = plataforma.dataset.pid;
            const container = document.getElementById(`container-plataforma-${pid}`);
            if (!container) return;
            
            const comisionesElements = plataforma.querySelectorAll('.comision-data');
            let html = '';
            
            if (comisionesElements.length === 0) {
                html = '<div class="p-3 text-center text-muted">No hay comisiones configuradas para esta plataforma.</div>';
            } else {
                html += `
                <div class="list-group-item bg-light border-bottom-0 pb-1 pt-2">
                    <div class="row text-secondary small fw-bold">
                        <div class="col-4">Comisión / Flete</div>
                        <div class="col-3 text-center" title="Total Sin Facturar">T.S.Fact</div>
                        <div class="col-2 text-center" title="Total Facturado">T.Fact</div>
                        <div class="col-3 text-end text-primary">Promedio</div>
                    </div>
                </div>
                `;
                
                comisionesElements.forEach((com, idx) => {
                    const pct = parseFloat(com.dataset.comision) || 0;
                    const flete = parseFloat(com.dataset.flete) || 0;
                    const nombre = com.dataset.nombre || '';
                    const tipo = com.dataset.tipo || 'DEFAULT';
                    const valor = com.dataset.valor || '';
                    const prioridad = parseInt(com.dataset.prioridad) || 0;
                    const id = com.dataset.id || idx;
                    
                    html += `
                    <div class="list-group-item py-2 hover-sistema-uno simulacion-row" 
                         id="sim-row-${pid}-${id}"
                         data-pid="${pid}" 
                         data-pct="${pct}" 
                         data-flete="${flete}"
                         data-tipo="${tipo}"
                         data-valor="${valor}"
                         data-prioridad="${prioridad}"
                         data-nombre="${nombre}">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="fw-bold me-1">${pct}%</span>
                                    ${flete > 0 ? `<span class="badge bg-light text-secondary border small fw-normal">+S/ ${flete.toFixed(2)}</span>` : ''}
                                </div>
                                ${nombre ? `<div class="text-muted text-truncate" style="font-size: 0.73rem; max-width: 140px;" title="${nombre}">${nombre}</div>` : ''}
                                <span class="badge bg-success d-none badge-aplicable mt-1" style="font-size: 0.65rem;"><i class="bi bi-check-circle-fill"></i> Sugerido</span>
                            </div>
                            <div class="col-3 text-center">
                                <small class="text-secondary">S/ <span class="val-tsfact">0.00</span></small>
                            </div>
                            <div class="col-2 text-center">
                                <small class="text-secondary">S/ <span class="val-tfact">0.00</span></small>
                            </div>
                            <div class="col-3 text-end fw-bold text-dark">
                                S/ <span class="val-promedio">0.00</span>
                            </div>
                        </div>
                    </div>
                    `;
                });
            }
            
            container.innerHTML = html;
        });
    }

    function getMatchingRule(filasPlataforma, activeProd) {
        if (!activeProd || !activeProd.idGrupo) return null;
        
        const idGrupo = String(activeProd.idGrupo).trim();
        const idCat = activeProd.idCategoria ? String(activeProd.idCategoria).trim() : null;
        
        // 1. Probar GRUPO_IN
        for (const fila of filasPlataforma) {
            const tipo = fila.dataset.tipo;
            const valor = fila.dataset.valor || '';
            if (tipo === 'GRUPO_IN' && valor) {
                const grupos = valor.split(',').map(g => g.trim());
                if (grupos.includes(idGrupo)) {
                    return fila;
                }
            }
        }
        
        // 2. Probar CATEGORIA_IN
        if (idCat) {
            for (const fila of filasPlataforma) {
                const tipo = fila.dataset.tipo;
                const valor = fila.dataset.valor || '';
                if (tipo === 'CATEGORIA_IN' && valor) {
                    const cats = valor.split(',').map(c => c.trim());
                    if (cats.includes(idCat)) {
                        return fila;
                    }
                }
            }
        }
        
        // 3. Fallback a DEFAULT
        for (const fila of filasPlataforma) {
            if (fila.dataset.tipo === 'DEFAULT') {
                return fila;
            }
        }
        
        return null;
    }

    function actualizarSimulacionPlataformas(totalBaseSinComision) {
        if (totalBaseSinComision <= 0) return;

        const plataformasDom = document.querySelectorAll('.plataforma-data');
        
        // Determinar producto activo para la simulación
        let activeProduct = null;
        if (!modoCombo && currentIndividualProduct) {
            activeProduct = currentIndividualProduct;
        } else if (modoCombo && comboItems.length > 0) {
            // En combo, tomar el producto prioritario o el primero
            activeProduct = comboItems[0];
        }

        plataformasDom.forEach(plataforma => {
            const pid = plataforma.dataset.pid;
            const hasRules = plataforma.dataset.hasRules === '1';
            const filas = document.querySelectorAll(`.simulacion-row[data-pid="${pid}"]`);
            
            if (filas.length === 0) return;

            const matchedRow = (activeProduct && hasRules) ? getMatchingRule(filas, activeProduct) : null;
            let menorValor = null;
            let matchedValor = null;

            filas.forEach(fila => {
                const pctComision = parseFloat(fila.dataset.pct) || 0;
                const fleteFijo = parseFloat(fila.dataset.flete) || 0;
                
                // Replicando la fórmula del cotizador:
                // montoCalculo = totalBase * ((pctComision / 100) + 1)
                // totalSinFacturar = totalBase + (montoCalculo * (pctComision / 100))
                // totalFacturado = totalSinFacturar * facturacionFactor
                // promedioFinal = ((totalSinFacturar + totalFacturado) / 2) + fleteFijo
                
                const montoCalculo = totalBaseSinComision * ((pctComision / 100) + 1);
                const totalSinFacturar = totalBaseSinComision + (montoCalculo * (pctComision / 100));
                const totalFacturado = totalSinFacturar * window.APP_DATA.facturacionFactor;
                const promedioFinal = ((totalSinFacturar + totalFacturado) / 2) + fleteFijo;
                
                fila.querySelector('.val-tsfact').textContent = totalSinFacturar.toFixed(2);
                fila.querySelector('.val-tfact').textContent = totalFacturado.toFixed(2);
                fila.querySelector('.val-promedio').textContent = promedioFinal.toFixed(2);

                const badgeAplicable = fila.querySelector('.badge-aplicable');

                if (matchedRow && fila === matchedRow) {
                    matchedValor = promedioFinal;
                    fila.classList.add('bg-light-subtle', 'border-start', 'border-3', 'border-success');
                    if (badgeAplicable) badgeAplicable.classList.remove('d-none');
                } else {
                    fila.classList.remove('bg-light-subtle', 'border-start', 'border-3', 'border-success');
                    if (badgeAplicable) badgeAplicable.classList.add('d-none');
                }

                if (menorValor === null || promedioFinal < menorValor) {
                    menorValor = promedioFinal;
                }
            });

            // Actualizar badge en el encabezado del accordion
            const badge = document.querySelector(`.js-rango-recomendado[data-pid="${pid}"]`);
            if (badge) {
                if (matchedValor !== null) {
                    badge.textContent = `Sugerido: S/ ${matchedValor.toFixed(2)}`;
                    badge.className = 'badge bg-success rounded-pill js-rango-recomendado';
                    badge.title = matchedRow ? (matchedRow.dataset.nombre || 'Regla sugerida para el producto') : '';
                } else if (menorValor !== null) {
                    badge.textContent = `Desde S/ ${menorValor.toFixed(2)}`;
                    badge.className = 'badge bg-primary rounded-pill js-rango-recomendado';
                    badge.title = '';
                }
            }
        });
    }

    // Inicializar la vista ahora que todo está definido
    renderPlataformasTemplate();
    window.recalcularCotizacion();

});
