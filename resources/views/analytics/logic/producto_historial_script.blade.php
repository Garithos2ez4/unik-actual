<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar TomSelect
        let tomSelect = new TomSelect("#productoSelect", {
            valueField: 'idProducto',
            labelField: 'modelo',
            searchField: ['modelo', 'nombreProducto'],
            load: function(query, callback) {
                if (!query.length) return callback();

                // Usamos la ruta existente searchModelProduct
                fetch(`{{ route('searchmodelproduct') }}?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    })
                    .catch(() => {
                        callback();
                    });
            },
            render: {
                option: function(item, escape) {
                    return `<div>
                                <span class="fw-bold">${escape(item.modelo)}</span>
                                <br><small class="text-muted">${escape(item.nombreProducto || '')}</small>
                            </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.modelo)}</div>`;
                }
            },
            onChange: function(value) {
                document.getElementById('btnBuscar').disabled = !value;
            }
        });

        // Inicializar DataTable
        let tabla = $('#historialTable').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            order: [
                [0, 'desc']
            ], // Ordenar por fecha descendente
            columns: [{
                    data: 'fecha',
                    render: function(data, type, row) {
                        // DataTables usa 'sort' y 'type' para el ordenamiento
                        if (type === 'sort' || type === 'type') {
                            return row.fecha_sort;
                        }
                        return data;
                    }
                },
                {
                    data: 'canal',
                    render: function(data) {
                        return `<span class="badge bg-secondary opacity-75">${data}</span>`;
                    }
                },
                {
                    data: 'orden',
                    className: 'text-primary fw-bold'
                },
                {
                    data: 'serie',
                    className: 'text-center text-muted fw-semibold'
                },
                {
                    data: 'cantidad',
                    className: 'text-center fw-bold'
                },
                {
                    data: 'precio_unitario',
                    className: 'text-end text-muted'
                },
                {
                    data: 'estado',
                    className: 'text-center',
                    render: function(data) {
                        let color = 'secondary';
                        if (data === 'ENTREGADO' || data === 'COMPLETADO') color = 'success';
                        else if (data === 'DEVUELTO' || data === 'CANCELADO' || data === 'INVALIDO') color = 'danger';
                        else if (data === 'PENDIENTE') color = 'warning text-dark';
                        return `<span class="badge bg-${color} rounded-pill px-3">${data}</span>`;
                    }
                }
            ]
        });

        // Botón Buscar
        document.getElementById('btnBuscar').addEventListener('click', function() {
            let idProducto = tomSelect.getValue();
            let modelo = tomSelect.options[idProducto].modelo;

            if (!idProducto) return;

            // Mostrar card
            document.getElementById('resultadosCard').style.display = 'block';
            document.getElementById('productTitle').innerText = 'Historial de Ventas: ' + modelo;

            // Mostrar loading con SweetAlert o en tabla
            tabla.clear().draw();

            fetch(`{{ route('dashboard.analitica.producto.data') }}?idProducto=${idProducto}`)
                .then(response => response.json())
                .then(data => {
                    tabla.rows.add(data).draw();
                })
                .catch(err => {
                    console.error(err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'Ocurrió un error al obtener el historial.', 'error');
                    } else {
                        alert('Ocurrió un error al obtener el historial.');
                    }
                });
        });

        // Autocargar si vienen datos en la URL (desde la lista de productos)
        const urlParams = new URLSearchParams(window.location.search);
        const urlId = urlParams.get('idProducto');
        const urlModelo = urlParams.get('modelo');

        if (urlId && urlModelo) {
            tomSelect.addOption({
                idProducto: urlId,
                modelo: urlModelo
            });
            tomSelect.setValue(urlId);

            // Simular click en buscar
            let btnBuscar = document.getElementById('btnBuscar');
            if (btnBuscar) {
                // Esperar a que el botn est habilitado si no lo est todava
                setTimeout(() => {
                    btnBuscar.disabled = false;
                    btnBuscar.click();
                }, 100);
            }
        }
    });
</script>
