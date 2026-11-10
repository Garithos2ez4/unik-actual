<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    async function guardarCeldaInline(id, campo, valor, inputEl = null) {
        try {
            const res = await fetch(`{{ url('dashboard/laptops/seguimiento') }}/${id}/inline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    campo,
                    valor
                })
            });
            const data = await res.json();
            if (data.ok) {
                if (campo.startsWith('prov_')) {
                    const idProv = campo.replace('prov_', '');
                    const td = document.getElementById(`celda-prov-${id}-${idProv}`);
                    const tipo = data.proveedores_data?.[idProv]?.tipo || 'VACIO';
                    if (td) {
                        td.className = `text-center celda-prov-${tipo}`;
                    }
                }
                if (campo === 'idProducto' && data.frecuencia) {
                    const fila = document.getElementById(`fila-seg-${id}`);
                    const freqInput = fila?.querySelector('select.sel-frecuencia');
                    if (freqInput && (!freqInput.value || freqInput.value === '-')) {
                        freqInput.value = data.frecuencia;
                    }
                }
                const tdFecha = document.getElementById(`celda-fecha-${id}`);
                if (tdFecha) {
                    tdFecha.classList.toggle('celda-fecha-vencida', !!data.vencido);
                    const dateInput = tdFecha.querySelector('input[type="date"]');
                    if (dateInput && data.fecha_revision) {
                        dateInput.value = data.fecha_revision;
                    }
                }
            }
        } catch (e) {
            console.error('Error guardando celda:', e);
        }
    }

    async function marcarRevisadoHoy(id) {
        await guardarCeldaInline(id, 'fecha_hoy', 1);
    }

    async function toggleCuentaMl(id, idCuenta, pillEl) {
        try {
            const res = await fetch(`{{ url('dashboard/laptops/seguimiento') }}/${id}/inline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    campo: 'toggle_cuenta_ml',
                    valor: idCuenta
                })
            });
            const data = await res.json();
            if (data.ok && pillEl) {
                const ids = data.cuentas_ml_data?.ids || [];
                pillEl.classList.toggle('inactiva', !ids.includes(Number(idCuenta)));

                // Feedback de sincronización con la API de Mercado Libre
                if (data.ml && data.ml.message) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: data.ml.synced ? 3000 : 4500,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: data.ml.synced ? 'success' : (data.ml.error ? 'warning' : 'info'),
                        title: data.ml.message
                    });
                }
            }
        } catch (e) {
            console.error('Error actualizando cuenta ML:', e);
        }
    }

    async function cambiarEstadoFila(id, nuevoEstado) {
        await guardarCeldaInline(id, 'estado_fila', nuevoEstado);
        const tr = document.getElementById(`fila-seg-${id}`);
        if (tr) {
            tr.className = tr.className.replace(/\bfila-estado-\S+/g, '').trim() + ` fila-estado-${nuevoEstado}`;
        }
    }

    async function eliminarFilaSeguimiento(id) {
        const confirm = await Swal.fire({
            title: '¿Eliminar modelo del seguimiento?',
            text: 'El producto del catálogo general no será afectado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirm.isConfirmed) return;

        const res = await fetch(`{{ url('dashboard/laptops/seguimiento') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        if (res.ok) {
            document.getElementById(`fila-seg-${id}`)?.remove();
        }
    }
</script>