<script>
    window.APP_DATA = {
        tc: {{ $tc ?? '0' }},
        tasaFija: {{ $tasaFija ?? '0' }},
        isDeltronImport: {{ isset($deltronData) && $deltronData ? 'true' : 'false' }},
        defaultImage: "{{ asset('storage/1000x1000image.webp') }}",
        codigos: @json($codigos->mapWithKeys(function($cod) {
            return [$cod->codigoProducto => $cod->idGrupo];
        }))
    };
</script>
<script src="{{ asset('js/create-product-scripts.js') }}?v=1.07"></script>
<script>
    (function () {
        const inputSearch = document.getElementById('buscadorProductoFantasma');
        const listResults = document.getElementById('resultadosProductoFantasma');
        const hiddenInput = document.getElementById('replace_id_input');
        const titleEl = document.getElementById('replaceBannerTitle');
        const subEl = document.getElementById('replaceBannerSubtitle');
        const btnClear = document.getElementById('btnQuitarReemplazo');
        const btnSubmit = document.getElementById('btnRegistrar');
        let timer = null;

        function actualizarBotonSubmit() {
            if (!btnSubmit) return;
            if (hiddenInput && hiddenInput.value) {
                btnSubmit.innerHTML = `Reemplazar ID #${hiddenInput.value} <i class="bi bi-arrow-repeat"></i>`;
                btnSubmit.classList.replace('btn-success', 'btn-warning');
            } else {
                btnSubmit.innerHTML = `Registrar <i class="bi bi-floppy"></i>`;
                btnSubmit.classList.replace('btn-warning', 'btn-success');
            }
        }
        actualizarBotonSubmit();

        window.seleccionarProductoFantasma = function (id, codigo, nombre) {
            hiddenInput.value = id;
            titleEl.innerHTML = `Reemplazando Producto ID #${id} (${codigo})`;
            subEl.innerHTML = `Producto actual: <strong>${nombre}</strong> — Se sobrescribirá conservando el ID #${id}.`;
            btnClear.classList.remove('d-none');
            listResults.classList.add('d-none');
            inputSearch.value = '';
            actualizarBotonSubmit();
        };

        window.limpiarSeleccionReemplazo = function () {
            hiddenInput.value = '';
            titleEl.innerHTML = `¿Deseas reciclar el ID de un producto sin ingresos (Fantasma)? <span class="text-muted fw-normal">(Opcional)</span>`;
            subEl.innerHTML = `Si seleccionas un producto que nunca tuvo ingresos en almacén, se sobrescribirá conservando su IDPRODUCTO.`;
            btnClear.classList.add('d-none');
            actualizarBotonSubmit();
        };

        if (inputSearch) {
            inputSearch.addEventListener('input', function () {
                clearTimeout(timer);
                const q = this.value.trim();
                if (q.length < 2) {
                    listResults.classList.add('d-none');
                    return;
                }
                timer = setTimeout(async () => {
                    try {
                        const res = await fetch(`{{ route('searchmodelproduct') }}?solo_reemplazables=1&query=${encodeURIComponent(q)}`);
                        const items = await res.json();
                        listResults.innerHTML = '';
                        if (!Array.isArray(items) || items.length === 0) {
                            listResults.innerHTML = `<div class="list-group-item small text-muted">No se encontraron productos sin ingresos para "${q}"</div>`;
                        } else {
                            items.forEach(p => {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'list-group-item list-group-item-action py-1 px-2 small text-start';
                                btn.innerHTML = `<div class="fw-bold text-dark">ID #${p.idProducto} - ${p.codigoProducto}</div><div class="text-muted text-truncate">${p.nombreProducto}</div>`;
                                btn.onclick = () => window.seleccionarProductoFantasma(p.idProducto, p.codigoProducto, p.nombreProducto);
                                listResults.appendChild(btn);
                            });
                        }
                        listResults.classList.remove('d-none');
                    } catch (e) {
                        listResults.classList.add('d-none');
                    }
                }, 300);
            });

            document.addEventListener('click', function (e) {
                if (!inputSearch.contains(e.target) && !listResults.contains(e.target)) {
                    listResults.classList.add('d-none');
                }
            });
        }
    })();
</script>

