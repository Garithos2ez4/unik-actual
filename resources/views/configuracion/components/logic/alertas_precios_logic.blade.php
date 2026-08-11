<script>
    function updateAlertaEstado(id, selectElement) {
        const estado = selectElement.value;
        const originalClasses = selectElement.className;
        // Obtenemos el valor original iterando por las opciones y buscando la que tenga 'selected' (o defaultValue)
        // Pero dado que esto cambia cada vez que el usuario elige, es mejor guardar el estado inicial como un data-attribute, o simplemente usar la opción por defecto.
        // Lo más seguro: guardar el estado anterior en el elemento, o sacarlo de las opciones.
        const originalValue = selectElement.getAttribute('data-original-value') || selectElement.querySelector('option[selected]')?.value || 'pendiente';
        
        fetch(`/configuracion/alerta/${id}/estado`, {
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
                if (data.success) {
                    // Update classes dynamically
                    selectElement.className = 'form-select form-select-sm border-0 fw-bold bg-light';
                    if (estado === 'pendiente') selectElement.classList.add('text-warning');
                    else if (estado === 'resuelto') selectElement.classList.add('text-success');
                    else if (estado === 'procesada') selectElement.classList.add('text-info');
                    else selectElement.classList.add('text-secondary');

                    // Actualizar el valor original al nuevo
                    selectElement.setAttribute('data-original-value', estado);

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Estado actualizado',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                    selectElement.className = originalClasses; // Revert
                    selectElement.value = originalValue; // Revert value
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo actualizar el estado', 'error');
                selectElement.className = originalClasses; // Revert
                selectElement.value = originalValue; // Revert value
            });
    }

    function ejecutarBotPrecios(btn) {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Iniciando...';
        btn.disabled = true;

        fetch(`/configuracion/alerta/ejecutar-bot`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;

                if (data.success) {
                    Swal.fire('Bot Iniciado', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                Swal.fire('Error', 'Hubo un error al comunicarse con el servidor', 'error');
            });
    }

    function guardarAlertaManual(btn) {
        const form = document.getElementById('formNuevaAlerta');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = {
            modelo: document.getElementById('alertaModelo').value,
            mi_precio: document.getElementById('alertaMiPrecio').value,
            competidor: document.getElementById('alertaCompetidor').value,
            precio_competidor: document.getElementById('alertaPrecioCompetidor').value,
            sugerencia: document.getElementById('alertaSugerencia').value
        };

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
        btn.disabled = true;

        fetch(`/configuracion/alerta/crear`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire('Éxito', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            Swal.fire('Error', 'Hubo un error al comunicarse con el servidor', 'error');
        });
    }
</script>
