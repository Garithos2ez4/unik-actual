<script>
function prepareSubAgenciaModal() {
    const selectAgencia = document.querySelector('select[name="idAgencia"]');
    const selectDestino = document.getElementById('select-destino');
    
    const idAgencia = selectAgencia ? selectAgencia.value : '';
    const idDestino = selectDestino ? selectDestino.value : '';
    
    if (!idAgencia || !idDestino) {
        alert('Por favor, primero seleccione una Agencia y un Destino (Distrito) en el formulario principal.');
        return false;
    }
    
    document.getElementById('idAgencia_subagencia').value = idAgencia;
    document.getElementById('idDestino_subagencia').value = idDestino;
    
    const nombreAgencia = selectAgencia.options[selectAgencia.selectedIndex].text.trim().toUpperCase();
    const nombreDestino = selectDestino.options[selectDestino.selectedIndex].text.trim().toUpperCase();

    document.getElementById('text-agencia-seleccionada').value = nombreAgencia;
    document.getElementById('text-destino-seleccionado').value = nombreDestino;

    // Auto-rellenar el nombre de la oficina con el formato AGENCIA+DISTRITO
    document.getElementById('nombre_subagencia').value = `${nombreAgencia} - ${nombreDestino}`;
    
    return true;
}

// Hook into bootstrap modal show event
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modalNewSubAgencia');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function(event) {
            const ok = prepareSubAgenciaModal();
            if (!ok) {
                event.preventDefault();
            }
        });
    }
});

function saveNewSubAgencia() {
    const formData = new FormData(document.getElementById('form-new-subagencia'));

    fetch("{{ route('envios.subagencia.store') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add option to select-subagencia and select it
            const select = document.getElementById('select-subagencia');
            if (select) {
                const option = document.createElement('option');
                option.value = data.subagencia.idSubAgencia;
                let nombre = data.subagencia.nombre_oficina || 'Sin Nombre';
                let direc = data.subagencia.direccion || 'Sin Dirección';
                option.textContent = `${nombre} - ${direc}`;
                option.selected = true;
                
                // Clear any "Primero elija..." option
                if (select.options.length === 1 && select.options[0].value === "") {
                    select.innerHTML = '<option value="">Seleccione oficina...</option>';
                }
                
                select.appendChild(option);
                select.disabled = false;
            }
            
            // Close modal
            const modalEl = document.getElementById('modalNewSubAgencia');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            document.getElementById('form-new-subagencia').reset();
        } else {
            alert('Error al guardar la sucursal: ' + (data.message || 'Desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al intentar guardar la oficina.');
    });
}
</script>
