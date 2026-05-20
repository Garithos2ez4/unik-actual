<script>
function saveNewDestino() {
    const idProvincia = document.getElementById('idProvincia_destino').value;
    const nombre = document.getElementById('nombre_destino').value;
    
    if (!idProvincia || !nombre.trim()) {
        alert('Por favor complete todos los campos');
        return;
    }

    const formData = new FormData(document.getElementById('form-new-destino'));

    fetch("{{ route('envios.destino.store') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add to all destino selects
            const selects = document.querySelectorAll('select[name="idDestino"]');
            selects.forEach(select => {
                const option = document.createElement('option');
                option.value = data.destino.idDestino;
                option.textContent = data.destino.nombre;
                option.selected = true;
                select.appendChild(option);
            });
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNewDestino'));
            modal.hide();
            document.getElementById('form-new-destino').reset();
        } else {
            alert('Error al guardar: ' + (data.message || 'Desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al intentar guardar the destino.');
    });
}
</script>
