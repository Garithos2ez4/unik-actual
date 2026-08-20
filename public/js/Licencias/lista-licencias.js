function copiarPreClave(preClave) {
    const tempInput = document.createElement('input');
    tempInput.value = preClave;
    document.body.appendChild(tempInput);
    tempInput.select();

    try {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(preClave).then(() => {
                mostrarNotificacionCopiado(preClave);
            }).catch(() => {
                document.execCommand('copy');
                mostrarNotificacionCopiado(preClave);
            });
        } else {
            document.execCommand('copy');
            mostrarNotificacionCopiado(preClave);
        }
    } catch (err) {
        console.error('Error al copiar:', err);
        alert('No se pudo copiar la Pre-Clave');
    } finally {
        document.body.removeChild(tempInput);
    }
}

function mostrarNotificacionCopiado(preClave) {
    const existingNotification = document.querySelector('.copy-notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    const notification = document.createElement('div');
    notification.className = 'copy-notification';
    notification.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span><strong>Pre-Clave copiada:</strong> ${preClave}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
let searchDebounceTimer = null;

function debounceBuscarLicencias() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        buscarLicencias();
    }, 400);
}

function buscarLicencias() {
    const tipo = document.getElementById('filtro-tipo')?.value || document.getElementById('search-hidden-tipo')?.value || '';
    const searchInput = document.getElementById('input-search-licencias');
    const search = searchInput ? searchInput.value.trim() : '';
    const containerName = 'container-list-licencias';
    const clearBtn = document.getElementById('btn-clear-licencias');

    if (clearBtn) {
        if (search.length > 0) {
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
        }
    }

    const url = `${window.location.pathname}?tipo=${encodeURIComponent(tipo)}&search=${encodeURIComponent(search)}&container=${containerName}`;

    const container = document.getElementById(containerName);
    if (!container) return;

    container.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Buscando...</span>
            </div>
            <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">Buscando licencias...</p>
        </div>
    `;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            container.innerHTML = data.html;
        })
        .catch(err => {
            console.error('Error al buscar licencias:', err);
            container.innerHTML = `
                <div class="alert alert-danger text-center my-3">
                    Ocurrió un error al realizar la búsqueda. Por favor, reintente.
                </div>
            `;
        });
}

function limpiarBusqueda(e) {
    if (e) e.preventDefault();
    const searchInput = document.getElementById('input-search-licencias');
    if (searchInput) searchInput.value = '';
    const clearBtn = document.getElementById('btn-clear-licencias');
    if (clearBtn) clearBtn.classList.add('d-none');
    buscarLicencias();
}

function filtrarPorTipo() {
    const tipo = document.getElementById('filtro-tipo')?.value || '';
    const hiddenTipo = document.getElementById('search-hidden-tipo');
    if (hiddenTipo) hiddenTipo.value = tipo;
    buscarLicencias();
}
