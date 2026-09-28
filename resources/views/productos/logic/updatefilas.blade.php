<script>
    function updateFilas(rackSelect) {
        let targetId = rackSelect.getAttribute('data-target');
        let filaSelect = document.getElementById(targetId);
        let selectedRack = rackSelect.value;

        let hasValidOption = false;
        Array.from(filaSelect.options).forEach(function(opt) {
            if (opt.value === "") return;
            if (opt.getAttribute('data-rack') === selectedRack) {
                opt.classList.remove('d-none');
                if (!hasValidOption) {
                    opt.selected = true; // Selecciona la primera fila válida
                    hasValidOption = true;
                }
            } else {
                opt.classList.add('d-none');
                opt.selected = false;
            }
        });

        if (!selectedRack) {
            filaSelect.value = "";
        }
    }
</script>