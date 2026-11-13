<script>
    // ========================
    // Google Maps
    // ========================
    function loadGoogleMapsScript() {
        if (typeof google === 'object' && typeof google.maps === 'object') return;
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initFlexMap`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    let map, markers = [],
        polyline;
    let manualMap, manualMarker, manualAutocomplete;
    @php
        $storeCoords = get_store_coordinates();
    @endphp
    // Coordenadas fijas de la tienda matriz
    const storeLocation = {
        lat: {{ $storeCoords['lat'] }},
        lng: {{ $storeCoords['lng'] }}
    };

    window.initFlexMap = function() {
        map = new google.maps.Map(document.getElementById("flexMap"), {
            zoom: 12,
            center: storeLocation,
            mapTypeControl: false,
        });
    };

    // ========================
    // Modal Mapa de Ruta
    // ========================
    let mapModalInstance = null;
    let currentMapZona = '';

    const mapModalEl = document.getElementById('mapModal');
    if (mapModalEl) {
        mapModalEl.addEventListener('shown.bs.modal', function() {
            if (map && currentMapZona) {
                google.maps.event.trigger(map, "resize");
                drawRouteForZone(currentMapZona);
            }
        });
    }

    function openMapModal(zona) {
        currentMapZona = zona;
        document.getElementById('mapModalTitle').innerText = 'Ruta Sugerida - Zona ' + zona;
        const modalEl = document.getElementById('mapModal');
        if (!mapModalInstance) {
            mapModalInstance = new bootstrap.Modal(modalEl);
        }
        mapModalInstance.show();
    }

    function drawRouteForZone(zona) {
        try {
            markers.forEach(m => m.setMap(null));
            markers = [];
            if (polyline) polyline.setMap(null);
            const tbody = document.querySelector(`tbody[data-zona="${zona}"]`);
            if (!tbody) {
                console.error("No se encontró el tbody para la zona:", zona);
                return;
            }
            const rows = tbody.querySelectorAll('tr.flex-row-draggable');
            const path = [storeLocation];
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(storeLocation);
            markers.push(new google.maps.Marker({
                position: storeLocation,
                map: map,
                title: 'Tienda (Origen)',
                icon: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
            }));
            rows.forEach((row, index) => {
                const lat = parseFloat(row.dataset.lat),
                    lng = parseFloat(row.dataset.lng);
                if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                    const pos = {
                        lat,
                        lng
                    };
                    path.push(pos);
                    bounds.extend(pos);
                    const isWsp = row.dataset.tipo === 'wsp';
                    markers.push(new google.maps.Marker({
                        position: pos,
                        map: map,
                        label: {
                            text: (index + 1).toString(),
                            color: isWsp ? "black" : "white",
                            fontWeight: "bold"
                        },
                        title: row.dataset.destino,
                        // Usar un ícono PNG simple para evitar errores de SVG Symbol
                        icon: isWsp ? 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png' : undefined
                    }));
                }
            });
            polyline = new google.maps.Polyline({
                path,
                geodesic: true,
                strokeColor: '#043e69',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                map
            });
            map.fitBounds(bounds);

            // Generar URL para el botón Copiar Enlace
            const copyBtn = document.getElementById('copyMapUrlBtn');
            if (path.length > 1) {
                const origin = `${storeLocation.lat},${storeLocation.lng}`;
                const destPoint = path[path.length - 1];
                const destination = `${destPoint.lat},${destPoint.lng}`;
                
                let waypoints = '';
                if (path.length > 2) {
                    const wpArray = [];
                    for (let i = 1; i < path.length - 1; i++) {
                        wpArray.push(`${path[i].lat},${path[i].lng}`);
                    }
                    waypoints = '&waypoints=' + wpArray.join('|');
                }
                
                const gmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}${waypoints}`;
                
                copyBtn.style.display = 'inline-block';
                copyBtn.onclick = function() {
                    navigator.clipboard.writeText(gmapsUrl).then(() => {
                        const originalHtml = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="bi bi-check2"></i> ¡Copiado!';
                        copyBtn.classList.replace('text-primary', 'text-success');
                        setTimeout(() => {
                            copyBtn.innerHTML = originalHtml;
                            copyBtn.classList.replace('text-success', 'text-primary');
                        }, 2000);
                    }).catch(err => {
                        console.error('Error al copiar al portapapeles: ', err);
                        alert('No se pudo copiar el enlace.');
                    });
                };
            } else {
                copyBtn.style.display = 'none';
            }
        } catch (error) {
            console.error("Error dibujando la ruta:", error);
            alert("Hubo un error al dibujar el mapa: " + error.message);
        }
    }

    // ========================
    // Modal Manual (CRUD WSP)
    // ========================
    let productosAgregados = [];

    let manualModalInstance = null;

    const manualModalEl = document.getElementById('manualModal');
    if (manualModalEl) {
        manualModalEl.addEventListener('shown.bs.modal', function() {
            initManualMap();
        });
    }

    function getModalidadManual() {
        const tienda = document.getElementById('manualTipoTienda');
        return (tienda && tienda.checked) ? 'tienda' : 'domicilio';
    }

    let costoEnvioDomicilioPrevio = '10.00';

    function cambiarModalidadManual() {
        const esTienda = getModalidadManual() === 'tienda';
        const costoInput = document.getElementById('manualCostoEnvio');

        document.querySelectorAll('.manual-solo-domicilio').forEach(el => {
            el.style.display = esTienda ? 'none' : '';
        });
        const dirInput = document.getElementById('manualDireccion');
        if (dirInput) dirInput.required = !esTienda;

        if (costoInput) {
            if (esTienda) {
                if (parseFloat(costoInput.value) > 0) costoEnvioDomicilioPrevio = costoInput.value;
                costoInput.value = '0.00';
                costoInput.readOnly = true;
                costoInput.classList.add('bg-light');
            } else {
                costoInput.readOnly = false;
                costoInput.classList.remove('bg-light');
                if (!(parseFloat(costoInput.value) > 0)) costoInput.value = costoEnvioDomicilioPrevio;
                if (typeof google !== 'undefined' && manualMap) {
                    setTimeout(() => google.maps.event.trigger(manualMap, 'resize'), 100);
                }
            }
        }
        recalcularTotalesManual();
    }

    function openManualModal(editData = null) {
        document.getElementById('manualEditId').value = '';
        document.getElementById('manualIdCliente').value = '';
        document.getElementById('manualNombreCliente').value = '';
        document.getElementById('manualDireccion').value = '';
        document.getElementById('manualTotalProductos').value = '0.00';
        document.getElementById('manualCostoEnvio').value = '10.00';
        document.getElementById('manualMontoTotal').value = '10.00';
        document.getElementById('manualLat').value = '-12.055775';
        document.getElementById('manualLng').value = '-77.037733';
        document.getElementById('manualTipoDomicilio').checked = true;
        costoEnvioDomicilioPrevio = '10.00';
        productosAgregados = [];

        if (editData) {
            document.getElementById('manualModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i> Editar Entrega WhatsApp';
            document.getElementById('manualEditId').value = editData.id;
            document.getElementById('manualIdCliente').value = editData.idCliente || '';
            document.getElementById('manualNombreCliente').value = editData.nombre || '';
            document.getElementById('manualDireccion').value = (editData.direccion && editData.direccion !== 'No especificada') ? editData.direccion : '';
            document.getElementById('manualCostoEnvio').value = parseFloat(editData.costo_envio || 0).toFixed(2);
            document.getElementById('manualLat').value = editData.latitud || editData.lat || '-12.055775';
            document.getElementById('manualLng').value = editData.longitud || editData.lng || '-77.037733';
            if (editData.tipo_entrega === 'tienda') {
                document.getElementById('manualTipoTienda').checked = true;
            }

            if (editData.productos && Array.isArray(editData.productos)) {
                productosAgregados = editData.productos.map(p => ({
                    idProducto: p.idProducto,
                    nombre: p.nombre,
                    cantidad: parseInt(p.cantidad) || 1,
                    precio: parseFloat(p.precio) || 0
                }));

                // Compatibilidad con pedidos antiguos donde el precio unitario se guardó en 0
                const sumaProductos = productosAgregados.reduce((acc, item) => acc + (item.cantidad * item.precio), 0);
                const totalGuardado = parseFloat(editData.monto_total || editData.total || 0);
                const envioGuardado = parseFloat(editData.costo_envio || 0);
                if ( productosAgregados.length > 0 && sumaProductos === 0 && totalGuardado > 0 ) {
                    const restante = Math.max(0, totalGuardado - envioGuardado);
                    productosAgregados[0].precio = +(restante / productosAgregados[0].cantidad).toFixed(2);
                }
            }
        } else {
            document.getElementById('manualModalTitle').innerHTML = '<i class="bi bi-whatsapp me-2"></i> Nueva Entrega WhatsApp';
        }

        renderProductos();
        cambiarModalidadManual();

        const modalEl = document.getElementById('manualModal');
        if (!manualModalInstance) {
            manualModalInstance = new bootstrap.Modal(modalEl);
        }

        manualModalInstance.show();
    }

    function obtenerDireccionDesdeMapa(soloSiVacio = false) {
        if (typeof google === 'undefined' || !google.maps) return;
        const dirInput = document.getElementById('manualDireccion');
        if (soloSiVacio && dirInput.value.trim() !== '' && dirInput.value.trim() !== 'No especificada') {
            return;
        }

        const lat = parseFloat(document.getElementById('manualLat').value);
        const lng = parseFloat(document.getElementById('manualLng').value);
        if (isNaN(lat) || isNaN(lng)) return;

        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat, lng } }, function(results, status) {
            if (status === 'OK' && results && results[0]) {
                dirInput.value = results[0].formatted_address;
            }
        });
    }

    function initManualMap() {
        const lat = parseFloat(document.getElementById('manualLat').value) || -12.055775;
        const lng = parseFloat(document.getElementById('manualLng').value) || -77.037733;

        if (manualMap) {
            manualMap.setCenter({
                lat,
                lng
            });
            manualMarker.setPosition({
                lat,
                lng
            });
            google.maps.event.trigger(manualMap, "resize");
            if (document.getElementById('manualEditId').value && !document.getElementById('manualDireccion').value.trim()) {
                obtenerDireccionDesdeMapa(true);
            }
            return;
        }

        manualMap = new google.maps.Map(document.getElementById("manualModalMap"), {
            zoom: 14,
            center: {
                lat,
                lng
            },
            mapTypeControl: false,
            streetViewControl: false,
        });
        manualMarker = new google.maps.Marker({
            position: {
                lat,
                lng
            },
            map: manualMap,
            draggable: true
        });

        manualMarker.addListener('dragend', function() {
            const pos = manualMarker.getPosition();
            document.getElementById('manualLat').value = pos.lat();
            document.getElementById('manualLng').value = pos.lng();
            obtenerDireccionDesdeMapa(true);
        });
        manualMap.addListener('click', function(e) {
            manualMarker.setPosition(e.latLng);
            document.getElementById('manualLat').value = e.latLng.lat();
            document.getElementById('manualLng').value = e.latLng.lng();
            obtenerDireccionDesdeMapa(true);
        });

        manualAutocomplete = new google.maps.places.Autocomplete(
            document.getElementById('manualDireccion'), {
                componentRestrictions: {
                    country: 'pe'
                },
                fields: ['geometry', 'formatted_address']
            }
        );
        manualAutocomplete.addListener('place_changed', function() {
            const place = manualAutocomplete.getPlace();
            if (!place.geometry) return;
            const loc = place.geometry.location;
            manualMap.setCenter(loc);
            manualMap.setZoom(17);
            manualMarker.setPosition(loc);
            document.getElementById('manualLat').value = loc.lat();
            document.getElementById('manualLng').value = loc.lng();
        });

        if (document.getElementById('manualEditId').value && !document.getElementById('manualDireccion').value.trim()) {
            obtenerDireccionDesdeMapa(true);
        }
    }

    // ========================
    // Buscador de Clientes
    // ========================
    let searchClienteTimeout;
    document.addEventListener('DOMContentLoaded', function() {
        const searchClienteInput = document.getElementById('manualNombreCliente');
        const resultsClienteList = document.getElementById('clienteSearchList');
        const resultsClienteContainer = document.getElementById('clienteSearchResults');

        if (searchClienteInput) {
            searchClienteInput.addEventListener('input', function() {
                clearTimeout(searchClienteTimeout);
                document.getElementById('manualIdCliente').value = ''; // Reset si escribe
                const q = this.value.trim();
                if (q.length < 2) {
                    resultsClienteContainer.classList.add('d-none');
                    return;
                }
                searchClienteTimeout = setTimeout(() => {
                    fetch('{{ route("delivery.flex.searchClientes") }}?q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(data => {
                            resultsClienteList.innerHTML = '';
                            if (data.length === 0) {
                                resultsClienteList.innerHTML = '<div class="list-group-item text-muted small">No se encontraron clientes</div>';
                            } else {
                                data.forEach(c => {
                                    const item = document.createElement('div');
                                    item.className = 'list-group-item';
                                    const ape = c.apellidoPaterno || '';
                                    item.innerHTML = '<strong>' + c.nombre + ' ' + ape + '</strong> <small class="text-muted">(' + (c.numeroDocumento || 'Sin doc') + ')</small>';
                                    item.addEventListener('click', () => {
                                        document.getElementById('manualIdCliente').value = c.idCliente;
                                        searchClienteInput.value = c.nombre + ' ' + ape;
                                        resultsClienteContainer.classList.add('d-none');
                                    });
                                    resultsClienteList.appendChild(item);
                                });
                            }
                            resultsClienteContainer.classList.remove('d-none');
                        });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!searchClienteInput.contains(e.target) && !resultsClienteContainer.contains(e.target)) {
                    resultsClienteContainer.classList.add('d-none');
                }
            });
        }
    });

    // Callback del modal de nuevo cliente
    window.onClienteCreado = function(cliente) {
        document.getElementById('manualIdCliente').value = cliente.idCliente;
        document.getElementById('manualNombreCliente').value = (cliente.nombre || '') + ' ' + (cliente.apellidoPaterno || '');
    };

    // ========================
    // Buscador de Productos
    // ========================
    let searchTimeout;
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('manualProductoSearch');
        const resultsList = document.getElementById('productoSearchList');
        const resultsContainer = document.getElementById('productoSearchResults');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const q = this.value.trim();
                if (q.length < 2) {
                    resultsContainer.classList.add('d-none');
                    return;
                }
                searchTimeout = setTimeout(() => {
                    fetch('{{ route("delivery.flex.searchProductos") }}?q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(data => {
                            resultsList.innerHTML = '';
                            if (data.length === 0) {
                                resultsList.innerHTML = '<div class="list-group-item text-muted small">No se encontraron productos</div>';
                            } else {
                                data.forEach(p => {
                                    const item = document.createElement('div');
                                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                                    const precioSug = parseFloat(p.precioSugerido || 0);
                                    const badgePrecio = precioSug > 0
                                        ? '<span class="badge bg-light text-dark border ms-2">S/ ' + precioSug.toFixed(2) + '</span>'
                                        : '';
                                    item.innerHTML = '<div><strong>' + p.nombreProducto + '</strong> <small class="text-muted">(' + (p.codigoProducto || 'Sin código') + ')</small></div>' + badgePrecio;
                                    item.addEventListener('click', () => {
                                        agregarProducto(p.idProducto, p.nombreProducto, precioSug);
                                        resultsContainer.classList.add('d-none');
                                        searchInput.value = '';
                                    });
                                    resultsList.appendChild(item);
                                });
                            }
                            resultsContainer.classList.remove('d-none');
                        });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.classList.add('d-none');
                }
            });
        }
    });

    function agregarProducto(idProducto, nombre, precioSugerido = 0) {
        const existente = productosAgregados.find(p => p.idProducto === idProducto);
        if (existente) {
            existente.cantidad += 1;
            renderProductos();
            return;
        }
        productosAgregados.push({
            idProducto,
            nombre,
            cantidad: 1,
            precio: parseFloat(precioSugerido) || 0
        });
        renderProductos();
    }

    function removerProducto(index) {
        productosAgregados.splice(index, 1);
        renderProductos();
    }

    function actualizarCantidadProducto(index, val) {
        const cant = Math.max(1, parseInt(val) || 1);
        productosAgregados[index].cantidad = cant;
        const subInput = document.getElementById('subtotalProd_' + index);
        if (subInput) {
            subInput.value = (cant * (parseFloat(productosAgregados[index].precio) || 0)).toFixed(2);
        }
        recalcularTotalesManual();
    }

    function actualizarPrecioProducto(index, val) {
        const precio = Math.max(0, parseFloat(val) || 0);
        productosAgregados[index].precio = precio;
        const subInput = document.getElementById('subtotalProd_' + index);
        if (subInput) {
            subInput.value = ((parseInt(productosAgregados[index].cantidad) || 1) * precio).toFixed(2);
        }
        recalcularTotalesManual();
    }

    function recalcularTotalesManual() {
        const subtotalProductos = productosAgregados.reduce((acc, p) => {
            return acc + ((parseInt(p.cantidad) || 1) * (parseFloat(p.precio) || 0));
        }, 0);

        const costoEnvioInput = document.getElementById('manualCostoEnvio');
        const costoEnvio = getModalidadManual() === 'tienda'
            ? 0
            : Math.max(0, parseFloat(costoEnvioInput ? costoEnvioInput.value : 0) || 0);
        const montoTotal = subtotalProductos + costoEnvio;

        const totalProdEl = document.getElementById('manualTotalProductos');
        if (totalProdEl) totalProdEl.value = subtotalProductos.toFixed(2);

        const montoTotalEl = document.getElementById('manualMontoTotal');
        if (montoTotalEl) montoTotalEl.value = montoTotal.toFixed(2);
    }

    function renderProductos() {
        const container = document.getElementById('productosSeleccionados');
        if (!container) return;
        if (productosAgregados.length === 0) {
            container.innerHTML = '<small class="text-muted">No se han agregado productos.</small>';
            recalcularTotalesManual();
            return;
        }
        let html = '<div class="d-flex justify-content-end gap-2 px-2 mb-1 text-muted" style="font-size: 11px; font-weight: 600;">' +
            '<div style="width: 65px;" class="text-center">Cant.</div>' +
            '<div style="width: 115px;" class="text-center">P. Unit. (S/)</div>' +
            '<div style="width: 115px;" class="text-center">Total Prod. (S/)</div>' +
            '<div style="width: 32px;"></div>' +
            '</div>';

        productosAgregados.forEach((p, i) => {
            const cant = parseInt(p.cantidad) || 1;
            const precio = parseFloat(p.precio) || 0;
            const subtotal = (cant * precio).toFixed(2);

            html += '<div class="producto-item d-flex align-items-center justify-content-between gap-2">' +
                '<div class="flex-grow-1"><strong class="small">' + p.nombre + '</strong></div>' +
                '<div class="d-flex align-items-center gap-2">' +
                '<input type="number" class="form-control form-control-sm text-center" style="width: 65px;" value="' + cant + '" min="1" oninput="actualizarCantidadProducto(' + i + ', this.value)" title="Cantidad">' +
                '<div class="input-group input-group-sm" style="width: 115px;">' +
                '<span class="input-group-text">S/</span>' +
                '<input type="number" class="form-control" step="0.01" min="0" value="' + precio.toFixed(2) + '" oninput="actualizarPrecioProducto(' + i + ', this.value)" title="Precio Unitario">' +
                '</div>' +
                '<div class="input-group input-group-sm" style="width: 115px;">' +
                '<span class="input-group-text bg-light">S/</span>' +
                '<input type="number" class="form-control bg-light fw-bold" id="subtotalProd_' + i + '" step="0.01" value="' + subtotal + '" readonly title="Total del Producto">' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" style="width: 32px;" onclick="removerProducto(' + i + ')" title="Quitar"><i class="bi bi-x"></i></button>' +
                '</div></div>';
        });
        container.innerHTML = html;
        recalcularTotalesManual();
    }

    // ========================
    // Guardar Entrega Manual
    // ========================
    function saveManual() {
        recalcularTotalesManual();
        const editId = document.getElementById('manualEditId').value;
        const tipoEntrega = getModalidadManual();
        const payload = {
            idCliente: document.getElementById('manualIdCliente').value,
            tipo_entrega: tipoEntrega,
            direccion: document.getElementById('manualDireccion').value.trim(),
            latitud: document.getElementById('manualLat').value,
            longitud: document.getElementById('manualLng').value,
            costo_envio: tipoEntrega === 'tienda' ? 0 : document.getElementById('manualCostoEnvio').value,
            monto_total: document.getElementById('manualMontoTotal').value,
            productos: productosAgregados.map(p => ({
                idProducto: p.idProducto,
                cantidad: parseInt(p.cantidad) || 1,
                precio: parseFloat(p.precio) || 0
            }))
        };

        if (!payload.idCliente) {
            alert('Por favor selecciona el Cliente.');
            return;
        }

        if (tipoEntrega === 'domicilio' && (!payload.direccion || payload.direccion === 'No especificada')) {
            alert('Por favor completa una Dirección de Entrega válida (o elige "Recojo en Tienda").');
            return;
        }

        if (payload.productos.length === 0) {
            alert('Por favor agrega al menos un producto a la entrega.');
            return;
        }

        const url = editId ?
            '{{ route("delivery.flex.update", ":id") }}'.replace(':id', editId) :
            '{{ route("delivery.flex.store") }}';
        const method = editId ? 'PUT' : 'POST';

        fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('manualModal')).hide();
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al guardar.');
                }
            })
            .catch(() => alert('Error de conexión.'));
    }

    // ========================
    // Editar / Eliminar
    // ========================
    function editManual(pedidoWebId) {
        fetch('{{ route("delivery.flex.getManual", ":id") }}'.replace(':id', pedidoWebId))
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    openManualModal(res.data);
                } else {
                    alert('Error al obtener datos del pedido.');
                }
            })
            .catch(() => alert('Error de conexión al obtener datos.'));
    }

    function completeManual(id) {
        if (!confirm('¿Marcar este pedido como despachado? Ya no aparecerá en la ruta Flex.')) return;

        const token = '{{ csrf_token() }}';
        const primaryUrl = '{{ route("delivery.flex.completeManual", ":id") }}'.replace(':id', id);
        const fallbackUrl = '{{ url("/dashboard/logistica/delivery-flex/manual") }}/' + id + '/complete';

        const sendReq = (url) => {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
        };

        sendReq(primaryUrl)
            .then(async (r) => {
                if (r.status === 404) {
                    return sendReq(fallbackUrl);
                }
                return r;
            })
            .then(async (r) => {
                const data = await r.json().catch(() => null);
                if (!r.ok) {
                    throw new Error((data && data.message) ? data.message : `Error HTTP ${r.status}`);
                }
                return data;
            })
            .then(data => {
                if (data && data.success) {
                    window.location.reload();
                } else {
                    alert((data && data.message) || 'Error al actualizar el estado.');
                }
            })
            .catch(err => {
                console.error('Error al despachar:', err);
                alert('No se pudo marcar como despachado: ' + err.message);
            });
    }

    function deleteManual(pedidoWebId) {
        if (!confirm('¿Seguro que deseas eliminar esta entrega?')) return;
        fetch('{{ route("delivery.flex.destroy", ":id") }}'.replace(':id', pedidoWebId), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al eliminar.');
                }
            });
    }

    // ========================
    // Editar Teléfono ML
    // ========================
    function editPhone(orderId, currentPhone, currentNote, currentDeliveryNote) {
        document.getElementById('editDeliveryOrderId').value = orderId;
        document.getElementById('editDeliveryPhone').value = currentPhone || '';
        document.getElementById('editDeliveryNote').value = currentNote || '';
        document.getElementById('editDeliveryDeliveryNote').value = currentDeliveryNote || '';
        const modal = new bootstrap.Modal(document.getElementById('modalEditDeliveryInfo'));
        modal.show();
    }

    function saveDeliveryInfo() {
        const orderId = document.getElementById('editDeliveryOrderId').value;
        const newPhone = document.getElementById('editDeliveryPhone').value;
        const newNote = document.getElementById('editDeliveryNote').value;
        const newDeliveryNote = document.getElementById('editDeliveryDeliveryNote').value;

        fetch('{{ url("dashboard/logistica/delivery-flex/update-delivery-info") }}/' + orderId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ buyer_phone: newPhone, note: newNote, delivery_note: newDeliveryNote })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const phoneText = data.buyer_phone || 'Sin teléfono';
                const phoneEl = document.getElementById('phone-text-' + orderId);
                if (phoneEl) {
                    phoneEl.innerHTML = '<i class="bi bi-telephone-fill me-1"></i>' + phoneText;
                }
                
                // Update note icon if exists
                const noteIcon = document.getElementById('note-icon-' + orderId);
                if (noteIcon) {
                    if (data.note) {
                        noteIcon.setAttribute('title', 'Palabra clave: ' + data.note);
                        noteIcon.classList.remove('d-none');
                    } else {
                        noteIcon.classList.add('d-none');
                    }
                }

                // Update delivery note text
                const noteCont = document.getElementById('delivery-note-container-' + orderId);
                const noteTxt = document.getElementById('delivery-note-text-' + orderId);
                if (noteCont && noteTxt) {
                    if (data.delivery_note) {
                        noteTxt.textContent = data.delivery_note;
                        noteCont.classList.remove('d-none');
                    } else {
                        noteCont.classList.add('d-none');
                    }
                }

                bootstrap.Modal.getInstance(document.getElementById('modalEditDeliveryInfo')).hide();
                // Optionally reload to update onclick arguments, but doing this dynamically is fine until refresh.
                window.location.reload(); 
            } else {
                alert('Error al guardar la información');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Ocurrió un error al intentar guardar la información.');
        });
    }

    // ========================
    // SortableJS
    // ========================
    function saveZoneOrder(tbodyEl) {
        if (!tbodyEl) return;
        const zona = tbodyEl.dataset.zona;
        const rows = tbodyEl.querySelectorAll('tr.flex-row-draggable');
        const orden = [];
        rows.forEach((row, index) => {
            orden.push(row.dataset.id);
            const badge = row.querySelector('.route-badge');
            if (badge) badge.innerText = index + 1;
        });

        if (document.getElementById('mapModal').classList.contains('show') && currentMapZona === zona) {
            drawRouteForZone(zona);
        }

        fetch('{{ route("delivery.flex.reorder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                zona: zona,
                orden: orden
            })
        }).then(r => r.json()).then(data => {
            if (!data.success) console.error("Error al guardar el orden para la zona " + zona);
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        loadGoogleMapsScript();
        document.querySelectorAll('.sortable-list').forEach(function(tbody) {
            new Sortable(tbody, {
                group: 'zonas-flex', // Permite arrastrar entre distintas zonas
                animation: 150,
                handle: '.drag-handle',
                onEnd: function(evt) {
                    // Actualizamos la zona destino (o la misma si no cambió)
                    saveZoneOrder(evt.to);
                    
                    // Si el pedido se movió a una zona diferente, actualizamos también la zona de origen
                    if (evt.from !== evt.to) {
                        saveZoneOrder(evt.from);
                        // Recargar la página para actualizar los contadores de pedidos y estructura de tablas
                        setTimeout(() => window.location.reload(), 300);
                    }
                }
            });
        });
    });
</script>