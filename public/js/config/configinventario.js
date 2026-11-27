function validateForm(id){
    let formPost = document.getElementById(id);
    let confirmacion = confirm('No se podra eliminar ¿Estas seguro(a)?');

    if(confirmacion){
        formPost.submit();
    }

}

function validateModalProveedor(){
    let modal = document.getElementById('form-proveedor');
    let inputs  = modal.querySelectorAll('.form-control');
    let button  = document.getElementById('btn-modal-proveedor');
    let disabledBtn = false;

    inputs.forEach(function(x){
        if(x.value == ''){
            disabledBtn = true;
        }
    });

    button.disabled = disabledBtn;
}

document.addEventListener('DOMContentLoaded', function() {
    let modalProveedor = document.getElementById('form-proveedor');
    let inputsProveedor  = modalProveedor.querySelectorAll('.form-control');

    inputsProveedor.forEach(function(x){
        x.addEventListener('input',validateModalProveedor);
    });
    validateModalProveedor();
});