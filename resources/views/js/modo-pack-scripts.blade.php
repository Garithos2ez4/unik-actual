@if($producto->GrupoProducto && $producto->GrupoProducto->nombreGrupo === 'Cabezales' && $producto->GrupoProducto->CategoriaProducto && $producto->GrupoProducto->CategoriaProducto->nombreCategoria === 'Impresoras')
<script>
    const productoIdEncriptado = "{{ encrypt($producto->idProducto) }}";
    
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
                        
                        if (data.length === 0) {
                            resultsContainer.innerHTML = '<div class="list-group-item text-muted">No se encontraron productos</div>';
                        } else {
                            data.forEach(item => {
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
                
                if (data.components.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay componentes configurados para este pack.</td></tr>';
                    return;
                }

                let totalPorcentaje = 0;

                data.components.forEach(comp => {
                    totalPorcentaje += parseFloat(comp.porcentaje_costo);
                    tbody.innerHTML += `
                        <tr>
                            <td><i class="bi bi-box"></i> ${comp.nombreProducto}</td>
                            <td class="text-center text-primary fw-bold">${comp.porcentaje_costo}%</td>
                            <td class="text-center"><span class="badge bg-secondary">x${comp.cantidad}</span></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePackComponent(${comp.idProductoHijo})"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `;
                });

                if (totalPorcentaje > 0 && totalPorcentaje !== 100) {
                    tbody.innerHTML += `
                        <tr>
                            <td colspan="4" class="text-center text-warning bg-light">
                                <i class="bi bi-exclamation-triangle"></i> La suma de los porcentajes es ${totalPorcentaje}%. Se recomienda que sea exactamente 100%.
                            </td>
                        </tr>
                    `;
                }
            });
    }

    function addPackComponent() {
        const idHijo = document.getElementById('selected-component-id').value;
        const cantidad = document.getElementById('cantidad-component-input').value;
        const porcentajeCosto = document.getElementById('porcentaje-costo-input').value;

        if (!idHijo || cantidad < 1 || porcentajeCosto < 0) return;

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
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Componente agregado',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    function removePackComponent(idHijo) {
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
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Componente removido',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }
</script>
@endif
