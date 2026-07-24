/**
 * create-licencias-gestionar-tipos.js
 * Lógica para gestionar (ocultar/mostrar) los tipos de licencia.
 */

const GET_ALL_TIPOS_URL = document.getElementById('getAllTiposUrl')?.value ?? '';
const TOGGLE_TIPO_URL   = document.getElementById('toggleTipoUrl')?.value ?? '';
const CSRF_TOKEN_GEST   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

let modalGestionarInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modalGestionarTipos');
    if (modalEl) {
        modalGestionarInstance = new bootstrap.Modal(modalEl);
        // Cargar datos cuando se abre el modal
        modalEl.addEventListener('show.bs.modal', function () {
            cargarTiposLicencia();
        });
        
        // Recargar la página al cerrar el modal para reflejar los cambios en el select
        modalEl.addEventListener('hidden.bs.modal', function () {
            window.location.reload();
        });
    }
});

function abrirModalGestionarTipos() {
    if (modalGestionarInstance) {
        modalGestionarInstance.show();
    }
}

async function cargarTiposLicencia() {
    const container = document.getElementById('listaTiposContainer');
    container.innerHTML = `<div class="text-center p-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Cargando tipos...</div>`;

    try {
        const res = await fetch(GET_ALL_TIPOS_URL);
        const tipos = await res.json();
        
        container.innerHTML = '';
        
        if (tipos.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-muted">No hay tipos de licencia registrados.</div>';
            return;
        }

        tipos.forEach(tipo => {
            const isChecked = tipo.estado == 1 ? 'checked' : '';
            const html = `
                <div class="tipo-item">
                    <span class="tipo-name ${tipo.estado == 0 ? 'text-muted text-decoration-line-through' : ''}">${tipo.nombre}</span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               id="switch-tipo-${tipo.id}" 
                               ${isChecked} 
                               onchange="toggleTipoEstado(${tipo.id}, this)">
                        <label class="form-check-label visually-hidden" for="switch-tipo-${tipo.id}">Activo</label>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });

    } catch (e) {
        container.innerHTML = '<div class="p-4 text-center text-danger"><i class="bi bi-exclamation-triangle"></i> Error al cargar los tipos.</div>';
    }
}

async function toggleTipoEstado(id, checkbox) {
    const estadoNuevo = checkbox.checked ? 1 : 0;
    checkbox.disabled = true; // deshabilitar mientras carga

    try {
        const res = await fetch(TOGGLE_TIPO_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN_GEST,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id: id, estado: estadoNuevo })
        });
        const data = await res.json();
        
        if (data.success) {
            // Actualizar estilo visual del texto
            const nameEl = checkbox.closest('.tipo-item').querySelector('.tipo-name');
            if (estadoNuevo === 1) {
                nameEl.classList.remove('text-muted', 'text-decoration-line-through');
            } else {
                nameEl.classList.add('text-muted', 'text-decoration-line-through');
            }
        } else {
            // Revertir si falla
            checkbox.checked = !estadoNuevo;
            Swal.fire({ icon: 'error', title: 'Error', text: data.message ?? 'No se pudo actualizar el estado.', timer: 2000 });
        }
    } catch (e) {
        checkbox.checked = !estadoNuevo;
        Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de conexión.', timer: 2000 });
    } finally {
        checkbox.disabled = false;
    }
}
