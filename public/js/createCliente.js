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
    let btnConsultarRuc = document.getElementById('btn-consultar-ruc');

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
            if(btnConsultarRuc) {
                btnConsultarRuc.style.display = 'block';
                btnConsultarRuc.setAttribute('title', 'Consultar RENIEC');
            }
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
            if(btnConsultarRuc) btnConsultarRuc.style.display = 'none';
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
            if(btnConsultarRuc) {
                btnConsultarRuc.style.display = 'block';
                btnConsultarRuc.setAttribute('title', 'Consultar SUNAT');
            }
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
            if(btnConsultarRuc) btnConsultarRuc.style.display = 'none';
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

function consultarRucAPI() {
    let modalNewClient = document.getElementById('nuevoClienteModal');
    let docInput = modalNewClient.querySelector('input[name="numerodoc"]');
    let doc = docInput.value.trim();
    let tipoDoc = modalNewClient.querySelector('select[name="tipodoc"]').value;
    
    if (tipoDoc === '3' && doc.length !== 11) {
        Swal.fire('Atención', 'El RUC debe tener 11 dígitos', 'warning');
        return;
    }
    if (tipoDoc === '1' && doc.length !== 8) {
        Swal.fire('Atención', 'El DNI debe tener 8 dígitos', 'warning');
        return;
    }
    
    let btn = document.getElementById('btn-consultar-ruc');
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btn.disabled = true;

    let url = tipoDoc === '3' ? `/cliente/consultar-ruc/${doc}` : `/cliente/consultar-dni/${doc}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            
            if (data.success && data.data) {
                let result = data.data;
                if(result.success === false) {
                    Swal.fire('Error', result.message || 'Documento no encontrado', 'error');
                    return;
                }
                
                if (tipoDoc === '3') {
                    // RUC Response
                    let razonSocial = result.nombre_o_razon_social || result.razonSocial || result.nombre || result.razon_social || '';
                    
                    if (Array.isArray(result) && result.length > 0) {
                        razonSocial = result[0].nombre_o_razon_social || result[0].razonSocial || result[0].razon_social || '';
                    }

                    if (!razonSocial) {
                        Swal.fire('Error', 'No se pudo obtener la razón social de la respuesta', 'error');
                        console.log("Respuesta API:", result);
                        return;
                    }

                    modalNewClient.querySelector('input[name="nombre"]').value = razonSocial;
                    modalNewClient.querySelector('input[name="apepaterno"]').value = '';
                    modalNewClient.querySelector('input[name="apematerno"]').value = '';
                    
                    let msg = 'Razón social obtenida correctamente';
                    if (data.api_count !== undefined) {
                        msg += `<br><small class="text-muted">Consumos este mes: ${data.api_count}/${data.api_limit}</small>`;
                    }
                    Swal.fire({
                        title: 'Éxito',
                        html: msg,
                        icon: 'success'
                    });
                } else if (tipoDoc === '1') {
                    // DNI Response (Decolecta)
                    let nombres = result.first_name || result.nombres || '';
                    let apePaterno = result.first_last_name || result.apellidoPaterno || '';
                    let apeMaterno = result.second_last_name || result.apellidoMaterno || '';
                    
                    if (Array.isArray(result) && result.length > 0) {
                        nombres = result[0].first_name || result[0].nombres || '';
                        apePaterno = result[0].first_last_name || result[0].apellidoPaterno || '';
                        apeMaterno = result[0].second_last_name || result[0].apellidoMaterno || '';
                    }

                    if (!nombres) {
                        Swal.fire('Error', 'No se pudo obtener el nombre de la respuesta', 'error');
                        console.log("Respuesta API:", result);
                        return;
                    }

                    modalNewClient.querySelector('input[name="nombre"]').value = nombres;
                    modalNewClient.querySelector('input[name="apepaterno"]').value = apePaterno;
                    modalNewClient.querySelector('input[name="apematerno"]').value = apeMaterno;
                    
                    let msg = 'DNI obtenido correctamente';
                    if (data.api_count !== undefined) {
                        msg += `<br><small class="text-muted">Consumos este mes: ${data.api_count}/${data.api_limit}</small>`;
                    }
                    Swal.fire({
                        title: 'Éxito',
                        html: msg,
                        icon: 'success'
                    });
                }
            } else {
                Swal.fire('Error', data.message || 'Documento no encontrado', 'error');
            }
        })
        .catch(err => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            console.error(err);
            Swal.fire('Error', 'Problema de conexión o servidor', 'error');
        });
}

function getCliente() {
    return objectCliente;
}
