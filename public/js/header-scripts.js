function viewUser(){
    let options = document.getElementById('options-user');
    options.style.display = 'block';
}

function hideUser(){
    let options = document.getElementById('options-user');
    options.style.display = 'none';
}

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('header-user-nav').addEventListener('mouseover',viewUser);
    document.getElementById('header-user-nav').addEventListener('mouseout',hideUser);

    document.getElementById('options-user').addEventListener('mouseover',viewUser);
    document.getElementById('options-user').addEventListener('mouseout',hideUser);

    let passInput = document.getElementById('pass-modal-password');
    let confirmPassInput = document.getElementById('confirmpass-modal-password');
    let btnRes = document.getElementById('btn-reestablecer-modal-password');
    
    if (passInput && confirmPassInput && btnRes) {
        disableReestablecer();
        passInput.addEventListener('input', disableReestablecer);
        confirmPassInput.addEventListener('input', disableReestablecer);
    }
});



function getIdPass(id,input){
    let inputHidden = document.getElementById(input);
    
    inputHidden.value = id;
}

function markPendientesAsRead(idUser, bandejaHash) {
    let navIcon = document.getElementById('nav-alert-icon');
    let menuIcon = document.getElementById('menu-alert-icon');
    let linkEl = document.getElementById('nav-pendientes-link');
    
    if (navIcon) navIcon.style.display = 'none';
    if (menuIcon) menuIcon.style.display = 'none';
    if (linkEl) {
        linkEl.classList.remove('text-danger', 'fw-bold');
        linkEl.classList.add('text-secondary');
    }

    if (idUser && bandejaHash) {
        document.cookie = "bandeja_read_" + idUser + "=" + bandejaHash + "; path=/; max-age=" + (60*60*24*365);
    }
}

function openMyPendientes(idUser, url, bandejaHash) {
    markPendientesAsRead(idUser, bandejaHash);
    
    let loader = document.getElementById('modalBandeja-total-body');
    let titleEl = document.getElementById('bandejaModalLabel');
    
    document.getElementById('id-modal-bandeja').value = idUser;
    if (titleEl) {
        titleEl.innerHTML = 'Mis Pendientes';
    }
    
    if (loader) loader.style.display = 'flex';
    
    fetch(url + "?id=" + idUser, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (loader) loader.style.display = 'none';
        if (typeof quill !== 'undefined' && quill) {
            quill.root.innerHTML = data.bandeja || '';
        } else {
            let tb = document.getElementById('text-bandeja');
            if (tb) tb.innerHTML = data.bandeja || '';
        }
    })
    .catch(error => {
        if (loader) loader.style.display = 'none';
        console.log('Error:', error);
    });
}

function cancelarModal(){
    let pass = document.getElementById('pass-modal-password');
    let validatepass = document.getElementById('confirmpass-modal-password');
    let passError =  document.getElementById('passwordError');
    let confirmPasswordError =  document.getElementById('confirmPasswordError');
    
    pass.value = '';
    validatepass.value = '';
    passError.textContent = '';
    confirmPasswordError.textContent = '';
}

function validateModal() {
    let isValid = true;
    
    const pass = document.getElementById('pass-modal-password').value.trim();
    const validatepass = document.getElementById('confirmpass-modal-password').value.trim();
    const passError =  document.getElementById('passwordError');
    const confirmPasswordError =  document.getElementById('confirmPasswordError');
    
    let numberRegex = /[0-9]/;
    let caracterRegex = /(?=.*[\W_])/;
    
    if (pass == '') {
        isValid = false;
        passError.textContent = '';
    } else if (pass.length < 8) {
        isValid = false;
        passError.textContent = 'La contraseña debe tener al menos 8 caracteres.';
    } else if (!numberRegex.test(pass)) {
        isValid = false;
        passError.textContent = 'La contraseña debe contener al menos un numero.';
    } else if (!caracterRegex.test(pass)) {
        isValid = false;
        passError.textContent = 'La contraseña debe contener al menos un carácter especial.';
    } else {
        passError.textContent = ''; // Limpiar mensaje de error si la contraseña es válida
    }
    
    if (validatepass == '') {
        isValid = false;
        confirmPasswordError.textContent = '';
    } else if (validatepass.length < 8) {
        isValid = false;
        confirmPasswordError.textContent = 'La contraseña debe tener al menos 8 caracteres.';
    } else if (!numberRegex.test(validatepass)) {
        isValid = false;
        confirmPasswordError.textContent = 'La contraseña debe contener al menos un numero.';
    } else if (!caracterRegex.test(validatepass)) {
        isValid = false;
        confirmPasswordError.textContent = 'La contraseña debe contener al menos un carácter especial.';
    } else if(validatepass != pass){
        confirmPasswordError.textContent = 'La contraseña no coincide';
    }else {
        confirmPasswordError.textContent = '';
    }
    
    return isValid;
}

function disableReestablecer() {
    let btnRes = document.getElementById('btn-reestablecer-modal-password');
    if (validateModal()) {
        btnRes.classList.remove('disabled');
    } else {
        btnRes.classList.add('disabled');
    }
}
