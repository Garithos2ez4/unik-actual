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
            inputDocumento.setAttribute('maxlength', '8');
            inputDocumento.setAttribute('placeholder', '72345678');
            inputDocumento.setAttribute('pattern', '\\d+');
            inputDocumento.setAttribute('title', 'DNI de 8 dígitos');
            inputDocumento.setAttribute('oninput', 'validateNumericInput(this)');  
            break;
        case '2':
            apePaterno.style.display = 'block';
            apeMaterno.style.display = 'block';
            documento.textContent = 'Carné:';
            nombre.textContent = 'Nombres:';
            inputDocumento.setAttribute('maxlength', '12');
            inputDocumento.setAttribute('placeholder', '001234567');
            inputDocumento.removeAttribute('pattern');
            inputDocumento.removeAttribute('oninput');
            break;
        case '3':
            apePaterno.style.display = 'none';
            apeMaterno.style.display = 'none';
            documento.textContent = 'RUC:';
            nombre.textContent = 'Razon Social:';
            inputDocumento.setAttribute('maxlength', '11');
            inputDocumento.setAttribute('placeholder', '20601234567');
            inputDocumento.setAttribute('pattern', '\\d+');
            inputDocumento.setAttribute('title', 'RUC de 11 dígitos');
            inputDocumento.setAttribute('oninput', 'validateNumericInput(this)');  
            break;
        default:
            apePaterno.style.display = 'block';
            apeMaterno.style.display = 'block';
            documento.textContent = 'Nro Documento:';
            nombre.textContent = 'Nombres:';
            inputDocumento.setAttribute('maxlength', '15');
            inputDocumento.setAttribute('placeholder', 'Número de documento');
            inputDocumento.removeAttribute('pattern');
            inputDocumento.removeAttribute('oninput');
    }
}

function sendFormNewCliente() {
    const tipodoc = formNewCliente.querySelector('select[name="tipodoc"]').value;
    const numerodoc = formNewCliente.querySelector('input[name="numerodoc"]').value.trim();

    if (tipodoc === '1' && !/^[0-9]{8}$/.test(numerodoc)) {
        alert('El DNI debe tener exactamente 8 dígitos numéricos.');
        return;
    }

    if (tipodoc === '3' && !/^(10|15|17|20)[0-9]{9}$/.test(numerodoc)) {
        alert('El RUC debe tener 11 dígitos numéricos y comenzar con 10, 15, 17 o 20.');
        return;
    }

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
        if (typeof window.onClienteCreado === "function") {
            window.onClienteCreado(data);
        }
    }) 
    .catch(error => {
        console.log('error: ' + error);
        alertBootstrap('error: ' + error, 'danger');
    });
}

function getCliente() {
    return objectCliente;
}
