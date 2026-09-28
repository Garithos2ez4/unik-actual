<script>
    function syncML() {
        const btn = document.getElementById('btnSyncML');
        const dateInput = document.getElementById('sync_from_date');
        const fromDate = dateInput ? dateInput.value : '';
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sync...';

        fetch('{{ route("plataformas.ml.sync-orders") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    from_date: fromDate ? fromDate + 'T00:00:00.000-05:00' : null
                })
            })
            .then(r => r.json())
            .then(data => {
                pollSyncStatus();
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync';
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
