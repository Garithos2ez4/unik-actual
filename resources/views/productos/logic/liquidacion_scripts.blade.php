<script>
    const tcGlobal = {{ $tcGlobal }};
    
    document.querySelectorAll('.btn-liquidar').forEach(btn => {
        btn.addEventListener('click', function() {
            let id = this.getAttribute('data-id');
            let nombre = this.getAttribute('data-nombre');
            let costoUsd = parseFloat(this.getAttribute('data-costo')) || 0;
            
            document.getElementById('liq-id').value = id;
            document.getElementById('liq-nombre').textContent = nombre;
            document.getElementById('liq-costo').value = '$' + costoUsd.toFixed(2);
            
            let costoSolesRef = costoUsd * 1.18 * tcGlobal;
            document.getElementById('liq-costo-soles').value = costoSolesRef.toFixed(2);
            
            document.getElementById('liq-precio-final').value = costoSolesRef.toFixed(2);
        });
    });

    document.getElementById('btn-save-liquidar').addEventListener('click', function() {
        let id = document.getElementById('liq-id').value;
        let precio = document.getElementById('liq-precio-final').value;
        
        if (!precio || isNaN(precio) || parseFloat(precio) <= 0) {
            Swal.fire('Error', 'Debe ingresar un precio válido mayor a 0', 'warning');
            return;
        }

        let btn = this;
        btn.disabled = true;
        btn.innerHTML = 'Guardando...';

        fetch('{{ route("productos.liquidacion.agregar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                idProducto: id,
                precio_liquidacion: precio
            })
        })
        .then(async res => {
            if (!res.ok) {
                let errorData = await res.json().catch(() => ({ message: 'Error del servidor (' + res.status + ')' }));
                throw errorData;
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                Swal.fire('¡Liquidación Activada!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message || 'Error desconocido', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Activar Oferta';
            }
        })
        .catch(err => {
            console.error(err);
            let msg = err.message || 'Ocurrió un error al comunicar con el servidor';
            if (err.errors) {
                msg = Object.values(err.errors).flat().join('<br>');
            }
            Swal.fire('Error', msg, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Activar Oferta';
        });
    });

    document.querySelectorAll('.btn-quitar-liquidar').forEach(btn => {
        btn.addEventListener('click', function() {
            let id = this.getAttribute('data-id');
            Swal.fire({
                title: '¿Retirar de Liquidación?',
                text: "El producto volverá a mostrar su precio normal en la web.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, retirar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route("productos.liquidacion.quitar") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ idProducto: id })
                    })
                    .then(async res => {
                        if (!res.ok) {
                            let errorData = await res.json().catch(() => ({ message: 'Error del servidor (' + res.status + ')' }));
                            throw errorData;
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Retirado', data.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', err.message || 'Error al procesar la solicitud', 'error');
                    });
                }
            });
        });
    });

    // Initialize TomSelect for manual product search
    if (document.getElementById('buscar-producto-select')) {
        new TomSelect("#buscar-producto-select", {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            load: function(query, callback) {
                if (!query.length) return callback();
                fetch("{{ route('productos.liquidacion.buscar-ajax') }}?q=" + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(json => {
                        callback(json.results);
                    }).catch(()=>{
                        callback();
                    });
            },
            onChange: function(value) {
                if (!value) return;
                let item = this.options[value];
                if(item) {
                    // Populate modal
                    document.getElementById('liq-id').value = item.id;
                    document.getElementById('liq-nombre').textContent = item.nombre;
                    
                    let costoUsd = parseFloat(item.costoUsd) || 0;
                    document.getElementById('liq-costo').value = '$' + costoUsd.toFixed(2);
                    
                    let costoSolesRef = costoUsd * 1.18 * tcGlobal;
                    document.getElementById('liq-costo-soles').value = costoSolesRef.toFixed(2);
                    document.getElementById('liq-precio-final').value = costoSolesRef.toFixed(2);
                    
                    // Show modal
                    var myModal = new bootstrap.Modal(document.getElementById('modalLiquidar'));
                    myModal.show();
                    
                    // Clear selection so they can search again
                    this.clear(true);
                }
            },
            render: {
                option: function(item, escape) {
                    return '<div>' +
                        '<span class="d-block fw-bold">' + escape(item.nombre) + '</span>' +
                        '<small class="text-muted">Costo: $' + escape(parseFloat(item.costoUsd).toFixed(2)) + '</small>' +
                    '</div>';
                }
            }
        });
    }
</script>
