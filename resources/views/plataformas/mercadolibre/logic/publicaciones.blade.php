<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.flex-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const itemId = this.dataset.itemId;
                const isEnabled = this.checked;
                const originalState = !isEnabled;
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                this.disabled = true;

                fetch('{{ route("plataformas.ml.toggle-flex") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            item_id: itemId,
                            enable: isEnabled
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.disabled = false;
                        if (data.success) {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(data.message || 'Estado actualizado.');
                            }
                        } else {
                            this.checked = originalState;
                            if (typeof toastr !== 'undefined') {
                                toastr.error(data.message || 'Error al actualizar.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.disabled = false;
                        this.checked = originalState;
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Ocurrió un error en la solicitud.');
                        }
                    });
            });
        });
    });
</script>
