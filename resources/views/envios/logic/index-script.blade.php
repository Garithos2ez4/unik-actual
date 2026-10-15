@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.check-despachado').forEach(check => {
            check.addEventListener('change', function() {
                const idEnvio = this.getAttribute('data-id');
                const isChecked = this.checked ? 1 : 0;
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch('{{ route("envios.toggle-despachado") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        idEnvioProvincia: idEnvio,
                        despachado: isChecked
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Éxito!', 'El estado de despacho ha sido actualizado.', 'success');
                    } else {
                        this.checked = !isChecked; // revert
                        Swal.fire('Error', data.message || 'No se pudo actualizar', 'error');
                    }
                })
                .catch(err => {
                    this.checked = !isChecked;
                    console.error(err);
                    Swal.fire('Error', 'Problema de conexión o de servidor', 'error');
                });
            });
        });
    });

    function accionSeleccionados(tipo) {
        let checkboxes = document.querySelectorAll('.envio-checkbox:checked');
        let seleccionados = Array.from(checkboxes).map(cb => cb.value);
        let baseUrl = '';

        if (tipo === 'pdf') baseUrl = '{{ route("envios.pdf") }}';
        if (tipo === 'lista-productos') baseUrl = '{{ route("envios.listaProductos") }}';
        if (tipo === 'etiquetas') baseUrl = '{{ route("envios.etiquetas") }}';
        if (tipo === 'excel') baseUrl = '{{ route("envios.excel") }}';

        if (seleccionados.length > 0) {
            window.open(baseUrl + '?ids=' + seleccionados.join(','), tipo === 'excel' ? '_self' : '_blank');
        } else {
            let fecha = document.getElementById('input_fecha').value;
            window.open(baseUrl + '?fecha=' + fecha, tipo === 'excel' ? '_self' : '_blank');
        }
    }

    function generarLinkCliente() {
        Swal.fire({
            title: 'Generar Link para Cliente',
            text: 'Se creará un formulario público que el cliente podrá llenar. El link expira en 20 minutos.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Generar Link',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#00b1b9'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("envios.generar-link") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        navigator.clipboard.writeText(data.link).then(() => {

                            // Banner de aviso si es feriado, domingo o después de las 5pm
                            const motivoTexto = data.motivo_postergacion
                                ? (data.motivo_postergacion.charAt(0).toUpperCase() + data.motivo_postergacion.slice(1))
                                : 'Día no hábil o fuera de horario de atención';

                            const avisoBanner = (data.tiene_postergacion || data.es_despues_5pm)
                                ? `<div class="alert alert-warning text-start py-2 mb-3" style="border-radius:8px;">
                                       <i class="bi bi-calendar-event-fill me-1"></i>
                                       <strong>⚠️ Aviso de fecha de envío:</strong> ${motivoTexto}.<br>
                                       El envío se registrará en la agencia el <strong>${data.fecha_registro_real}</strong>.
                                       El cliente verá este aviso también en su formulario.
                                   </div>`
                                : '';

                            Swal.fire({
                                icon: 'success',
                                title: '¡Link copiado!',
                                html: `${avisoBanner}
                                       <p class="mb-2">El link fue copiado al portapapeles.</p>
                                       <div class="input-group mt-3">
                                           <input type="text" class="form-control form-control-sm" value="${data.link}" readonly id="link-generado">
                                           <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('link-generado').value)">
                                               <i class="bi bi-clipboard"></i>
                                           </button>
                                       </div>
                                       <small class="text-muted d-block mt-2">Expira en ${data.expira_en}</small>`,
                                confirmButtonColor: '#00b1b9'
                            }).then(() => location.reload());
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function regenerarLink(idEnvio) {
        fetch(`/envios-provincias/regenerar-link/${idEnvio}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                navigator.clipboard.writeText(data.link).then(() => {
                    const motivoTexto = data.motivo_postergacion
                        ? (data.motivo_postergacion.charAt(0).toUpperCase() + data.motivo_postergacion.slice(1))
                        : 'Día no hábil o fuera de horario de atención';

                    const avisoBanner = (data.tiene_postergacion || data.es_despues_5pm)
                        ? `<div class="alert alert-warning text-start py-2 mb-3" style="border-radius:8px;">
                               <i class="bi bi-calendar-event-fill me-1"></i>
                               <strong>⚠️ Aviso de fecha de envío:</strong> ${motivoTexto}.<br>
                               El envío se registrará en la agencia el <strong>${data.fecha_registro_real}</strong>.
                           </div>`
                        : '';

                    Swal.fire({
                        icon: 'success',
                        title: '¡Nuevo link copiado!',
                        html: `${avisoBanner}
                               <div class="input-group mt-2">
                                   <input type="text" class="form-control form-control-sm" value="${data.link}" readonly id="link-regen">
                                   <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('link-regen').value)">
                                       <i class="bi bi-clipboard"></i>
                                   </button>
                               </div>
                               <small class="text-muted d-block mt-2">Nuevo timer: ${data.expira_en}</small>`,
                        confirmButtonColor: '#00b1b9'
                    });
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }
</script>
@endpush
