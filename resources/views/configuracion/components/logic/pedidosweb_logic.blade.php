<script>
    function updatePedidoWebEstado(id, selectElement) {
        const estado = selectElement.value;
        const originalClasses = selectElement.className;
        const originalValue = selectElement.getAttribute('data-original-value') || 'PENDIENTE';
        
        // Show saving state (optional, just disable for a moment)
        selectElement.disabled = true;

        fetch(`/configuracion/pedidos-web/${id}/estado`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    estado: estado
                })
            })
            .then(response => response.json())
            .then(data => {
                selectElement.disabled = false;
                
                if (data.success) {
                    // Update classes dynamically based on the new state
                    selectElement.className = 'form-select form-select-sm border-0 fw-bold bg-light';
                    if (estado === 'PENDIENTE') selectElement.classList.add('text-warning');
                    else if (estado === 'PAGADO') selectElement.classList.add('text-success');
                    else if (estado === 'DESPACHADO') selectElement.classList.add('text-primary');
                    else selectElement.classList.add('text-secondary');

                    // Actualizar el valor original al nuevo
                    selectElement.setAttribute('data-original-value', estado);

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message || 'Estado actualizado',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                    selectElement.className = originalClasses; // Revert
                    selectElement.value = originalValue; // Revert value
                }
            })
            .catch(error => {
                console.error('Error:', error);
                selectElement.disabled = false;
                Swal.fire('Error', 'No se pudo actualizar el estado del pedido', 'error');
                selectElement.className = originalClasses; // Revert
                selectElement.value = originalValue; // Revert value
            });
    }
</script>
