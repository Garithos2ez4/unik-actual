let suggestionCliente = document.getElementById('suggestion-cliente');
let suggestionRegistro = document.getElementById('suggestion-registro');
let backgroundSuggestion = document.getElementById('hidden-body');
let inputSearchCliente = document.getElementById('input-garantia-cliente');
let inputSearchRegistro = document.getElementById('input-producto-serial');
let divClienteInput = document.getElementById('div-input-group-cliente');
let divRegistroInput = document.getElementById('div-input-group-registro');
let btnRegistroGarantia = document.getElementById('btn-registrar-garantia');

    function searchCliente(query){
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/cliente/searchcliente?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                data = JSON.parse(xhr.responseText);
                divClienteInput.style.zIndex = 1000;
                suggestionCliente.innerHTML = '';
                data.forEach(function(x){
                    console.log(x);
                    createSuggestionsCliente(x);
                });
                
            }
        };
        xhr.send();
    }

    function searchRegistro(query){
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/garantia/searchregistro?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                data = JSON.parse(xhr.responseText);
                suggestionRegistro.innerHTML = '';
                divRegistroInput.style.zIndex = 1000;
                data.forEach(function(x){
                    console.log(x);
                    createSuggestionsRegistro(x);
                });
                
            }
        };
        xhr.send();
    }

    function getOneRegistro(query) {
        let data = null;
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/garantia/getoneegreso?query=${query}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                data = JSON.parse(xhr.responseText);
                viewRegistroForm(data,query);
            }
        };
        xhr.send();
        return data;
    }

    function createSuggestionsRegistro(object){
        backgroundSuggestion.style.display = 'block';
        let itemLi = createLi(['list-group-item','hover-sistema-uno'],null);
        itemLi.style.cursor = 'pointer';
        let divRowItem = createDiv(['row'],null);

        let divColNombre = createDiv(['col-12'],null);
        divColNombre.textContent = object.nombreProducto;

        let divColSerial = createDiv(['col-12'],null);
        divColSerial.innerHTML = '<small class="text-secondary">'+object.numeroSerie+'</small>';

        itemLi.addEventListener('click',function(){
            clearView();
            viewRegistroForm(object,object.numeroSerie);
        });

        divRowItem.appendChild(divColNombre);
        divRowItem.appendChild(divColSerial);
        itemLi.appendChild(divRowItem);
        suggestionRegistro.appendChild(itemLi);
    }

    function createSuggestionsCliente(object){
        backgroundSuggestion.style.display = 'block';
        let itemLi = createLi(['list-group-item','hover-sistema-uno'],null);
        itemLi.style.cursor = 'pointer';
        let divRowItem = createDiv(['row'],null);
        
        let divColName = createDiv(['col-12'],null);
        divColName.textContent = object.nombre;

        let divColDoc = createDiv(['col-6'],null);
        divColDoc.innerHTML ='<small class="text-secondary">'+ object.numeroDocumento +'</small>';

        let divColType = createDiv(['col-6','text-end'],null);
        divColType.innerHTML = '<small class="text-secondary">'+ object.tipo_documento.descripcion +'</small>';

        itemLi.addEventListener('click',function(){
            inputSearchCliente.value = object.numeroDocumento;
            viewClienteForm(object);
            clearView();
        });

        divRowItem.appendChild(divColName);
        divRowItem.appendChild(divColDoc);
        divRowItem.appendChild(divColType);
        itemLi.appendChild(divRowItem);
        suggestionCliente.appendChild(itemLi);
    }

    function viewRegistroForm(object,query){
        if (object == null || Object.keys(object).length == 0) {
            alertBootstrap('Producto '+query+' no encontrado','warning');
            return;
        }
    
        let nombre = document.getElementById('input-producto-form-nombre');
        let serie = document.getElementById('input-producto-form-serie');
        let marca = document.getElementById('input-producto-form-marca');
        let modelo = document.getElementById('input-producto-form-modelo');
        let id = document.getElementById('input-producto-form-id');
        
        id.value = object.idRegistroProducto;
        nombre.value = object.nombreProducto;
        serie.value = object.numeroSerie;
        marca.value = object.marca;
        modelo.value = object.modelo;

        validateInputs();
    }

    function viewClienteForm(object){
        let nombre = document.getElementById('input-cliente-form-nombre');
        let apePaterno = document.getElementById('input-cliente-form-apePaterno');
        let apeMaterno = document.getElementById('input-cliente-form-apeMaterno');
        let tipoDoc = document.getElementById('input-cliente-form-tipoDoc');
        let numeroDoc = document.getElementById('input-cliente-form-numDoc');
        let telefono = document.getElementById('input-cliente-form-telefono');
        let correo = document.getElementById('input-cliente-form-correo');
        let id = document.getElementById('input-cliente-form-id');

        id.value = object.idCliente;
        nombre.value = object.nombre;
        apePaterno.value = object.apellidoPaterno;
        apeMaterno.value = object.apellidoMaterno;
        tipoDoc.textContent = object.tipo_documento.descripcion;
        numeroDoc.value = object.numeroDocumento;
        telefono.value = object.telefono;
        correo.value = object.correo;

        validateInputs();
    }

    function clearView(){
        suggestionCliente.innerHTML = '';
        suggestionRegistro.innerHTML = '';
        backgroundSuggestion.style.display = 'none';
    }

    function scanOperations(){
        getOneRegistro(getSerial());
    }

    function validateInputs(){
        let input = document.querySelectorAll('.body-form-garantia');
        let inactive = false;

        input.forEach(function(x){
            if(x.value == ''){
                inactive = true;
            }
        });

        btnRegistroGarantia.disabled = inactive;
    }

    inputSearchCliente.addEventListener('input',function(){
        if(this.value.length > 2){
            searchCliente(this.value);
        }else{
            clearView();
            divRegistroInput.style.zIndex = 0;
        }
    });

    inputSearchRegistro.addEventListener('input',function(){
        if(this.value.length > 2){
            searchRegistro(this.value);
        }else{
            clearView();
            divClienteInput.style.zIndex = 0;
        }
    });

    inputSearchCliente.addEventListener('click', function() {
        this.select();
    });

    document.getElementById('form-create-garantia').addEventListener('keydown', function(event) {
        if (event.key == 'Enter') {
            event.preventDefault();
        }
    });

    document.addEventListener('click', function() {
        clearView();
    });

    document.getElementById('btn-modal-new-cliente').addEventListener('click',function(){
        setTimeout(function(){
            viewClienteForm(getCliente());
            console.log(getCliente());
        },500);
        
    });
    
    document.addEventListener('DOMContentLoaded',function(){
        validateInputs();
    });

    let bodyFormGarantia = document.querySelectorAll('textarea');
    bodyFormGarantia.forEach(function(x){
        x.addEventListener('input',validateInputs);
    });
    