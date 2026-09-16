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
    const storeLocation = {
        lat: -12.0545,
        lng: -77.0388
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
        
        // DEBUG para ayudar al usuario a decirnos qué pasa
        if (zona === 'Este') {
            alert('Abriendo mapa para Zona Este. Si el modal no aparece después de este mensaje, hay un error en Bootstrap.');
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

    function openManualModal(editData = null) {
        document.getElementById('manualEditId').value = '';
        document.getElementById('manualIdCliente').value = '';
        document.getElementById('manualNombreCliente').value = '';
        document.getElementById('manualDireccion').value = '';
        document.getElementById('manualMontoTotal').value = '';
        document.getElementById('manualLat').value = '-12.046374';
        document.getElementById('manualLng').value = '-77.042793';
        productosAgregados = [];
        renderProductos();

        if (editData) {
            document.getElementById('manualModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i> Editar Entrega WhatsApp';
            document.getElementById('manualEditId').value = editData.id;
            document.getElementById('manualIdCliente').value = editData.idCliente || '';
            document.getElementById('manualNombreCliente').value = editData.nombre || '';
            document.getElementById('manualDireccion').value = editData.direccion || '';
            document.getElementById('manualMontoTotal').value = editData.monto_total || editData.total || '';
            document.getElementById('manualLat').value = editData.latitud || editData.lat || '-12.046374';
            document.getElementById('manualLng').value = editData.longitud || editData.lng || '-77.042793';
            if (editData.productos) {
                productosAgregados = editData.productos;
                renderProductos();
            }
        } else {
            document.getElementById('manualModalTitle').innerHTML = '<i class="bi bi-whatsapp me-2"></i> Nueva Entrega WhatsApp';
        }

        const modalEl = document.getElementById('manualModal');
        if (!manualModalInstance) {
            manualModalInstance = new bootstrap.Modal(modalEl);
        }
        
        manualModalInstance.show();
    }

    function initManualMap() {
        const lat = parseFloat(document.getElementById('manualLat').value) || -12.046374;
        const lng = parseFloat(document.getElementById('manualLng').value) || -77.042793;

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
        });
        manualMap.addListener('click', function(e) {
            manualMarker.setPosition(e.latLng);
            document.getElementById('manualLat').value = e.latLng.lat();
            document.getElementById('manualLng').value = e.latLng.lng();
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
                                    item.className = 'list-group-item';
                                    item.innerHTML = '<strong>' + p.nombreProducto + '</strong> <small class="text-muted">(' + (p.codigoProducto || 'Sin código') + ')</small>';
                                    item.addEventListener('click', () => {
                                        agregarProducto(p.idProducto, p.nombreProducto);
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

    function agregarProducto(idProducto, nombre) {
        if (productosAgregados.find(p => p.idProducto === idProducto)) return;
        productosAgregados.push({
            idProducto,
            nombre,
            cantidad: 1,
            precio: 0
        });
        renderProductos();
    }

    function removerProducto(index) {
        productosAgregados.splice(index, 1);
        renderProductos();
    }

    function renderProductos() {
        const container = document.getElementById('productosSeleccionados');
        if (!container) return;
        if (productosAgregados.length === 0) {
            container.innerHTML = '<small class="text-muted">No se han agregado productos.</small>';
            return;
        }
        let html = '';
        productosAgregados.forEach((p, i) => {
            html += '<div class="producto-item d-flex align-items-center justify-content-between">' +
                '<div class="flex-grow-1"><strong class="small">' + p.nombre + '</strong></div>' +
                '<div class="d-flex align-items-center gap-2">' +
                '<input type="number" class="form-control form-control-sm" style="width: 70px;" value="' + p.cantidad + '" min="1" onchange="productosAgregados[' + i + '].cantidad = parseInt(this.value)">' +
                '<input type="number" class="form-control form-control-sm" style="width: 90px;" value="' + p.precio + '" step="0.01" min="0" placeholder="Precio" onchange="productosAgregados[' + i + '].precio = parseFloat(this.value)">' +
                '<button class="btn btn-sm btn-outline-danger" onclick="removerProducto(' + i + ')"><i class="bi bi-x"></i></button>' +
                '</div></div>';
        });
        container.innerHTML = html;
    }

    // ========================
    // Guardar Entrega Manual
    // ========================
    function saveManual() {
        const editId = document.getElementById('manualEditId').value;
        const payload = {
            idCliente: document.getElementById('manualIdCliente').value,
            direccion: document.getElementById('manualDireccion').value,
            latitud: document.getElementById('manualLat').value,
            longitud: document.getElementById('manualLng').value,
            monto_total: document.getElementById('manualMontoTotal').value,
            productos: productosAgregados.map(p => ({
                idProducto: p.idProducto,
                cantidad: p.cantidad,
                precio: p.precio
            }))
        };

        if (!payload.idCliente || !payload.direccion || !payload.monto_total) {
            alert('Por favor completa los campos obligatorios (Cliente, Dirección, Monto).');
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
    // SortableJS
    // ========================
    document.addEventListener("DOMContentLoaded", function() {
        loadGoogleMapsScript();
        document.querySelectorAll('.sortable-list').forEach(function(tbody) {
            new Sortable(tbody, {
                animation: 150,
                handle: '.align-middle',
                onEnd: function(evt) {
                    const zona = tbody.dataset.zona;
                    const rows = tbody.querySelectorAll('tr.flex-row-draggable');
                    const orden = [];
                    rows.forEach((row, index) => {
                        orden.push(row.dataset.id);
                        const badge = row.querySelector('.route-badge');
                        if (badge) badge.innerText = index + 1;
                    });
                    if (document.getElementById('mapModal').classList.contains('show')) {
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
                        if (!data.success) console.error("Error al guardar el orden");
                    });
                }
            });
        });
    });
</script>
