
    const productoIdEncriptado = window.APP_DATA.productoIdEncriptado;

    // IDs de componentes ya agregados (para filtrar sugerencias)
    let componentesAgregados = new Set();

    // Estado de edición
    let editingIdHijo = null;

    // Configuración de Packs
    document.addEventListener('DOMContentLoaded', function() {
        loadPackComponents();

        const searchInput = document.getElementById('search-component-input');
        if (!searchInput) return;

        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value;

            if (query.length < 3) {
                document.getElementById('search-component-results').style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`/egresos/search-producto-ajax?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultsContainer = document.getElementById('search-component-results');
                        resultsContainer.innerHTML = '';

                        // Filtrar productos ya agregados (excepto el que se está editando)
                        const filtrados = data.filter(item =>
                            !componentesAgregados.has(parseInt(item.idProducto)) ||
                            parseInt(item.idProducto) === editingIdHijo
                        );

                        if (filtrados.length === 0) {
                            resultsContainer.innerHTML = '<div class="list-group-item text-muted">No se encontraron productos disponibles</div>';
                        } else {
                            filtrados.forEach(item => {
                                const div = document.createElement('a');
                                div.href = '#';
                                div.className = 'list-group-item list-group-item-action';
                                div.innerHTML = `<strong>${item.modelo}</strong> - <small>${item.nombreProducto}</small>`;
                                div.onclick = function(e) {
                                    e.preventDefault();
                                    document.getElementById('search-component-input').value = item.modelo;
                                    document.getElementById('selected-component-id').value = item.idProducto;
                                    document.getElementById('btn-add-component').disabled = false;
                                    resultsContainer.style.display = 'none';
                                };
                                resultsContainer.appendChild(div);
                            });
                        }
                        resultsContainer.style.display = 'block';
                    });
            }, 300);
        });

        // Ocultar resultados al hacer click fuera
        document.addEventListener('click', function(e) {
            if (e.target.id !== 'search-component-input') {
                const resultsContainer = document.getElementById('search-component-results');
                if(resultsContainer) resultsContainer.style.display = 'none';
            }
        });
    });

    function loadPackComponents() {
        fetch(`/producto/${productoIdEncriptado}/pack-components`)
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('pack-components-list');
                if(!tbody) return;

                tbody.innerHTML = '';

                // Actualizar el set de componentes ya agregados
                componentesAgregados = new Set(data.components.map(c => parseInt(c.idProductoHijo)));

                if (data.components.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay componentes configurados para este pack.</td></tr>';
                    return;
                }

                let totalPorcentaje = 0;

                data.components.forEach(comp => {
                    totalPorcentaje += parseFloat(comp.porcentaje_costo);
                    tbody.innerHTML += `
                        <tr id="comp-row-${comp.idProductoHijo}">
                            <td><i class="bi bi-box"></i> ${comp.nombreProducto}</td>
                            <td class="text-center text-primary fw-bold">${parseFloat(comp.porcentaje_costo).toFixed(2)}%</td>
                            <td class="text-center"><span class="badge bg-secondary">x${comp.cantidad}</span></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                                    title="Editar"
                                    onclick="editPackComponent(${comp.idProductoHijo}, '${comp.nombreProducto}', ${comp.cantidad}, ${comp.porcentaje_costo})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    title="Eliminar"
                                    onclick="removePackComponent(${comp.idProductoHijo})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                if (totalPorcentaje > 0 && Math.abs(totalPorcentaje - 100) > 0.01) {
                    tbody.innerHTML += `
                        <tr>
                            <td colspan="4" class="text-center text-warning bg-light">
                                <i class="bi bi-exclamation-triangle"></i> La suma de los porcentajes es ${totalPorcentaje.toFixed(2)}%. Se recomienda que sea exactamente 100%.
                            </td>
                        </tr>
                    `;
                }
            });
    }

    function editPackComponent(idHijo, nombreProducto, cantidad, porcentajeCosto) {
        editingIdHijo = idHijo;

        // Pre-llenar el formulario con los datos actuales
        document.getElementById('search-component-input').value = nombreProducto;
        document.getElementById('selected-component-id').value = idHijo;
        document.getElementById('cantidad-component-input').value = cantidad;
        document.getElementById('porcentaje-costo-input').value = porcentajeCosto;

        // Cambiar el botón a modo edición
        const btn = document.getElementById('btn-add-component');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-pencil-fill"></i> Actualizar';
        btn.classList.remove('btn-success');
        btn.classList.add('btn-warning');

        // Mostrar botón cancelar
        document.getElementById('btn-cancel-edit').classList.remove('d-none');

        // Resaltar la fila siendo editada
        document.querySelectorAll('#pack-components-list tr').forEach(r => r.classList.remove('table-warning'));
        const row = document.getElementById(`comp-row-${idHijo}`);
        if (row) row.classList.add('table-warning');

        // Scroll al formulario
        document.getElementById('search-component-input').scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('search-component-input').focus();
    }

    function cancelEdit() {
        editingIdHijo = null;
        document.getElementById('search-component-input').value = '';
        document.getElementById('selected-component-id').value = '';
        document.getElementById('cantidad-component-input').value = '1';
        document.getElementById('porcentaje-costo-input').value = '0';

        const btn = document.getElementById('btn-add-component');
        btn.disabled = true;
        btn.innerHTML = 'Agregar <i class="bi bi-plus-circle"></i>';
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-success');

        // Ocultar botón cancelar
        document.getElementById('btn-cancel-edit').classList.add('d-none');

        document.querySelectorAll('#pack-components-list tr').forEach(r => r.classList.remove('table-warning'));
    }

    function addPackComponent() {
        const idHijo = document.getElementById('selected-component-id').value;
        const cantidad = document.getElementById('cantidad-component-input').value;
        const porcentajeCosto = document.getElementById('porcentaje-costo-input').value;

        if (!idHijo || cantidad < 1 || porcentajeCosto < 0) return;

        // Si está en modo edición, usar PUT
        if (editingIdHijo !== null) {
            fetch(`/producto/${productoIdEncriptado}/pack-components/${editingIdHijo}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ cantidad, porcentaje_costo: porcentajeCosto })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    cancelEdit();
                    loadPackComponents();
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: 'Componente actualizado', showConfirmButton: false, timer: 1500
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
            return;
        }

        // Modo agregar (POST)
        fetch(`/producto/${productoIdEncriptado}/pack-components`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ idHijo, cantidad, porcentaje_costo: porcentajeCosto })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('search-component-input').value = '';
                document.getElementById('selected-component-id').value = '';
                document.getElementById('cantidad-component-input').value = '1';
                document.getElementById('porcentaje-costo-input').value = '0';
                document.getElementById('btn-add-component').disabled = true;
                loadPackComponents();
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Componente agregado', showConfirmButton: false, timer: 1500
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    function removePackComponent(idHijo) {
        // Si se está editando este componente, cancelar edición primero
        if (editingIdHijo === idHijo) cancelEdit();

        Swal.fire({
            title: '¿Eliminar componente?',
            text: "El producto ya no formará parte de este pack",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/producto/${productoIdEncriptado}/pack-components/${idHijo}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadPackComponents();
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: 'Componente removido', showConfirmButton: false, timer: 1500
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

