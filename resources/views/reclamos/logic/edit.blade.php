<script>
    // Guardar Seguimiento vía AJAX
    function saveSeguimiento() {
        const form = document.getElementById('formSeguimiento');

        // 1. Fuerza al navegador a validar los campos requeridos
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        fetch('{{ route("reclamos.seguimiento.add", $reclamo->idReclamoPlataforma) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json' // Clave para que Laravel responda en JSON si hay error
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    // 2. Aquí te mostrará EXACTAMENTE por qué falló Laravel
                    alert('Error del Servidor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                alert('Error crítico de red. Revisa la consola F12.');
            });
    }

    // Guardar Diagnóstico vía AJAX
    function saveDiagnostico() {
        if (!document.getElementById('input-id-registro').value) {
            alert('Debe vincular un producto mediante el serial');
            return;
        }
        const formData = new FormData(document.getElementById('formDiagnostico'));
        fetch('{{ route("reclamos.diagnostico.add", $reclamo->idReclamoPlataforma) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert('Error al guardar diagnóstico');
        });
    }

    // Buscador de Seriales (Basado en createGarantia.js logic)
    const inputSerial = document.getElementById('input-serial-search');
    const suggestionBox = document.getElementById('suggestion-registro');
    const inputIdReg = document.getElementById('input-id-registro');
    const productInfo = document.getElementById('product-info');

    inputSerial.addEventListener('input', function() {
        if (this.value.length > 2) {
            fetch(`/egresos/searchregistro?query=${this.value}`)
                .then(r => r.json())
                .then(data => {
                    suggestionBox.innerHTML = '';
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action small';
                        li.innerHTML = `<strong>${item.nombreProducto}</strong><br><span class="text-muted">${item.numeroSerie}</span>`;
                        li.onclick = () => {
                            inputIdReg.value = item.idRegistroProducto;
                            inputSerial.value = item.numeroSerie;
                            document.getElementById('product-name').textContent = item.nombreProducto;
                            document.getElementById('product-serial').textContent = item.numeroSerie;
                            productInfo.classList.remove('d-none');
                            suggestionBox.innerHTML = '';
                        };
                        suggestionBox.appendChild(li);
                    });
                });
        } else {
            suggestionBox.innerHTML = '';
        }
    });
</script>
