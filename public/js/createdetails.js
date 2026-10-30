function sendDataToDeleteSpect(id,title){
    let titleModalDelete = document.getElementById('title-delete-spect');
    let inputModalDelete = document.getElementById('input-delete-spect');
    titleModalDelete.textContent = title;
    inputModalDelete.value = id;
}

document.addEventListener('DOMContentLoaded', function() {
    let btnAdd = document.getElementById('btnAddDetail');
    let select = document.getElementById('caracteristicaSelect');

    
    
    // Inicializar Tom Select en los campos existentes
    document.querySelectorAll('.tom-select-filtro').forEach(function(el) {
        new TomSelect(el, {
            create: true,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });

    // Función para actualizar el estado del botón
    function updateButtonState() {
        let selectedCar = select.options[select.selectedIndex];
        let carValue = selectedCar.value;
        
        if (carValue === 'none') {
            btnAdd.classList.add('disabled');
            btnAdd.disabled = true;
        } else {
            btnAdd.classList.remove('disabled');
            btnAdd.disabled = false;
        }
    }
    
    function addDiv() {
        var selectedOption = select.options[select.selectedIndex];
        var selectedValue = selectedOption.value;
        var selectedText = selectedOption.text;
        var selectedTipo = selectedOption.dataset.tipo;
        var selectedFiltros = JSON.parse(selectedOption.dataset.filtros);

        console.log(selectedFiltros);
        
        if (selectedValue === 'none') {
            return; // No hacer nada si se selecciona la opción predeterminada
        }
        
        let newDiv = document.createElement('div');
        let newSpan = document.createElement('span');
        let newInput = '';
        let newBtnDelete = document.createElement('button');
        
        newDiv.className = 'input-group mb-3';
        
        newSpan.className = 'input-group-text update-details label-car bg-sistema-light text-light';
        newSpan.textContent = selectedText;

        if(selectedTipo == 'FILTRO'){
            newInput = document.createElement('select');
            newInput.className = 'form-select tom-select-filtro';
            let defaultOption = document.createElement('option');
            defaultOption.textContent = '-Elige un valor-';
            defaultOption.value = '';
            newInput.appendChild(defaultOption);
            Object.values(selectedFiltros).forEach(valor => {
                let newOption = document.createElement('option');
                newOption.textContent = valor.sugerencia;
                newOption.value = valor.sugerencia;
                newInput.appendChild(newOption);
            });
            newInput.name = 'insertcaracteristicas[' + selectedValue + ']';
        }else{
            newInput = document.createElement('input');
            newInput.className = 'form-control';
            newInput.type = 'text';
            newInput.maxLength  = 100;
            newInput.placeholder = 'Característica';
            newInput.name = 'insertcaracteristicas[' + selectedValue + ']';
        }

        let infoTooltip = null;
        if(selectedText.toLowerCase().includes('peso')){
            infoTooltip = document.createElement('span');
            infoTooltip.className = 'input-group-text bg-info text-white';
            infoTooltip.innerHTML = '<i class="bi bi-info-circle"></i>';
            infoTooltip.setAttribute('data-bs-toggle', 'tooltip');
            infoTooltip.setAttribute('title', "Ejemplo: Para menos de 1 Kilo digita '0.75' o '750gr'. El sistema lo lee en KG.");
        }
        

        newBtnDelete.className = 'btn btn-outline-danger';
        newBtnDelete.type = 'button';
        newBtnDelete.innerHTML = '<i class="bi bi-trash"></i>';
        newBtnDelete.addEventListener('click',function(event){
            newDiv.remove();
            viewSelect(selectedOption);
        });
        
        newDiv.appendChild(newSpan);
        newDiv.appendChild(newInput);
        if (infoTooltip) {
            newDiv.appendChild(infoTooltip);
            // Re-initialize tooltips if bootstrap is available globally
            if (typeof bootstrap !== 'undefined') {
                new bootstrap.Tooltip(infoTooltip);
            }
        }
        newDiv.appendChild(newBtnDelete);
        document.getElementById('containerDivs').appendChild(newDiv);
        
        if(selectedTipo == 'FILTRO'){
            new TomSelect(newInput, {
                create: true,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        }
        
        hiddenSelect(selectedOption);
        
        select.value = 'none';
        
        updateButtonState();
        sizeLabel();
    }
    
    function hiddenSelect(option) {
        option.classList.add('hidden');
        option.style.display = 'none';
    }

    function viewSelect(option) {
        option.classList.remove('hidden');
        option.style.display = 'block';
    }
    
    function sizeLabel(){
        let lblCar = document.querySelectorAll('.label-car');
        lblCar.forEach(function(label) {
            if (window.innerWidth < 576) { 
                label.style.width = "40%";
            } else { 
                label.style.width = "15%";
            }
        });
    }

    // Inicializar el estado del botón al cargar
    updateButtonState();
    sizeLabel();

    // Agregar un evento para cuando se cambie la selección
    select.addEventListener('change', updateButtonState);
    
    // Agregar un evento para el botón
    btnAdd.addEventListener('click', addDiv);

    // Autollenar especificaciones desde la API de Deltron
    const btnAutollenar = document.getElementById('btnAutollenarSpecs');
    if (btnAutollenar) {
        btnAutollenar.addEventListener('click', async function () {
            const confirmResult = await Swal.fire({
                title: '¿Autollenar especificaciones?',
                text: 'Se consultará la ficha técnica en Deltron y se asignarán los valores a las características de este producto.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, autollenar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd'
            });

            if (!confirmResult.isConfirmed) return;

            const url = btnAutollenar.getAttribute('data-url');
            if (!url) return;

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="_token"]')?.value;

            const originalHtml = btnAutollenar.innerHTML;
            btnAutollenar.disabled = true;
            btnAutollenar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Consultando Deltron...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });

                const data = await response.json().catch(() => ({ success: false, message: 'Respuesta inválida del servidor.' }));

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Especificaciones importadas!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Aviso',
                        text: data.message || 'No se pudieron importar especificaciones.'
                    });
                    btnAutollenar.disabled = false;
                    btnAutollenar.innerHTML = originalHtml;
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo comunicar con el servidor para obtener los datos de Deltron.'
                });
                btnAutollenar.disabled = false;
                btnAutollenar.innerHTML = originalHtml;
            }
        });
    }
});