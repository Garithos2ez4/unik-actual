// egresos_masivos.js - Lógica para egreso masivo multiproducto con carrito
(function () {
    'use strict';

    let productosEnCarrito = []; // Array que almacena los productos agregados
    let productoSeleccionado = null; // Producto actualmente en búsqueda/selección
    let seriesDisponibles = []; // Series del producto activo
    let seriesSeleccionadas = new Set(); // IDs de las series checked del producto activo

    let pagosAgregados = [];
    let pagoIdCounter = 0;
    let debounceTimer = null;

    // Elementos DOM
    const inputBuscar = document.getElementById('input-buscar-producto');
    const suggestionsEl = document.getElementById('suggestions-producto');
    const seccionSeries = document.getElementById('seccion-series');
    const productoCard = document.getElementById('producto-seleccionado');

    // Configurar checkboxes por defecto al cargar la página (evita autocompletado del navegador)
    document.addEventListener('DOMContentLoaded', function () {
        const checkSkuMasivo = document.getElementById('check-sku-masivo');
        const checkOrdenNoAplica = document.getElementById('check-orden-no-aplica');
        if (checkSkuMasivo) {
            checkSkuMasivo.checked = false; 
            const inputSkuMasivo = document.getElementById('input-sku-masivo');
            if (inputSkuMasivo) {
                inputSkuMasivo.disabled = false;
                inputSkuMasivo.value = '';
            }
            const skuValidateMasivo = document.getElementById('sku-validate-masivo');
            if (skuValidateMasivo) {
                skuValidateMasivo.className = 'bi bi-check-circle text-success';
            }
        }
        if (checkOrdenNoAplica) checkOrdenNoAplica.checked = false;
    });

    // ==================== BÚSQUEDA DE PRODUCTOS ====================
    inputBuscar.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        if (query.length < 2) {
            suggestionsEl.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(() => {
            fetch(window.routes.searchProducto + '?query=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => renderSuggestions(data))
                .catch(() => suggestionsEl.innerHTML = '');
        }, 300);
    });

    function renderSuggestions(productos) {
        if (!productos.length) {
            suggestionsEl.innerHTML = '<li class="list-group-item text-muted">No se encontraron productos</li>';
            return;
        }

        suggestionsEl.innerHTML = productos.map(p => {
            const imgSrc = p.imagenProducto1 ? window.assetUrl + '/' + p.imagenProducto1 : '';
            return `
                    <li class="list-group-item list-group-item-action d-flex align-items-center" style="cursor:pointer" data-id="${p.idProducto}" data-nombre="${escapeHtml(p.nombreProducto)}" data-marca="${escapeHtml(p.nombreMarca || '')}" data-modelo="${escapeHtml(p.modelo || '')}" data-codigo="${escapeHtml(p.codigoProducto || '')}" data-img="${escapeHtml(imgSrc)}" data-precio="${p.precioDolar || 0}" data-precioweb="${p.precioWebSoles || 0}" data-preciotienda="${p.precioTiendaSoles || ''}">
                        ${imgSrc ? `<img src="${imgSrc}" style="width:35px;height:35px;object-fit:contain;margin-right:8px;border-radius:4px;background:#f1f1f1">` : ''}
                        <div>
                            <strong>${escapeHtml(p.nombreProducto)}</strong><br>
                            <small class="text-muted">${escapeHtml(p.nombreMarca || '')} | ${escapeHtml(p.modelo || '')} | ${escapeHtml(p.codigoProducto || '')}</small>
                        </div>
                    </li>
                `;
        }).join('');

        suggestionsEl.querySelectorAll('li').forEach(li => {
            li.addEventListener('click', function () {
                seleccionarProducto({
                    idProducto: this.dataset.id,
                    nombreProducto: this.dataset.nombre,
                    nombreMarca: this.dataset.marca,
                    modelo: this.dataset.modelo,
                    codigoProducto: this.dataset.codigo,
                    imagenProducto1: this.dataset.img,
                    precioDolar: this.dataset.precio,
                    precioWebSoles: this.dataset.precioweb,
                    precioTiendaSoles: this.dataset.preciotienda
                });
            });
        });
    }

    // ==================== SELECCIÓN DE PRODUCTO ====================
    function seleccionarProducto(producto) {
        productoSeleccionado = producto;
        suggestionsEl.innerHTML = '';
        inputBuscar.value = '';

        // Mostrar card del producto
        productoCard.style.display = '';
        document.getElementById('producto-img').src = producto.imagenProducto1 || '';
        document.getElementById('producto-nombre').textContent = producto.nombreProducto;
        document.getElementById('producto-marca').textContent = producto.nombreMarca;
        document.getElementById('producto-modelo').textContent = producto.modelo;
        document.getElementById('producto-codigo').textContent = producto.codigoProducto;

        // Limpiar inputs de SKU específicos del producto y activar "No aplica" por defecto
        const checkSkuMasivo = document.getElementById('check-sku-masivo');
        const inputSkuMasivo = document.getElementById('input-sku-masivo');
        checkSkuMasivo.checked = false; // False por defecto a petición del usuario
        inputSkuMasivo.disabled = false; // Habilitado por defecto
        inputSkuMasivo.value = ''; // "No aplica" por defecto
        document.getElementById('hidden-publicacion-id-masivo').value = '';
        document.getElementById('hidden-publicacion-precio-masivo').value = '';
        document.getElementById('suggestions-sku-masivo').innerHTML = '';
        document.getElementById('sku-validate-masivo').className = 'bi bi-check-circle text-success';

        // Calcular precio sugerido en Soles usando el precio calculado por el backend o fallback
        const precioWebSoles = parseFloat(producto.precioWebSoles) || 0;
        let precioSugeridoSoles = 0;
        if (precioWebSoles > 0) {
            precioSugeridoSoles = precioWebSoles.toFixed(2);
        } else {
            const tasaCambio = parseFloat(window.tasaCambio) || 3.42;
            const precioDolar = parseFloat(producto.precioDolar) || 0;
            precioSugeridoSoles = (precioDolar * tasaCambio).toFixed(2);
        }

        // Asignar al input de precio unitario
        document.getElementById('input-precio-unitario-masivo').value = precioSugeridoSoles > 0 ? precioSugeridoSoles : '';

        // Cargar series disponibles
        cargarSeries(producto.idProducto);
    }

    window.limpiarProducto = function () {
        productoSeleccionado = null;
        seriesDisponibles = [];
        seriesSeleccionadas.clear();

        productoCard.style.display = 'none';
        seccionSeries.style.display = 'none';

        // Limpiar input de precio
        document.getElementById('input-precio-unitario-masivo').value = '';
    };

    window.marcarComoRegalo = function (index) {
        let item = productosEnCarrito[index];
        if (!item) return;

        Swal.fire({
            title: 'Buscando serie disponible...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(window.routes.seriesDisponibles + '?idProducto=' + item.idProducto)
            .then(r => r.json())
            .then(data => {
                // Filtrar series que ya están en el carrito
                const seriesAgregadas = new Set();
                productosEnCarrito.forEach(prod => {
                    prod.series.forEach(s => seriesAgregadas.add(s.idRegistro));
                });

                const disponibles = data.filter(s => !seriesAgregadas.has(s.idRegistro));

                if (disponibles.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin stock disponible',
                        text: 'No hay más series disponibles en stock para agregar como regalo.'
                    });
                    return;
                }

                // Tomamos 1 serie disponible
                let serieRegalo = disponibles[0];

                // Verificamos si ya existe una fila de regalo para este mismo producto exacto
                let regaloExistenteIndex = productosEnCarrito.findIndex(p =>
                    p.idProducto === item.idProducto &&
                    p.idPublicacion === item.idPublicacion &&
                    p.precioVenta === 0.10
                );

                if (regaloExistenteIndex !== -1) {
                    // Agregamos a la fila de regalo existente
                    productosEnCarrito[regaloExistenteIndex].series.push(serieRegalo);
                } else {
                    // Creamos un nuevo item para el regalo
                    let nuevoItemRegalo = {
                        idProducto: item.idProducto,
                        nombreProducto: item.nombreProducto,
                        modelo: item.modelo,
                        imagenProducto1: item.imagenProducto1,
                        idPublicacion: item.idPublicacion,
                        sku: item.sku,
                        precioVenta: 0.10,
                        precioTiendaSoles: item.precioTiendaSoles,
                        series: [serieRegalo]
                    };
                    // Insertamos el nuevo item en el carrito después de la fila actual
                    productosEnCarrito.splice(index + 1, 0, nuevoItemRegalo);
                }

                renderCarrito();
                actualizarResumen();

                // Actualizar la vista de selección de series si el producto actual está activo en el buscador
                if (productoSeleccionado && productoSeleccionado.idProducto == item.idProducto) {
                    cargarSeries(item.idProducto);
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Regalo añadido',
                    text: `Se agregó la serie ${serieRegalo.numeroSerie} como regalo (S/ 0.10).`,
                    timer: 2000,
                    showConfirmButton: false
                });
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al consultar el stock.' });
            });
    };

    // ==================== CARGA DE SERIES ====================
    function cargarSeries(idProducto) {
        fetch(window.routes.seriesDisponibles + '?idProducto=' + idProducto)
            .then(r => r.json())
            .then(data => {
                // Filtrar series que ya están en el carrito de egreso para este u otros productos
                const seriesAgregadas = new Set();
                productosEnCarrito.forEach(item => {
                    item.series.forEach(s => seriesAgregadas.add(s.idRegistro));
                });

                seriesDisponibles = data.filter(s => !seriesAgregadas.has(s.idRegistro));

                if (seriesDisponibles.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin series disponibles',
                        text: 'Todas las series disponibles en stock de este producto ya han sido añadidas al carrito de egreso.'
                    });
                    limpiarProducto();
                    return;
                }

                seriesSeleccionadas.clear();
                renderSeries();

                document.getElementById('producto-stock-badge').textContent = seriesDisponibles.length + ' series disponibles';
                seccionSeries.style.display = '';

                const inputCantidad = document.getElementById('input-cantidad-series');
                inputCantidad.max = seriesDisponibles.length;
                inputCantidad.value = 0;
                document.getElementById('max-series-text').textContent = seriesDisponibles.length;
            })
            .catch(err => {
                console.error('Error cargando series:', err);
            });
    }

    function renderSeries() {
        const tbody = document.getElementById('tbody-series');
        if (!seriesDisponibles.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No hay series disponibles para este producto</td></tr>';
            return;
        }

        tbody.innerHTML = seriesDisponibles.map((s, i) => {
            const checked = seriesSeleccionadas.has(s.idRegistro) ? 'checked' : '';
            return `
                    <tr class="${checked ? 'table-primary' : ''}">
                        <td><input type="checkbox" class="check-serie" data-id="${s.idRegistro}" ${checked} onchange="toggleSerie(${s.idRegistro}, this)"></td>
                        <td>${i + 1}</td>
                        <td><code>${escapeHtml(s.numeroSerie)}</code></td>
                        <td><small>${escapeHtml(s.almacen)}</small></td>
                    </tr>
                `;
        }).join('');
    }

    // ==================== SELECCIÓN DE SERIES ====================
    window.toggleSerie = function (idRegistro, checkbox) {
        if (checkbox.checked) {
            seriesSeleccionadas.add(idRegistro);
        } else {
            seriesSeleccionadas.delete(idRegistro);
        }
        syncCantidadInput();
        renderSeries();
    };

    window.toggleTodasSeries = function (checkbox) {
        if (checkbox.checked) {
            seriesDisponibles.forEach(s => seriesSeleccionadas.add(s.idRegistro));
        } else {
            seriesSeleccionadas.clear();
        }
        document.getElementById('input-cantidad-series').value = seriesSeleccionadas.size;
        renderSeries();
    };

    window.seleccionarTodas = function () {
        seriesDisponibles.forEach(s => seriesSeleccionadas.add(s.idRegistro));
        document.getElementById('input-cantidad-series').value = seriesSeleccionadas.size;
        document.getElementById('check-todas-series').checked = true;
        renderSeries();
    };

    window.deseleccionarTodas = function () {
        seriesSeleccionadas.clear();
        document.getElementById('input-cantidad-series').value = 0;
        document.getElementById('check-todas-series').checked = false;
        renderSeries();
    };

    function syncCantidadInput() {
        document.getElementById('input-cantidad-series').value = seriesSeleccionadas.size;
    }

    // Input de cantidad: al cambiar, seleccionar las primeras N
    document.getElementById('input-cantidad-series').addEventListener('input', function () {
        let n = parseInt(this.value) || 0;
        if (n < 0) n = 0;
        if (n > seriesDisponibles.length) n = seriesDisponibles.length;
        this.value = n;

        seriesSeleccionadas.clear();
        for (let i = 0; i < n; i++) {
            seriesSeleccionadas.add(seriesDisponibles[i].idRegistro);
        }
        document.getElementById('check-todas-series').checked = (n === seriesDisponibles.length && n > 0);
        renderSeries();
    });

    // ==================== BÚSQUEDA DE PUBLICACIÓN (SKU) ====================
    const checkSkuMasivo = document.getElementById('check-sku-masivo');
    const inputSkuMasivo = document.getElementById('input-sku-masivo');

    checkSkuMasivo.addEventListener('change', function () {
        if (this.checked) {
            inputSkuMasivo.disabled = true;
            inputSkuMasivo.value = 'No aplica';
            document.getElementById('hidden-publicacion-id-masivo').value = '';
            document.getElementById('suggestions-sku-masivo').innerHTML = '';
            document.getElementById('sku-validate-masivo').className = 'bi bi-check-circle text-success';

            // Si es "No aplica", restauramos el precio original del producto en USD * tasa de cambio o el calculado de Web
            if (productoSeleccionado) {
                const precioWebSoles = parseFloat(productoSeleccionado.precioWebSoles) || 0;
                let precioSugeridoSoles = 0;
                if (precioWebSoles > 0) {
                    precioSugeridoSoles = precioWebSoles.toFixed(2);
                } else {
                    const tasaCambio = parseFloat(window.tasaCambio) || 3.42;
                    const precioDolar = parseFloat(productoSeleccionado.precioDolar) || 0;
                    precioSugeridoSoles = (precioDolar * tasaCambio).toFixed(2);
                }
                document.getElementById('input-precio-unitario-masivo').value = precioSugeridoSoles > 0 ? precioSugeridoSoles : '';
            }
        } else {
            inputSkuMasivo.disabled = false;
            inputSkuMasivo.value = '';
            document.getElementById('sku-validate-masivo').className = 'bi bi-exclamation-circle text-danger';
        }
    });

    window.searchPublicacionMasivo = function (input) {
        const query = input.value.trim();
        const sugList = document.getElementById('suggestions-sku-masivo');
        if (query.length < 2) {
            sugList.innerHTML = '';
            return;
        }

        fetch(window.routes.searchPublicacion + '?query=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    sugList.innerHTML = '<li class="list-group-item text-muted">Sin resultados</li>';
                    return;
                }
                sugList.innerHTML = data.map(p => `
                        <li class="list-group-item list-group-item-action" style="cursor:pointer" onclick="selectPublicacionMasivo(${p.idPublicacion}, '${escapeHtml(p.sku)}', ${p.precio || p.precioPublicacion || 0})">
                            <strong>${escapeHtml(p.sku)}</strong> - S/ ${(p.precio || p.precioPublicacion || 0).toFixed(2)}
                            <br><small class="text-muted">${escapeHtml(p.titulo || p.tituloPublicacion || '')}</small>
                        </li>
                    `).join('');
            });
    };

    window.selectPublicacionMasivo = function (id, sku, precio) {
        document.getElementById('input-sku-masivo').value = sku;
        document.getElementById('hidden-publicacion-id-masivo').value = id;
        document.getElementById('hidden-publicacion-precio-masivo').value = precio;
        document.getElementById('suggestions-sku-masivo').innerHTML = '';
        document.getElementById('sku-validate-masivo').className = 'bi bi-check-circle text-success';

        // Auto-completar el precio de venta unitario con el precio de la publicación en Soles
        if (precio !== undefined && precio !== null && precio !== '' && !isNaN(parseFloat(precio))) {
            document.getElementById('input-precio-unitario-masivo').value = parseFloat(precio).toFixed(2);
        }
    };

    // ==================== CARRITO STATE MANAGEMENT ====================
    window.agregarAlCarrito = function () {
        if (!productoSeleccionado) {
            Swal.fire({ icon: 'warning', title: 'Producto requerido', text: 'Por favor selecciona un producto.' });
            return;
        }

        if (seriesSeleccionadas.size === 0) {
            Swal.fire({ icon: 'warning', title: 'Series requeridas', text: 'Por favor selecciona al menos una serie para agregar a la lista.' });
            return;
        }

        const checkSku = document.getElementById('check-sku-masivo');
        const pubId = document.getElementById('hidden-publicacion-id-masivo').value;
        const skuVal = document.getElementById('input-sku-masivo').value.trim();

        if (!checkSku.checked && !pubId) {
            Swal.fire({ icon: 'warning', title: 'SKU Requerido', text: 'Por favor busca y selecciona un SKU válido de la lista o marca la casilla "No aplica".' });
            return;
        }

        const checkOrden = document.getElementById('check-orden-no-aplica');
        const ordenVal = document.getElementById('input-numero-orden-masivo').value.trim();

        if (!checkOrden.checked && ordenVal === '') {
            Swal.fire({ icon: 'warning', title: 'Número de Orden Requerido', text: 'Por favor ingresa un número de orden o marca la casilla "No aplica".' });
            return;
        }

        const precioVentaInput = document.getElementById('input-precio-unitario-masivo').value.trim();
        if (precioVentaInput === '') {
            Swal.fire({ icon: 'warning', title: 'Precio requerido', text: 'Por favor ingresa un precio de venta para este producto.' });
            return;
        }
        const precioVenta = parseFloat(precioVentaInput);
        if (isNaN(precioVenta) || precioVenta < 0) {
            Swal.fire({ icon: 'warning', title: 'Precio inválido', text: 'Por favor ingresa un precio de venta válido mayor o igual a 0.' });
            return;
        }

        // Obtener los objetos completos de las series seleccionadas
        const listadoSeries = seriesDisponibles.filter(s => seriesSeleccionadas.has(s.idRegistro));

        const nuevoItem = {
            idProducto: productoSeleccionado.idProducto,
            nombreProducto: productoSeleccionado.nombreProducto,
            modelo: productoSeleccionado.modelo,
            imagenProducto1: productoSeleccionado.imagenProducto1,
            idPublicacion: checkSku.checked ? 'NULO' : pubId,
            sku: checkSku.checked ? 'No aplica' : skuVal,
            numeroOrden: checkOrden.checked ? 'No aplica' : ordenVal,
            precioVenta: precioVenta,
            precioTiendaSoles: productoSeleccionado.precioTiendaSoles,
            series: listadoSeries
        };

        productosEnCarrito.push(nuevoItem);

        // Resetear selección actual
        limpiarProducto();

        // Renderizar y actualizar
        renderCarrito();
        actualizarResumen();
    };

    function renderCarrito() {
        const sectionCarrito = document.getElementById('seccion-carrito');
        const formEgreso = document.getElementById('form-egreso-masivo');
        const tbody = document.getElementById('tbody-carrito');
        const badge = document.getElementById('badge-total-items-carrito');

        if (productosEnCarrito.length === 0) {
            sectionCarrito.style.display = 'none';
            formEgreso.style.display = 'none';
            tbody.innerHTML = '';
            badge.textContent = '0 items';
            return;
        }

        sectionCarrito.style.display = '';
        formEgreso.style.display = '';
        tbody.innerHTML = '';

        let totalSeries = 0;
        let totalVentaLote = 0;

        productosEnCarrito.forEach((item, index) => {
            totalSeries += item.series.length;
            const subtotal = item.precioVenta * item.series.length;
            totalVentaLote += subtotal;

            const seriesBadges = item.series.map(s => `
                    <span class="badge bg-secondary me-1 py-1" style="font-family: monospace;">${escapeHtml(s.numeroSerie)}</span>
                `).join('');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                    <td class="text-center">
                        <img src="${item.imagenProducto1 || ''}" style="width:40px;height:40px;object-fit:contain;border-radius:4px;background:#f8f9fa;">
                    </td>
                    <td>
                        <strong>${escapeHtml(item.nombreProducto)}</strong><br>
                        <small class="text-muted">Modelo: ${escapeHtml(item.modelo)}</small>
                        ${item.precioTiendaSoles ? `<br><span class="badge bg-success mt-1" style="font-size:0.7em" title="Precio en tienda física">Tienda: S/ ${parseFloat(item.precioTiendaSoles).toFixed(2)}</span>` : ''}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary fs-7">
                            ${escapeHtml(item.numeroOrden)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge ${item.sku === 'No aplica' ? 'bg-warning text-dark' : 'bg-success'} fs-7">
                            ${escapeHtml(item.sku)}
                        </span>
                    </td>
                    <td>
                        <div class="input-group input-group-sm mx-auto" style="min-width: 90px; max-width: 120px;">
                            <span class="input-group-text px-1 py-0" style="font-size: 12px;">S/</span>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end fw-bold px-1" value="${item.precioVenta.toFixed(2)}" onchange="actualizarPrecioCarrito(${index}, this.value)">
                        </div>
                    </td>
                    <td class="text-center fw-bold fs-6">${item.series.length}</td>
                    <td class="text-end fw-bold text-primary">S/ ${subtotal.toFixed(2)}</td>
                    <td>${seriesBadges}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 me-1" onclick="marcarComoRegalo(${index})" title="Marcar como Regalo (S/ 0.10)">
                            <i class="bi bi-gift-fill"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="eliminarDelCarrito(${index})" title="Quitar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
            tbody.appendChild(tr);
        });

        badge.textContent = totalSeries + ' serie(s) agregadas';
        document.getElementById('span-total-venta-lote').textContent = totalVentaLote.toFixed(2);
    }

    window.eliminarDelCarrito = function (index) {
        productosEnCarrito.splice(index, 1);
        renderCarrito();
        actualizarResumen();
    };

    window.actualizarPrecioCarrito = function (index, nuevoPrecio) {
        let precio = parseFloat(nuevoPrecio);
        if (isNaN(precio) || precio < 0) {
            precio = 0;
        }
        if (productosEnCarrito[index]) {
            productosEnCarrito[index].precioVenta = precio;
            renderCarrito();
            actualizarResumen();
        }
    };

    // ==================== NÚMERO DE ORDEN GLOBAL VALIDATOR ====================
    const checkOrdenNoAplica = document.getElementById('check-orden-no-aplica');
    const inputNumeroOrden = document.getElementById('input-numero-orden-masivo');

    checkOrdenNoAplica.addEventListener('change', function () {
        if (this.checked) {
            inputNumeroOrden.disabled = true;
            inputNumeroOrden.value = 'No aplica';
        } else {
            inputNumeroOrden.disabled = false;
            inputNumeroOrden.value = '';
        }
    });

    // ==================== RESUMEN Y VALIDACIÓN GLOBAL ====================
    function actualizarResumen() {
        let total = 0;
        let totalVentaLote = 0;
        let esVentaTienda = true;

        productosEnCarrito.forEach(item => {
            total += item.series.length;
            totalVentaLote += item.precioVenta * item.series.length;
            if (item.sku !== 'No aplica' || item.numeroOrden !== 'No aplica') {
                esVentaTienda = false;
            }
        });

        const divCliente = document.getElementById('div-cliente-egreso-masivo');
        const seccionPagos = document.getElementById('seccion-pagos-masivo');
        
        if (productosEnCarrito.length > 0 && esVentaTienda) {
            if (divCliente) divCliente.style.display = 'flex';
            if (seccionPagos) seccionPagos.classList.remove('d-none');
        } else {
            if (divCliente) divCliente.style.display = 'none';
            if (seccionPagos) seccionPagos.classList.add('d-none');
            if (typeof clearClienteMasivo === 'function') clearClienteMasivo();
        }

        const texto = document.getElementById('resumen-texto');
        const btnSubmit = document.getElementById('btn-registrar-masivo');

        let validate = true;
        let errorMsg = '';

        if (total === 0) {
            texto.textContent = 'Añade productos al carrito para continuar';
            validate = false;
        } else {
            // Pagos masivos desactivados temporalmente porque ahora los números de orden son por ítem
            if (validate) {
                texto.innerHTML = `Se procesará el egreso masivo de un lote con <strong>${total} serie(s)</strong> en total a través de <strong>${productosEnCarrito.length} producto(s)</strong> diferentes. Total Venta: <strong>S/ ${totalVentaLote.toFixed(2)}</strong>.`;
            } else {
                texto.innerHTML = `Se procesará el egreso masivo de un lote con <strong>${total} serie(s)</strong> en total. Total Venta: <strong>S/ ${totalVentaLote.toFixed(2)}</strong>.${errorMsg}`;
            }
        }

        btnSubmit.disabled = !validate;
        actualizarHiddenItems();
    }

    function actualizarHiddenItems() {
        const container = document.getElementById('hidden-items-container');
        container.innerHTML = '';

        let index = 0;
        productosEnCarrito.forEach(item => {
            item.series.forEach(serie => {
                container.innerHTML += `
                        <input type="hidden" name="items[${index}][idregistro]" value="${serie.idRegistro}">
                        <input type="hidden" name="items[${index}][idpublicacion]" value="${item.idPublicacion || 'NULO'}">
                        <input type="hidden" name="items[${index}][precioVenta]" value="${item.precioVenta}">
                        <input type="hidden" name="items[${index}][numeroorden]" value="${item.numeroOrden}">
                    `;
                index++;
            });
        });
    }

    // ==================== REGISTRO DE PAGOS ====================
    const selectMetodoMasivo = document.getElementById('pago-metodo-masivo');
    const selectEmpresaMasivo = document.getElementById('pago-empresa-masivo');
    const selectCuentaMasivo = document.getElementById('pago-cuenta-masivo');

    function calcularSaldoPendiente() {
        let totalVentaLote = 0;
        productosEnCarrito.forEach(item => {
            totalVentaLote += item.precioVenta * item.series.length;
        });

        let totalPagado = 0;
        pagosAgregados.forEach(p => totalPagado += p.monto);

        return Math.max(0, totalVentaLote - totalPagado);
    }

    selectMetodoMasivo.addEventListener('change', function () {
        const metodo = this.options[this.selectedIndex];
        const nombre = metodo ? metodo.text.toUpperCase() : '';
        const necesitaCuenta = nombre.includes('TRANSFERENCIA') || nombre.includes('YAPE') || nombre.includes('PLIN') || nombre.includes('DEPOSITO');

        document.getElementById('div-pago-empresa-masivo').style.display = necesitaCuenta ? '' : 'none';
        document.getElementById('div-pago-cuenta-masivo').style.display = necesitaCuenta ? '' : 'none';

        if (necesitaCuenta) {
            filtrarCuentasMasivo();
        } else {
            selectEmpresaMasivo.value = '';
            selectCuentaMasivo.value = '';
        }

        // Auto-llenar el monto con el saldo pendiente
        const inputMonto = document.getElementById('pago-monto-masivo');
        if (this.value && (!inputMonto.value || parseFloat(inputMonto.value) === 0)) {
            const saldo = calcularSaldoPendiente();
            if (saldo > 0) {
                inputMonto.value = saldo.toFixed(2);
            }
        }
    });

    selectEmpresaMasivo.addEventListener('change', filtrarCuentasMasivo);

    function filtrarCuentasMasivo() {
        const idEmpresa = selectEmpresaMasivo.value;
        const metodo = selectMetodoMasivo.options[selectMetodoMasivo.selectedIndex];
        const nombreMetodo = metodo ? metodo.text.toUpperCase() : '';
        const options = selectCuentaMasivo.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return;

            const optIdEmpresa = opt.dataset.idempresa;
            const banco = opt.dataset.banco ? opt.dataset.banco.toUpperCase() : '';

            let show = true;

            // Filtrar por Empresa si se seleccionó una
            if (idEmpresa && optIdEmpresa !== idEmpresa) {
                show = false;
            }

            // Filtrar por banco si es Yape o Plin
            if (show) {
                if (nombreMetodo.includes('YAPE')) {
                    show = banco.includes('BCP');
                } else if (nombreMetodo.includes('PLIN')) {
                    show = banco.includes('BBVA') || banco.includes('INTERBANK') || banco.includes('SCOTIABANK');
                }
            }

            opt.style.display = show ? '' : 'none';
        });

        // Si la cuenta seleccionada actualmente ya no es visible, deseleccionarla
        if (selectCuentaMasivo.selectedIndex > 0) {
            const selectedOpt = selectCuentaMasivo.options[selectCuentaMasivo.selectedIndex];
            if (selectedOpt.style.display === 'none') {
                selectCuentaMasivo.value = '';
            }
        }
    }

    document.getElementById('btn-add-pago-masivo').addEventListener('click', function () {
        const metodoSelect = selectMetodoMasivo;
        const idMetodo = metodoSelect.value;
        if (!idMetodo) return;

        const nombreMetodo = metodoSelect.options[metodoSelect.selectedIndex].text;
        const monto = parseFloat(document.getElementById('pago-monto-masivo').value) || 0;
        if (monto <= 0) return;

        const idCuenta = selectCuentaMasivo.value || null;

        pagosAgregados.push({
            id: ++pagoIdCounter,
            idMetodo,
            nombreMetodo,
            monto,
            idCuenta
        });

        renderPagosMasivo();
        document.getElementById('pago-monto-masivo').value = '';
    });

    function renderPagosMasivo() {
        const tabla = document.getElementById('tabla-pagos-masivo');
        const tbody = tabla.querySelector('tbody');
        const hiddenContainer = document.getElementById('hidden-pagos-container-masivo');

        if (!pagosAgregados.length) {
            tabla.style.display = 'none';
            tbody.innerHTML = '';
            hiddenContainer.innerHTML = '';
            actualizarResumen();
            return;
        }

        tabla.style.display = '';
        tbody.innerHTML = '';
        hiddenContainer.innerHTML = '';

        let total = 0;
        pagosAgregados.forEach((p, i) => {
            total += p.monto;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                    <td>${p.nombreMetodo}</td>
                    <td>S/ ${p.monto.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removePagoMasivo(${p.id})"><i class="bi bi-x"></i></button></td>
                `;
            tbody.appendChild(tr);

            hiddenContainer.innerHTML += `
                    <input type="hidden" name="pagos[${i}][idMetodo]" value="${p.idMetodo}">
                    <input type="hidden" name="pagos[${i}][monto]" value="${p.monto}">
                    <input type="hidden" name="pagos[${i}][idCuentaBancaria]" value="${p.idCuenta || ''}">
                `;
        });

        document.getElementById('total-pagado-masivo').textContent = total.toFixed(2);
        actualizarResumen();
    }

    window.removePagoMasivo = function (id) {
        pagosAgregados = pagosAgregados.filter(p => p.id !== id);
        renderPagosMasivo();
    };

    // ==================== SUBMIT DE LOTE MASIVO ====================
    document.getElementById('form-egreso-masivo')?.addEventListener('submit', function (e) {
        e.preventDefault();

        if (productosEnCarrito.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Carrito vacío', text: 'Por favor agrega al menos un producto con sus series antes de registrar.' });
            return;
        }

        const fechaPedido = document.getElementById('fechapedido-masivo').value;
        const fechaDespacho = document.getElementById('fechadespacho-masivo').value;
        if (!fechaPedido || !fechaDespacho) {
            Swal.fire({ icon: 'warning', title: 'Fechas requeridas', text: 'Por favor completa ambas fechas de pedido y despacho.' });
            return;
        }

        // Forzar actualización de inputs hidden
        actualizarHiddenItems();

        let totalSeries = 0;
        productosEnCarrito.forEach(item => totalSeries += item.series.length);

        Swal.fire({
            title: 'Confirmar Egreso Lote Masivo',
            html: `<p>Se registrará el egreso de un total de <strong>${totalSeries} serie(s)</strong> de <strong>${productosEnCarrito.length} tipos de productos</strong> diferentes.<br><br>¿Deseas guardar este lote?</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar lote',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                // Habilitar temporalmente los inputs deshabilitados antes del submit para que viajen al servidor
                if (inputNumeroOrden) inputNumeroOrden.disabled = false;
                this.submit();
            }
        });
    });

    // ==================== UTILIDADES ====================
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ==================== BÚSQUEDA DE CLIENTE MASIVO ====================
    window.searchClienteMasivo = function (inputElement) {
        let query = inputElement.value;
        const suggestions = document.getElementById('suggestions-cliente-masivo');
        const hiddenId = document.getElementById('hidden-id-cliente-masivo');
        const btnClear = document.getElementById('btn-clear-cliente-masivo');

        if (query.length > 2) {
            hiddenId.value = "";
            btnClear.style.display = 'none';

            fetch(`/cliente/searchcliente?query=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    suggestions.innerHTML = '';
                    inputElement.style.zIndex = '1000';

                    data.forEach(item => {
                        let li = document.createElement('li');
                        li.classList.add('list-group-item', 'pe-0', 'hover-sistema-uno', 'text-truncate');
                        li.style.cursor = "pointer";

                        let nombreCompleto = item.nombre + (item.apePaterno ? ' ' + item.apePaterno : '');
                        li.innerHTML = `<strong>${escapeHtml(item.numeroDocumento)}</strong> - ${escapeHtml(nombreCompleto)}`;

                        li.addEventListener('click', function () {
                            inputElement.value = nombreCompleto;
                            hiddenId.value = item.idCliente;
                            btnClear.style.display = 'block';

                            suggestions.innerHTML = '';
                            inputElement.style.zIndex = '1';
                            inputElement.readOnly = true;
                        });

                        suggestions.appendChild(li);
                    });
                })
                .catch(err => console.error('Error buscando cliente:', err));
        } else {
            suggestions.innerHTML = '';
            hiddenId.value = "";
            btnClear.style.display = 'none';
            inputElement.style.zIndex = '1';
        }
    };

    window.clearClienteMasivo = function () {
        let inputElement = document.getElementById('input-cliente-egreso-masivo');
        if (inputElement) {
            inputElement.value = '';
            inputElement.readOnly = false;
        }
        const hiddenId = document.getElementById('hidden-id-cliente-masivo');
        if (hiddenId) hiddenId.value = '';
        const btnClear = document.getElementById('btn-clear-cliente-masivo');
        if (btnClear) btnClear.style.display = 'none';
        const suggestions = document.getElementById('suggestions-cliente-masivo');
        if (suggestions) suggestions.innerHTML = '';
    };

    // Click fuera cierra sugerencias
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#input-buscar-producto') && !e.target.closest('#suggestions-producto')) {
            suggestionsEl.innerHTML = '';
        }
        if (!e.target.closest('#input-sku-masivo') && !e.target.closest('#suggestions-sku-masivo')) {
            const sug = document.getElementById('suggestions-sku-masivo');
            if (sug) sug.innerHTML = '';
        }
        if (!e.target.closest('#input-cliente-egreso-masivo') && !e.target.closest('#suggestions-cliente-masivo')) {
            const sugC = document.getElementById('suggestions-cliente-masivo');
            if (sugC) sugC.innerHTML = '';
        }
    });

})();
