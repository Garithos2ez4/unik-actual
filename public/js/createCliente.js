const formNewCliente = document.getElementById('modal-form-create-cliente');
let objectCliente = null;

function validateNumericInput(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
}

function changeTipeDoc(input) {
    let modalNewClient = document.getElementById('nuevoClienteModal');
    let nombre = modalNewClient.querySelector('.client-name');
    let apePaterno = modalNewClient.querySelector('.client-apel-patern');
    let apeMaterno = modalNewClient.querySelector('.client-apel-matern');
    let documento = modalNewClient.querySelector('.client-document');
    let inputDocumento = modalNewClient.querySelector('input[name="numerodoc"]');

    switch (input.value) {
        case '1':
            apePaterno.style.display = 'block';
            apeMaterno.style.display = 'block';
            documento.textContent = 'DNI:';
            nombre.textContent = 'Nombres:';
            inputDocumento.setAttribute('pattern', '\\d+');
            inputDocumento.setAttribute('title', 'solo números permitidos');
            inputDocumento.setAttribute('oninput', 'validateNumericInput(this)');  
            break;
        case '2':
            apePaterno.style.display = 'block';
            apeMaterno.style.display = 'block';
            documento.textContent = 'Carné:';
            nombre.textContent = 'Nombres:';
            inputDocumento.setAttribute('pattern', '\\d+');
            inputDocumento.setAttribute('title', 'solo números permitidos');
            inputDocumento.setAttribute('oninput', 'validateNumericInput(this)');  
            break;
        case '3':
            apePaterno.style.display = 'none';
            apeMaterno.style.display = 'none';
            documento.textContent = 'RUC:';
            nombre.textContent = 'Razon Social:';
            inputDocumento.setAttribute('pattern', '\\d+');
            inputDocumento.setAttribute('title', 'solo números permitidos');
            inputDocumento.setAttribute('oninput', 'validateNumericInput(this)');  
            break;
        default:
            apePaterno.style.display = 'block';
            apeMaterno.style.display = 'block';
            documento.textContent = 'Nro Documento:';
            nombre.textContent = 'Nombres:';
            inputDocumento.setAttribute('pattern', '\\d+');
            inputDocumento.setAttribute('title', 'Solo números permitidos');
            inputDocumento.setAttribute('oninput', 'validateNumericInput(this)');  
    }
}

function sendFormNewCliente() {
    let responseConfirm = confirm('¿Estas seguro?');

    if (responseConfirm == false) {
        return;
    }

    const formData = new FormData(formNewCliente);
    const actionUrl = formNewCliente.getAttribute('action') || '/cliente/create';

    fetch(actionUrl, { 
        method: 'POST',
        body: formData
    })
    .then(response => { 
        if (response.ok) { 
            return response.json(); 
        } else { 
            throw new Error('Error al registrar.'); 
        } 
    })
    .then(data => {
        objectCliente = data;
        alertBootstrap('Cliente '+data.numeroDocumento+' registrado.', 'success');
    }) 
    .catch(error => {
        console.log('error: ' + error);
        alertBootstrap('error: ' + error, 'danger');
    });
}

function getCliente() {
    return objectCliente;
}
