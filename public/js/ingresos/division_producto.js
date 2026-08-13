/**
 * division_producto.js
 * Lógica de División y Unión libre de Packs de productos.
 * Se encarga de: abrir modales, llamadas AJAX, validaciones y submit.
 * La vista (ingresos.blade.php) solo contiene el HTML/renderizado.
 */

// =======================================================================
// MÓDULO 1: División de Pack por serie
// =======================================================================

/**
 * Abre el modal de división precargando los datos del registro seleccionado.
 * @param {number|string} idRegistro
 * @param {string} nombreProducto
 * @param {string} serie
 */
function abrirModalDivision(idRegistro, nombreProducto, serie) {
    document.getElementById('dividir-pack-idregistro').value     = idRegistro;
    document.getElementById('dividir-pack-producto').textContent  = nombreProducto;
    document.getElementById('dividir-pack-serie').textContent     = serie;

    const componentesDiv = document.getElementById('dividir-pack-componentes');
    componentesDiv.innerHTML = '<p class="text-secondary"><i class="bi bi-hourglass-split"></i> Cargando componentes...</p>';

    fetch(`/ingresos/verificar-pack?idRegistro=${idRegistro}`)
        .then(r => r.json())
        .then(data => {
            if (data && data.componentes) {
                let html = '<h6 class="fw-bold">Componentes que se crearán:</h6><ul class="list-group">';
                data.componentes.forEach(c => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> ${c.nombreProducto}</span>
                        <span class="badge bg-success rounded-pill">x${c.cantidad}</span>
                    </li>`;
                });
                html += '</ul>';
                componentesDiv.innerHTML = html;
            } else {
                componentesDiv.innerHTML = '<div class="alert alert-warning">Este producto no tiene componentes de pack configurados.</div>';
                document.getElementById('btn-confirmar-division').disabled = true;
            }
        })
        .catch(() => {
            componentesDiv.innerHTML = '<div class="alert alert-danger">Error al cargar componentes.</div>';
        });

    const modalElement = document.getElementById('dividirPackModal');
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    document.getElementById('btn-confirmar-division').disabled = false;
    modal.show();
}

// Delegación de eventos: botón "Dividir" en cada fila de la tabla
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-dividir-pack');
    if (!btn) return;
    e.preventDefault();
    abrirModalDivision(btn.dataset.idregistro, btn.dataset.nombreproducto, btn.dataset.serie);
});

// Búsqueda de Pack por serie (autocomplete + botón buscar)
(function iniciarBusquedaPorSerie() {
    const btnBuscar   = document.getElementById('btn-ejecutar-busqueda-pack');
    const inputSerie  = document.getElementById('input-buscar-serie-pack');
    const suggestions = document.getElementById('suggestions-serie-pack');

    if (!inputSerie) return;

    let timeout;

    inputSerie.addEventListener('input', function () {
        clearTimeout(timeout);
        const query = this.value.trim();

        if (query.length < 3) { suggestions.style.display = 'none'; return; }

        timeout = setTimeout(() => {
            fetch(`/ingresos/buscar-series-pack-ajax?query=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    suggestions.innerHTML = '';
                    if (data.length === 0) {
                        suggestions.innerHTML = '<div class="list-group-item text-muted">No se encontraron series válidas para dividir</div>';
                    } else {
                        data.forEach(item => {
                            const div     = document.createElement('a');
                            div.href      = '#';
                            div.className = 'list-group-item list-group-item-action py-1';
                            div.innerHTML = `<strong>${item.serie}</strong><br><small class="text-muted" style="font-size:0.75rem;">${item.nombreProducto}</small>`;
                            div.addEventListener('click', function (e) {
                                e.preventDefault();
                                inputSerie.value          = item.serie;
                                suggestions.style.display = 'none';
                                btnBuscar.click();
                            });
                            suggestions.appendChild(div);
                        });
                    }
                    suggestions.style.display = 'block';
                })
                .catch(() => { suggestions.style.display = 'none'; });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (e.target.id !== 'input-buscar-serie-pack' && suggestions) {
            suggestions.style.display = 'none';
        }
    });

    if (!btnBuscar) return;

    btnBuscar.addEventListener('click', function () {
        const serie = inputSerie.value.trim();

        if (!serie) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Ingresa una serie', showConfirmButton: false, timer: 1500 });
            return;
        }

        btnBuscar.disabled  = true;
        btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Buscando...';

        fetch(`/ingresos/buscar-pack-por-serie?serie=${encodeURIComponent(serie)}`)
            .then(r => r.json())
            .then(data => {
                btnBuscar.disabled  = false;
                btnBuscar.innerHTML = 'Buscar y Dividir';

                if (data.success) {
                    const buscarModalEl = document.getElementById('buscarPackModal');
                    const buscarModal   = bootstrap.Modal.getInstance(buscarModalEl);
                    if (buscarModal) buscarModal.hide();
                    abrirModalDivision(data.data.idRegistro, data.data.nombreProducto, data.data.serie);
                    inputSerie.value = '';
                } else {
                    Swal.fire('No se puede dividir', data.message, 'error');
                }
            })
            .catch(() => {
                btnBuscar.disabled  = false;
                btnBuscar.innerHTML = 'Buscar y Dividir';
                Swal.fire('Error', 'Ocurrió un error al buscar la serie', 'error');
            });
    });

    inputSerie.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); btnBuscar.click(); }
    });
})();

// Confirmación SweetAlert antes de enviar el form de división
(function iniciarFormDivision() {
    const formDividir = document.getElementById('form-dividir-pack');
    if (!formDividir) return;

    formDividir.addEventListener('submit', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Se dividirá el pack en sus componentes individuales. Esta acción no se puede deshacer fácilmente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6f42c1',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-scissors"></i> Sí, dividir',
            cancelButtonText: 'Cancelar',
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
})();


// =======================================================================
// MÓDULO 2: Unión libre de componentes en Pack (wizard 3 pasos)
// =======================================================================

(function iniciarWizardUnion() {

    const modalEl  = document.getElementById('unirPackModal');
    const btnAbrir = document.getElementById('btn-abrir-union-pack');

    // Si no hay permiso el modal/botón no existen en el DOM
    if (!btnAbrir || !modalEl) return;

    const bsModal = new bootstrap.Modal(modalEl);

    // ── Elementos del DOM ──────────────────────────────────────────────
    const step1         = document.getElementById('union-step-1');
    const step2         = document.getElementById('union-step-2');
    const step3         = document.getElementById('union-step-3');
    const loadingPacks  = document.getElementById('union-loading-packs');
    const listaPacks    = document.getElementById('union-lista-packs');
    const noPacks       = document.getElementById('union-no-packs');
    const nombreSelec   = document.getElementById('union-nombre-pack-seleccionado');
    const compContainer = document.getElementById('union-componentes-container');
    const resumen       = document.getElementById('union-resumen');
    const btnSig2       = document.getElementById('btn-union-siguiente-paso2');
    const btnSig3       = document.getElementById('btn-union-siguiente-paso3');
    const btnConf       = document.getElementById('btn-union-confirmar');
    const btnVolver1    = document.getElementById('btn-union-volver-paso1');
    const btnVolver2    = document.getElementById('btn-union-volver-paso2');
    const btnAutoCompletar = document.getElementById('btn-union-autocompletar');
    const badge1        = document.getElementById('step-badge-1');
    const badge2        = document.getElementById('step-badge-2');
    const badge3        = document.getElementById('step-badge-3');

    // ── Estado del wizard ──────────────────────────────────────────────
    let wizardData = resetWizard();

    function resetWizard() {
        return {
            idProductoPack:        null,
            nombrePack:            null,
            modeloPack:            null,
            componentesRequeridos: [],
        };
    }

    // ── Navegación entre pasos ─────────────────────────────────────────
    function irAPaso(n) {
        [step1, step2, step3].forEach(s => s.classList.add('d-none'));
        [btnSig2, btnSig3, btnConf].forEach(b => b.classList.add('d-none'));
        [badge1, badge2, badge3].forEach(b => {
            b.classList.remove('bg-success');
            b.classList.add('bg-secondary');
        });

        const mapPaso  = { 1: step1,  2: step2,  3: step3  };
        const mapBadge = { 1: badge1, 2: badge2, 3: badge3 };
        const mapBtn   = { 1: btnSig2, 2: btnSig3, 3: btnConf };

        for (let i = 1; i <= n; i++) {
            mapBadge[i].classList.replace('bg-secondary', 'bg-success');
        }
        mapPaso[n].classList.remove('d-none');
        mapBtn[n].classList.remove('d-none');
    }

    // ── PASO 1: Abrir y cargar buscador de packs ───────────────────────
    const inputBuscarPadre = document.getElementById('input-buscar-padre-pack');
    const suggestionsPadre = document.getElementById('suggestions-padre-pack');
    const errorPadre       = document.getElementById('union-padre-error');
    let timeoutBuscarPadre = null;

    btnAbrir.addEventListener('click', function () {
        wizardData = resetWizard();
        inputBuscarPadre.value = '';
        suggestionsPadre.style.display = 'none';
        errorPadre.classList.add('d-none');
        irAPaso(1);
        bsModal.show();
    });

    inputBuscarPadre.addEventListener('input', function () {
        const query = this.value.trim();
        errorPadre.classList.add('d-none');
        
        if (query.length < 2) {
            suggestionsPadre.style.display = 'none';
            return;
        }

        clearTimeout(timeoutBuscarPadre);
        timeoutBuscarPadre = setTimeout(() => {
            fetch(`/ingresos/buscar-padres-pack-ajax?query=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(productos => {
                    suggestionsPadre.innerHTML = '';
                    if (!productos.length) {
                        suggestionsPadre.innerHTML = '<div class="list-group-item text-muted">No se encontraron productos compatibles.</div>';
                    } else {
                        productos.forEach(prod => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action text-start';
                            btn.innerHTML = `<strong>${prod.nombreProducto}</strong> <br><small class="text-muted">Modelo: ${prod.modelo || '-'} | Código: ${prod.codigo || '-'}</small>`;
                            btn.addEventListener('click', () => {
                                seleccionarPack(prod);
                                inputBuscarPadre.value = prod.nombreProducto;
                                suggestionsPadre.style.display = 'none';
                            });
                            suggestionsPadre.appendChild(btn);
                        });
                    }
                    suggestionsPadre.style.display = 'block';
                })
                .catch(() => {
                    suggestionsPadre.innerHTML = '<div class="list-group-item text-danger">Error en la búsqueda.</div>';
                    suggestionsPadre.style.display = 'block';
                });
        }, 300);
    });

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function (e) {
        if (!inputBuscarPadre.contains(e.target) && !suggestionsPadre.contains(e.target)) {
            suggestionsPadre.style.display = 'none';
        }
    });

    function seleccionarPack(pack) {
        wizardData.idProductoPack = pack.idProducto;
        wizardData.nombrePack     = pack.nombreProducto;
        wizardData.modeloPack     = pack.modelo || '';
        errorPadre.classList.add('d-none');
    }


    // ── PASO 2: Componentes requeridos ─────────────────────────────────
    btnSig2.addEventListener('click', function () {
        if (!wizardData.idProductoPack) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Selecciona un pack primero', showConfirmButton: false, timer: 1800 });
            return;
        }

        nombreSelec.textContent = wizardData.nombrePack + (wizardData.modeloPack ? ` (${wizardData.modeloPack})` : '');
        compContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-success" role="status"></div></div>';
        
        // Mantener oculto el error inicialmente
        errorPadre.classList.add('d-none');

        fetch(`/ingresos/componentes-union?idProductoPack=${wizardData.idProductoPack}`)
            .then(r => r.json())
            .then(componentes => {
                if (!componentes || !componentes.length) {
                    // Si no trae componentes, no está configurado en la BD
                    errorPadre.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Este producto no tiene componentes configurados en la base de datos (Tabla ProductoPack).';
                    errorPadre.classList.remove('d-none');
                    compContainer.innerHTML = '';
                    return;
                }

                // Verificar si hay stock suficiente de los HIJOS para armar al menos 1 pack
                const componentesFaltantes = componentes.filter(c => c.disponibles.length < c.cantidadNecesaria);
                if (componentesFaltantes.length > 0) {
                    const nombres = componentesFaltantes.map(c => `<strong>${c.nombreProducto}</strong> (Faltan ${c.cantidadNecesaria - c.disponibles.length})`).join('<br>');
                    errorPadre.innerHTML = `<i class="bi bi-exclamation-triangle"></i> No hay stock suficiente de los siguientes componentes para armar este pack:<br>${nombres}`;
                    errorPadre.classList.remove('d-none');
                    compContainer.innerHTML = '';
                    return;
                }

                irAPaso(2);
                wizardData.componentesRequeridos = componentes.map(c => ({
                    ...c, slots: Array(c.cantidadNecesaria).fill(null),
                }));
                renderizarComponentes();
            })
            .catch(() => {
                compContainer.innerHTML = '<div class="alert alert-danger">Error al cargar componentes.</div>';
            });
    });

    function renderizarComponentes() {
        compContainer.innerHTML = '';
        wizardData.componentesRequeridos.forEach((comp, ci) => {
            for (let slot = 0; slot < comp.cantidadNecesaria; slot++) {
                const card     = document.createElement('div');
                card.className = 'card mb-2';
                card.innerHTML = renderCardComponente(comp, ci, slot);
                compContainer.appendChild(card);
            }
        });
    }

    function renderCardComponente(comp, ci, slot) {
        const modeloBadge = comp.modelo
            ? `<span class="badge bg-info text-dark ms-1">${comp.modelo}</span>` : '';
        const slotBadge = comp.cantidadNecesaria > 1
            ? `<span class="badge bg-secondary">Unidad ${slot + 1} de ${comp.cantidadNecesaria}</span>` : '';
        const options = comp.disponibles.map(d => {
            const yaUsado = wizardData.componentesRequeridos[ci].slots.includes(d.idRegistro)
                         && wizardData.componentesRequeridos[ci].slots[slot] !== d.idRegistro;
            return `<option value="${d.idRegistro}" ${yaUsado ? 'disabled' : ''}>
                        Serie: ${d.numeroSerie} | ${d.almacen} | S/ ${parseFloat(d.costo).toFixed(2)}
                    </option>`;
        }).join('');
        return `<div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold">${comp.nombreProducto}${modeloBadge}</span>
                        ${slotBadge}
                    </div>
                    <select class="form-select form-select-sm"
                            data-comp="${ci}" data-slot="${slot}"
                            onchange="actualizarSlotUnion(this)">
                        <option value="">— Selecciona una unidad del stock —</option>
                        ${options}
                    </select>
                </div>`;
    }

    // Expuesta globalmente (llamada desde onchange generado dinámicamente)
    window.actualizarSlotUnion = function (sel) {
        const ci   = parseInt(sel.dataset.comp);
        const slot = parseInt(sel.dataset.slot);
        wizardData.componentesRequeridos[ci].slots[slot] = sel.value ? parseInt(sel.value) : null;

        const scrollTop = compContainer.scrollTop;
        renderizarComponentes();
        compContainer.querySelectorAll('select').forEach(s => {
            const c  = parseInt(s.dataset.comp);
            const sl = parseInt(s.dataset.slot);
            const v  = wizardData.componentesRequeridos[c].slots[sl];
            if (v) s.value = v;
        });
        compContainer.scrollTop = scrollTop;
    };

    // Auto-completar todos los slots
    if (btnAutoCompletar) {
        btnAutoCompletar.addEventListener('click', function () {
            let changes = 0;
            wizardData.componentesRequeridos.forEach((comp, ci) => {
                let libres = comp.disponibles.filter(d => !comp.slots.includes(d.idRegistro));
                for (let slot = 0; slot < comp.cantidadNecesaria; slot++) {
                    if (!comp.slots[slot] && libres.length > 0) {
                        const escogido = libres.shift();
                        comp.slots[slot] = escogido.idRegistro;
                        changes++;
                    }
                }
            });

            if (changes > 0) {
                const scrollTop = compContainer.scrollTop;
                renderizarComponentes();
                compContainer.querySelectorAll('select').forEach(s => {
                    const c  = parseInt(s.dataset.comp);
                    const sl = parseInt(s.dataset.slot);
                    const v  = wizardData.componentesRequeridos[c].slots[sl];
                    if (v) s.value = v;
                });
                compContainer.scrollTop = scrollTop;
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Componentes autocompletados', showConfirmButton: false, timer: 1500 });
            } else {
                Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Todos los slots ya están asignados o no hay stock', showConfirmButton: false, timer: 1500 });
            }
        });
    }

    // ── PASO 3: Validar, resumir, confirmar ────────────────────────────
    btnSig3.addEventListener('click', function () {
        let todosCompletos = true;
        let todosUnicos    = true;
        const idsUsados    = new Set();

        wizardData.componentesRequeridos.forEach(comp => {
            comp.slots.forEach(idReg => {
                if (!idReg)               { todosCompletos = false; return; }
                if (idsUsados.has(idReg)) { todosUnicos   = false; }
                idsUsados.add(idReg);
            });
        });

        if (!todosCompletos) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Asigna una unidad a cada componente', showConfirmButton: false, timer: 2000 });
            return;
        }
        if (!todosUnicos) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'No puedes usar el mismo registro dos veces', showConfirmButton: false, timer: 2000 });
            return;
        }

        resumen.innerHTML = construirResumen();
        irAPaso(3);
    });

    function construirResumen() {
        const modeloBadge = wizardData.modeloPack
            ? ` <span class="badge bg-info text-dark">${wizardData.modeloPack}</span>` : '';
        let html = `<div class="alert alert-success py-2"><strong>Pack a armar:</strong> ${wizardData.nombrePack}${modeloBadge}</div>
                    <ul class="list-group">`;
        wizardData.componentesRequeridos.forEach(comp => {
            comp.slots.forEach(idReg => {
                const disp   = comp.disponibles.find(d => d.idRegistro === idReg);
                const modelo = comp.modelo ? `<em class="text-secondary">${comp.modelo}</em>` : '';
                html += `<li class="list-group-item py-1 d-flex justify-content-between">
                            <span>${comp.nombreProducto} ${modelo}</span>
                            <small class="text-muted">Serie: ${disp?.numeroSerie ?? '?'} | ${disp?.almacen ?? ''}</small>
                         </li>`;
            });
        });
        html += '</ul>';
        return html;
    }

    btnVolver1.addEventListener('click', () => irAPaso(1));
    btnVolver2.addEventListener('click', () => irAPaso(2));

    btnConf.addEventListener('click', function () {
        Swal.fire({
            title: '¿Confirmar unión?',
            text: `Se creará un nuevo registro de "${wizardData.nombrePack}" y los componentes seleccionados quedarán como REUNIDOS.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-boxes"></i> Sí, armar pack',
            cancelButtonText: 'Cancelar',
        }).then(result => { if (result.isConfirmed) enviarFormulario(); });
    });

    function enviarFormulario() {
        const form = document.getElementById('form-unir-pack');
        document.getElementById('union-input-idpack').value    = wizardData.idProductoPack;
        document.getElementById('union-input-idalmacen').value = document.getElementById('union-almacen-destino').value;

        const hiddenDiv     = document.getElementById('union-hidden-registros');
        hiddenDiv.innerHTML = '';
        wizardData.componentesRequeridos.forEach(comp => {
            comp.slots.forEach(idReg => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'idRegistrosHijos[]';
                inp.value = idReg;
                hiddenDiv.appendChild(inp);
            });
        });

        form.submit();
    }

    // Reset completo al cerrar el modal
    modalEl.addEventListener('hidden.bs.modal', function () {
        wizardData              = resetWizard();
        listaPacks.innerHTML    = '';
        compContainer.innerHTML = '';
        resumen.innerHTML       = '';
        noPacks.classList.add('d-none');
        loadingPacks.classList.remove('d-none');
        irAPaso(1);
    });

})();

