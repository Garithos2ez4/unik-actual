<script>
    function syncML() {
        const btn = document.getElementById('btnSyncML');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sincronizando...';

        fetch('{{ route("plataformas.ml.sync-orders") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(r => r.json())
            .then(data => {
                pollSyncStatus();
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sincronizar Órdenes';
            });
    }

    function pollSyncStatus() {
        const interval = setInterval(() => {
            fetch('{{ route("plataformas.ml.sync-status") }}')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'completed' || data.status === 'idle') {
                        clearInterval(interval);
                        document.getElementById('btnSyncML').disabled = false;
                        document.getElementById('btnSyncML').innerHTML = '<i class="bi bi-check-circle"></i> Listo';
                        setTimeout(() => location.reload(), 1500);
                    }
                });
        }, 3000);
    }
</script>
