<script>
function saveNewProvincia() {
    const nombre = document.getElementById('nombre_provincia').value;
    if (!nombre.trim()) {
        alert('Por favor ingrese el nombre de la provincia');
        return;
    }

    const formData = new FormData(document.getElementById('form-new-provincia'));

    fetch("{{ route('envios.provincia.store') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add to the provincia select in the destino modal
            const selects = document.querySelectorAll('select[name="idProvincia"]');
            selects.forEach(select => {
                const option = document.createElement('option');
                option.value = data.provincia.idProvincia;
                option.textContent = data.provincia.nombre;
                option.selected = true;
                select.appendChild(option);
            });
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNewProvincia'));
            modal.hide();
            document.getElementById('form-new-provincia').reset();
            
            // Ensure the parent modal (Destino) is still open and scrolled properly if needed
            document.body.classList.add('modal-open');
        } else {
            alert('Error al guardar: ' + (data.message || 'Desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al intentar guardar la provincia.');
    });
}
</script>
