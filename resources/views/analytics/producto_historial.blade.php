@extends('layouts.app')

@section('title', 'Historial de Ventas por Producto')

@section('content')
<div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0 text-dark">
            <i class="bi bi-clock-history text-primary me-2"></i> Historial de Ventas por Producto
        </h2>
    </div>

    <!-- Buscador -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-search me-2"></i>Buscar Producto</h5>
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label for="productoSelect" class="form-label text-muted small fw-bold">Seleccione o escriba el nombre/modelo del producto</label>
                    <select id="productoSelect" class="form-control" placeholder="Escriba para buscar..."></select>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <button type="button" id="btnBuscar" class="btn btn-primary w-100 py-2 rounded-3 fw-bold" disabled>
                        <i class="bi bi-table me-2"></i> Ver Historial
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div class="card border-0 shadow-sm rounded-4" id="resultadosCard" style="display: none;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3" id="productTitle">Historial de Ventas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="historialTable" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-bold text-secondary">Fecha</th>
                            <th class="text-uppercase small fw-bold text-secondary">Canal</th>
                            <th class="text-uppercase small fw-bold text-secondary">Nro Orden / Factura</th>
                            <th class="text-uppercase small fw-bold text-secondary text-center">Cantidad</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Precio Unit.</th>
                            <th class="text-uppercase small fw-bold text-secondary text-end">Total</th>
                            <th class="text-uppercase small fw-bold text-secondary text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Llenado vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Añadimos jQuery y DataTables que se requieren -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

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
            order: [[0, 'desc']], // Ordenar por fecha descendente
            columns: [
                { 
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
                { data: 'orden', className: 'text-primary fw-bold' },
                { data: 'cantidad', className: 'text-center fw-bold' },
                { data: 'precio_unitario', className: 'text-end text-muted' },
                { 
                    data: 'total', 
                    className: 'text-end fw-bold text-success',
                    render: function(data) {
                        return 'S/ ' + data;
                    }
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
            tomSelect.addOption({ idProducto: urlId, modelo: urlModelo });
            tomSelect.setValue(urlId);
            
            // Simular click en buscar
            let btnBuscar = document.getElementById('btnBuscar');
            if(btnBuscar) {
                // Esperar a que el botn est habilitado si no lo est todava
                setTimeout(() => {
                    btnBuscar.disabled = false;
                    btnBuscar.click();
                }, 100);
            }
        }
    });
</script>
@endpush
