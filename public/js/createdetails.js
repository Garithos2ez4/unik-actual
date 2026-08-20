function sendDataToDeleteSpect(id,title){
    let titleModalDelete = document.getElementById('title-delete-spect');
    let inputModalDelete = document.getElementById('input-delete-spect');
    titleModalDelete.textContent = title;
    inputModalDelete.value = id;
}

document.addEventListener('DOMContentLoaded', function() {
    let btnAdd = document.getElementById('btnAddDetail');
    let select = document.getElementById('caracteristicaSelect');

    
    
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
            newInput.className = 'form-select';
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

    let selectedOptions = select.options[select.selectedIndex];
    selectedOptions.forEach('click',addDiv);
});