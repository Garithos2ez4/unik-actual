<script>
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: '¡Exito!',
        text: '{{ session('success') }}',
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
    });
    @endif

    @if($errors->any())
    Swal.fire({
        icon: 'error',
        title: 'Error al procesar',
        html: `{!! implode('<br>', $errors->all()) !!}`,
        confirmButtonColor: '#dc3545'
    });
    @endif
</script>
<script>
    function abrirModalLicenciaDefectuosa(actionUrl, ordenCompra, idProveedor, razonSocialProveedor) {
        const form = document.getElementById('formLicenciaDefectuosa');
        form.reset();
        form.action = actionUrl;

        form.querySelector('input[name="orden"]').value = ordenCompra ?? '';
        form.querySelector('input[name="razSocialProveedor"]').value = razonSocialProveedor ?? '';

        let hiddenIdField = form.querySelector('input[name="idProveedor"]');
        if (!hiddenIdField) {
            hiddenIdField = document.createElement('input');
            hiddenIdField.type = 'hidden';
            hiddenIdField.name = 'idProveedor';
            form.appendChild(hiddenIdField);
        }
        hiddenIdField.value = idProveedor ?? '';

        const modal = new bootstrap.Modal(document.getElementById('modalLicenciaDefectuosa'));
        modal.show();

        setTimeout(() => {
            form.querySelector('input[name="clave_key"]').focus();
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formLicenciaDefectuosa');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const claveKey = form.querySelector('input[name="clave_key"]').value.trim();
            const numeroTicket = form.querySelector('input[name="numero_ticket"]').value.trim();

            if (!claveKey) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo Requerido',
                    text: 'La Clave de Activación es obligatoria',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#e74c3c'
                });
                form.querySelector('input[name="clave_key"]').focus();
                return;
            }

            if (claveKey.length < 8) {
                Swal.fire({
                    icon: 'error',
                    title: 'Clave Inválida',
                    text: 'La clave debe tener al menos 8 caracteres',
                    confirmButtonText: 'Corregir',
                    confirmButtonColor: '#e74c3c'
                });
                form.querySelector('input[name="clave_key"]').focus();
                return;
            }
            if (!numeroTicket) {
                Swal.fire({
                    icon: 'question',
                    title: '¿Continuar sin Ticket?',
                    text: 'No se ha ingresado un número de ticket. ¿Desea continuar?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f39c12',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        confirmarAccion();
                    }
                });
                return;
            }
            confirmarAccion();
        });

        function confirmarAccion() {
            const claveKey = form.querySelector('input[name="clave_key"]').value.trim();

            Swal.fire({
                icon: 'warning',
                title: 'Confirmar Acción',
                html: `¿Está seguro de marcar la licencia <strong>${claveKey}</strong> como defectuosa?<br><br><small class="text-muted">Esta acción no se puede deshacer</small>`,
                showCancelButton: true,
                confirmButtonText: 'Sí, marcar como defectuosa',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Marcando licencia como defectuosa',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    form.submit();
                }
            });
        }
    });
    // ===============================
    // Variables globales
    // ===============================
    let licenciaPendiente = null;


    // ===============================
    // Abrir modal usar licencia
    // ===============================
    function abrirModalUsarLicencia(actionUrl, ordenCompra, modo = null) {

        const form = document.getElementById('formUsarLicencia');
        const hiddenModo = document.getElementById('modo_uso_hidden');

        if (!form || !hiddenModo) {
            console.error('No se encontró el formulario o el input hidden.');
            return;
        }

        form.reset();
        form.action = actionUrl;

        const ordenInput = form.querySelector('input[name="orden"]');
        if (ordenInput) {
            ordenInput.value = ordenCompra ?? '';
        }

        // 👉 UNIFUNCIONAL
        if (modo === null) {
            hiddenModo.value = 'COMPLETO';
        }
        // 👉 MULTIFUNCIONAL
        else {
            hiddenModo.value = modo;
        }

        const modalElement = document.getElementById('modalUsarLicencia');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }


    // ===============================
    // Flujo decisión multifuncional
    // ===============================
    function usarLicenciaDecision(data) {

        licenciaPendiente = data;

        if (!data.multifuncional) {
            abrirModalUsarLicencia(data.url, data.orden);
            return;
        }

        const modal = new bootstrap.Modal(
            document.getElementById('modalDecisionUso')
        );

        modal.show();
    }


    function confirmarUso(modo) {

        bootstrap.Modal
            .getInstance(document.getElementById('modalDecisionUso'))
            .hide();

        abrirModalUsarLicencia(
            licenciaPendiente.url,
            licenciaPendiente.orden,
            modo
        );
    }


    // ===============================
    // Submit del formulario
    // ===============================
    document.getElementById('formUsarLicencia')
        .addEventListener('submit', function(e) {

            e.preventDefault();

            const claveInput = document.getElementById('clave_key');
            const hiddenModo = document.getElementById('modo_uso_hidden');
            const claveValue = claveInput.value.trim();

            const btnGuardar = document.getElementById('btnGuardar');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const saveIcon = document.getElementById('saveIcon');
            const btnText = document.getElementById('btnText');

            if (claveValue === '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: 'La Clave de Activación es obligatoria.',
                    confirmButtonColor: '#667eea'
                }).then(() => claveInput.focus());
                return;
            }

            if (!hiddenModo.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tipo de uso requerido',
                    text: 'Debe seleccionar si el uso es parcial o completo.',
                    confirmButtonColor: '#667eea'
                });
                return;
            }

            Swal.fire({
                title: '¿Confirmar registro?',
                text: 'Se registrará esta licencia como usada',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (result.isConfirmed) {

                    btnGuardar.disabled = true;
                    loadingSpinner.classList.remove('d-none');
                    saveIcon.classList.add('d-none');
                    btnText.textContent = 'Guardando...';

                    this.submit();
                }
            });
        });


    // ===============================
    // Reset al cerrar modal
    // ===============================
    document.getElementById('modalUsarLicencia')
        .addEventListener('hidden.bs.modal', function() {

            const form = document.getElementById('formUsarLicencia');
            const btnGuardar = document.getElementById('btnGuardar');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const saveIcon = document.getElementById('saveIcon');
            const btnText = document.getElementById('btnText');

            form.reset();

            document.getElementById('modo_uso_hidden').value = '';

            btnGuardar.disabled = false;
            loadingSpinner.classList.add('d-none');
            saveIcon.classList.remove('d-none');
            btnText.textContent = 'Guardar Licencia';
        });


    // ===============================
    // Botones usar licencia
    // ===============================
    document.addEventListener('click', function(e) {

        const btn = e.target.closest('.btn-usar-licencia');
        if (!btn) return;

        const data = {
            url: btn.dataset.url,
            orden: btn.dataset.orden,
            multifuncional: btn.dataset.multifuncional == "1"
        };

        usarLicenciaDecision(data);
    });
</script>
