/**
 * create-licencias-tipo.js
 * Lógica del modal "Nuevo Tipo de Licencia" en licencias/create
 */

const STORE_TIPO_URL = document.getElementById('storeNuevoTipoUrl')?.value ?? '';
const CSRF_TOKEN     = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function abrirModalNuevoTipo() {
    document.getElementById('nuevoTipoNombre').value = '';
    document.getElementById('nuevoTipoError').classList.add('d-none');
    const modal = new bootstrap.Modal(document.getElementById('modalNuevoTipo'));
    modal.show();
    setTimeout(() => document.getElementById('nuevoTipoNombre').focus(), 400);
}

async function guardarNuevoTipo() {
    const nombre  = document.getElementById('nuevoTipoNombre').value.trim();
    const errorEl = document.getElementById('nuevoTipoError');
    const btn     = document.getElementById('btnGuardarNuevoTipo');

    if (!nombre) {
        errorEl.textContent = 'El nombre es obligatorio.';
        errorEl.classList.remove('d-none');
        return;
    }

    btn.disabled    = true;
    btn.innerHTML   = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
    errorEl.classList.add('d-none');

    try {
        const res  = await fetch(STORE_TIPO_URL, {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  CSRF_TOKEN,
                'Accept':        'application/json'
            },
            body: JSON.stringify({ nombre })
        });

        const data = await res.json();

        if (data.success) {
            const select = document.getElementById('id_tipo');
            const option = new Option(data.nombre, data.id, true, true);
            select.add(option);
            bootstrap.Modal.getInstance(document.getElementById('modalNuevoTipo')).hide();
            Swal.fire({
                icon: 'success',
                title: '¡Listo!',
                text:  'Tipo de licencia creado.',
                timer: 1800,
                showConfirmButton: false
            });
        } else {
            errorEl.textContent = data.message ?? 'Error al guardar.';
            errorEl.classList.remove('d-none');
        }
    } catch (e) {
        errorEl.textContent = 'Error de conexión. Intenta de nuevo.';
        errorEl.classList.remove('d-none');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i>Guardar';
    }
}

// Inicializar listener en el botón una vez que el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnGuardarNuevoTipo');
    if (btn) {
        btn.addEventListener('click', guardarNuevoTipo);
    }
});
