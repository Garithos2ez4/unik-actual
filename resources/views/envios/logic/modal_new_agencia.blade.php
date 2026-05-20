<script>
function saveNewAgencia() {
    const nombre = document.getElementById('nombre_agencia').value;
    if (!nombre.trim()) {
        alert('Por favor ingrese el nombre de la agencia');
        return;
    }

    const formData = new FormData(document.getElementById('form-new-agencia'));

    fetch("{{ route('envios.agencia.store') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add to all agencia selects
            const selects = document.querySelectorAll('select[name="idAgencia"]');
            selects.forEach(select => {
                const option = document.createElement('option');
                option.value = data.agencia.idAgencia;
                option.textContent = data.agencia.nombre;
                option.selected = true;
                select.appendChild(option);
            });
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNewAgencia'));
            modal.hide();
            document.getElementById('form-new-agencia').reset();
        } else {
            alert('Error al guardar: ' + (data.message || 'Desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al intentar guardar la agencia.');
    });
}
</script>
