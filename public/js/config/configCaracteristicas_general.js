function sendDataToEdit(spect,tipo,title,sugerencias){
    let tituloModal = document.getElementById('title-modal-editespecificacion');
    let hiddenModal = document.getElementById('hidden-modal-editespecificacion');
    let tipoSelectModal = document.getElementById('tipo-modal-editespecificacion');
    let divFiltros = document.getElementById('filtros-modal-editespecificacion');

    tituloModal.textContent = title;
    hiddenModal.value = spect;
    tipoSelectModal.value = tipo;
    divFiltros.innerHTML = '';

    createSugerencias(sugerencias,divFiltros);
    if(tipo == 'FILTRO'){
        divFiltros.style.display = 'block';
    }else{
        divFiltros.style.display = 'none';
    }
}

function createSugerencias(object,container){
    let rowCab = createDiv(['row'],null);
    let divColLabel = createDiv(['col-6'],null);
    let divColBtnAdd = createDiv(['col-6','text-end'],null);
    
    let labelFiltros = document.createElement('label');
    labelFiltros.classList.add('form-label','mt-2');
    labelFiltros.textContent = 'Sugerencias:';
    divColLabel.appendChild(labelFiltros);

    let btnAddSugerencia = createButton(['btn','btn-success','btn-sm','mt-2'],null,'<i class="bi bi-plus-lg"></i>','button',null);
    btnAddSugerencia.addEventListener('click',function(){
        let newLiSugerencia = document.createElement('li');
        newLiSugerencia.classList.add('list-group-item','pt-0','pb-0','ps-0','pe-0');

        let newInputGroup = document.createElement('div');
        newInputGroup.classList.add('input-group');

        let newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.classList.add('form-control','border-0');
        newInput.name = 'createsugerencia[]';
        newInput.required = true;
        newInputGroup.appendChild(newInput);

        let btnRemoveInput = document.createElement('button');
        btnRemoveInput.classList.add('btn','btn-outline-danger');
        btnRemoveInput.innerHTML = '<i class="bi bi-x-lg"></i>';
        btnRemoveInput.type = 'button';
        btnRemoveInput.addEventListener('click',function(){
            newLiSugerencia.remove();
        });
        newInputGroup.appendChild(btnRemoveInput);

        newLiSugerencia.appendChild(newInputGroup);
        listaSugerencias.appendChild(newLiSugerencia);
    });
    divColBtnAdd.appendChild(btnAddSugerencia);

    let listaSugerencias = document.createElement('ul');
    listaSugerencias.classList.add('list-group','mt-2');

    if(object != null){
        Object.values(object).forEach((valor) => {
            let liSugerencia = document.createElement('li');
            liSugerencia.id = 'item-sugerencia-' + valor.idSugerencia;
            liSugerencia.classList.add('list-group-item','pt-0','pb-0','ps-0','pe-0');
            
            createItemSugerencia(liSugerencia,valor);
            
            listaSugerencias.appendChild(liSugerencia);
        });
    }
    
    rowCab.appendChild(divColLabel);
    rowCab.appendChild(divColBtnAdd);
    
    container.appendChild(rowCab);
    container.appendChild(listaSugerencias);
}

function createItemSugerencia(container,object){
    let inputGroup = document.createElement('div');
    inputGroup.classList.add('input-group');

    let inputSugerencia = document.createElement('input');
    inputSugerencia.type = 'text';
    inputSugerencia.classList.add('form-control','border-0');
    inputSugerencia.value = object.sugerencia;
    inputSugerencia.readOnly = object.estado == 0 ? true : false;
    inputSugerencia.name = 'updatesugerencia['+ object.idSugerencia +']';
    inputSugerencia.required = true;
    inputGroup.appendChild(inputSugerencia);

    let btnDeleteSugerencia = document.createElement('button');
    btnDeleteSugerencia.type = 'button';
    if(object.estado == 0){
        btnDeleteSugerencia.classList.add('btn','btn-outline-success');
        btnDeleteSugerencia.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
        btnDeleteSugerencia.addEventListener('click',function(){
            sendDataToRemoveSugerencia(object.idSugerencia,'RESTORE');
        });
    }

    if(object.estado == 1){
        btnDeleteSugerencia.classList.add('btn','btn-outline-danger');
        btnDeleteSugerencia.innerHTML = '<i class="bi bi-x-lg"></i>';
        btnDeleteSugerencia.addEventListener('click',function(){
            sendDataToRemoveSugerencia(object.idSugerencia,'DELETE');
        });
    }
    inputGroup.appendChild(btnDeleteSugerencia);
    
    container.appendChild(inputGroup);
}

function changeOperacionModal(valor){
    let inputOperacion = document.getElementById('operacion-modal-editespecificacion');

    inputOperacion.value = valor;
}

function changeTypeSugerencia(input){
    let divSugerencias = document.getElementById('filtros-modal-editespecificacion');
    if(input.value == 'FILTRO'){
        divSugerencias.style.display = 'block';    
    }else{
        divSugerencias.style.display = 'none';
    }
}

function changeTypeCreate(input){
    let divSugerencias = document.getElementById('filtros-modal-createespecificacion');
    if(input.value == 'FILTRO'){
        createSugerencias(null,divSugerencias);
    }else{
        divSugerencias.innerHTML = '';
    }
}

function sendDataToRemoveSugerencia(idSugerencia,tipo) {
    let formDelete = document.getElementById('form-delete-sugerencia');
    let inputIdSugerencia = document.getElementById('hidden-delete-sugerencia');
    let inputType = document.getElementById('hidden-delete-sugerencia-type');
    let confirmDelete = null;
    if(tipo == 'DELETE'){
        confirmDelete = confirm("¿Estás seguro de que quieres eliminar esta sugerencia?");
    }
    
    if(tipo == 'RESTORE'){
        confirmDelete = confirm("¿Quieres restaurar esta sugerencia?");
    }

    inputIdSugerencia.value = idSugerencia;
    inputType.value = tipo;
    const formData = new FormData(formDelete);

    if (confirmDelete) {
        fetch('/configuracion/removesugerencia', { 
            method: 'POST',
            body: formData
        })
        .then(response => { 
            if (response.ok) { 
                return response.json(); 
            } else { 
                throw new Error('Error al procesar la sugerencia'); 
            } 
        })
        .then(data => { 
            let itemSugerencia = document.getElementById('item-sugerencia-' + data.idSugerencia);
            itemSugerencia.innerHTML = '';
            createItemSugerencia(itemSugerencia,data);
            alertBootstrap('Sugerencia '+ data.sugerencia + ' ' + (tipo == 'DELETE' ? 'eliminada' : 'restaurada') +' exitosamente' , 'success');
        }) 
        .catch(error => {
            console.log('error: ' + error);
            alertBootstrap('error: ' + error, 'danger');
        });
    }
}
