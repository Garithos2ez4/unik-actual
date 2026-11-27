function sendDataToDelete(grupo,spect,title){
    let tituloModal = document.getElementById('title-modal-deleteespecificacionxgrupo');
    let hiddenGrupo = document.getElementById('hidden-modal-deleteespecificacionxgrupo-grupo');
    let hiddenSpect = document.getElementById('hidden-modal-deleteespecificacionxgrupo-caracteristica');

    tituloModal.textContent = title;
    hiddenGrupo.value = grupo;
    hiddenSpect.value = spect;
}

function deleteDivGrupoSpect(grupo,spect){
    let divGrupoSpect = document.getElementById('div-groupSpect-'+grupo+'-'+spect);
    divGrupoSpect.remove();
}

function submitFormDeleteCar(){
    let formDeleteCar = document.getElementById('modal-delete-caracteristica');

    const formData = new FormData(formDeleteCar);
    deleteDivGrupoSpect(formData.get('grupo'),formData.get('caracteristica'));
    fetch('/configuracion/deletecaracteristicaxgrupo', {
        method: 'POST',
        body: formData  
    })
    .then(response => response.json())  
    .then(data => {
        alertBootstrap('Eliminación exitosa', 'success')
    })
    .catch(error => {
        alertBootstrap('Ocurrió un error en la solicitud', 'danger')
    });
}

function inputSearch(input){
    const query = input.value;
    const results = search(query);
    const resultsContainer = document.getElementById('modal-addespecificacionxgrupo-results');
    resultsContainer.innerHTML = '';

    if(query.length > 0){
        results.forEach(item => {
            const li = document.createElement('li');
            li.style.cursor = "pointer";
            li.classList.add('list-group-item', 'hover-sistema-uno');
            li.textContent = `${item.especificacion}`;

            li.addEventListener('click', function() {
                    document.getElementById('hidden-modal-addespecificacionxgrupo-especificacion').value = item.idCaracteristica; 
                    document.getElementById('text-modal-addespecificacionxgrupo').value = item.especificacion;
                    disableButtonSave();
                    resultsContainer.innerHTML = ''; 
                });
            resultsContainer.appendChild(li);
        });
    }else {
        resultsContainer.innerHTML = '';
    }
}

function search(query) {
    return dataSpects.filter(item => item.especificacion.toLowerCase().includes(query.toLowerCase())).slice(0, 5);
}

function sendGrupoToModal(id,title){
    let inputHidden = document.getElementById('hidden-modal-addespecificacionxgrupo-grupo');
    let spanTitle = document.getElementById('title-modal-addespecificacionxgrupo');
    let inputHiddeSpect = document.getElementById('hidden-modal-addespecificacionxgrupo-especificacion'); 
    let textSpect = document.getElementById('text-modal-addespecificacionxgrupo');
    inputHidden.value = '';
    inputHiddeSpect.value = '';
    textSpect.value = '';

    spanTitle.textContent = title;
    inputHidden.value = id;
}

function disableButtonSave(){
    let btnModalCaracteristicas = document.getElementById('btn-modal-addespecificacionxgrupo');
    let inputHiddenGrupo = document.getElementById('hidden-modal-addespecificacionxgrupo-grupo');
    let inputHiddenCar = document.getElementById('hidden-modal-addespecificacionxgrupo-especificacion');
    let disabled = false;

    if(inputHiddenGrupo.value == ''){
        disabled = true;
    }

    if(inputHiddenCar.value == ''){
        disabled = true;
    }

    btnModalCaracteristicas.disabled = disabled;
}

function submitFormAddCar(){
    let formAddCar = document.getElementById('modal-add-caracteristica');

    const formData = new FormData(formAddCar);
    for (let [key, value] of formData.entries()) {
        console.log(key, value);  // Imprime cada par (nombre, valor)
    }

    fetch('/configuracion/insertcaracteristicaxgrupo', {
        method: 'POST',
        body: formData  
    })
    .then(response => response.json())  
    .then(data => {
        addDivGrupoSpect(data);
        console.log(data);
        alertBootstrap('Registro exitoso', 'success')
    })
    .catch(error => {
        alertBootstrap('Ocurrió un error en la solicitud', 'danger')
    });
}

function addDivGrupoSpect(object){
    let referencia = document.getElementById('div-row-groupSpect-'+object.idGrupoProducto);
    console.log(object.idGrupoProducto);
    let divColCaracteristica = createDiv(['col-4','col-md-2','mb-1'],'div-groupSpect-'+object.idGrupoProducto+'-'+object.idCaracteristica);
    let divRowCaracteristica = createDiv(['row','ms-1','h-100','border','bg-light','rounded-2','truncate'],null);
    let smallContent = document.createElement('small');
    let linkSpect = createLink(['text-dark','link-danger'],
                                null,
                                ' <i class="bi bi-x-lg "></i>',
                                'javascript:void(0)',
                                [() => sendDataToDelete(object.idGrupoProducto,object.idCaracteristica,object.grupo_producto.nombreGrupo +'-'+object.caracteristicas.especificacion)]);
    let textNode = document.createTextNode(' ' + object.caracteristicas.especificacion);
                        
    smallContent.appendChild(linkSpect);
    smallContent.appendChild(textNode);
    divRowCaracteristica.appendChild(smallContent);    
    divColCaracteristica.appendChild(divRowCaracteristica);
    referencia.appendChild(divColCaracteristica);
}

document.getElementById('btn-modal-addespecificacionxgrupo').addEventListener('click',function(event){
    event.preventDefault();
    submitFormAddCar();
});