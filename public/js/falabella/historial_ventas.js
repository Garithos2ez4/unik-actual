document.addEventListener('DOMContentLoaded', function() {
    // Lógica para el filtro de búsqueda de Historial de Ventas
    const inputBusqueda = document.getElementById('buscarHistorialFalabella');
    const tabla = document.getElementById('tablaFalabella');
    
    if (inputBusqueda && tabla) {
        inputBusqueda.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            const filas = tabla.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                // Ignorar la fila de "No hay ventas" si es que existe
                if (fila.cells.length === 1 && fila.cells[0].colSpan > 1) return;
                
                const orden = fila.cells[0] ? fila.cells[0].textContent.toLowerCase() : '';
                const modelo = fila.cells[3] ? fila.cells[3].textContent.toLowerCase() : '';
                
                if (orden.includes(term) || modelo.includes(term)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
    }

    // Lógica para el botón de colapsar la tabla de Historial de Ventas
    const collapseElement = document.getElementById('collapseHistorial');
    const btnIcon = document.querySelector('#btnToggleHistorial i');
    
    if (collapseElement && btnIcon) {
        collapseElement.addEventListener('show.bs.collapse', function () {
            btnIcon.classList.remove('bi-chevron-down');
            btnIcon.classList.add('bi-chevron-up');
        });
        collapseElement.addEventListener('hide.bs.collapse', function () {
            btnIcon.classList.remove('bi-chevron-up');
            btnIcon.classList.add('bi-chevron-down');
        });
    }
});
