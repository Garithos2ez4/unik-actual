document.addEventListener('DOMContentLoaded', function() {
    setupDropArea('drop-area-marca', 'file-modal-marca', 'img-modal-marca', 1000, 400);
    setupDropArea('drop-area-grupo', 'file-modal-grupo', 'img-modal-grupo', 300, 300);
    validateButton('btn-modal-marca','body-modal-marcas');
    validateButton('btn-modal-grupo','body-modal-grupo');
    validateButton('btn-modal-categoria','body-modal-categoria');
});

function validateForm(id){
    let formPost = document.getElementById(id);
    let confirmacion = confirm('No se podra eliminar ¿Estas seguro(a)?');

    if(confirmacion){
        formPost.submit();
    }

}

function sendGrupoData(categoria,title){
    let inputHidden = document.getElementById('hidde-modal-grupo');
    let titleModal = document.getElementById('title-modal-grupo');

    inputHidden.value = categoria;
    titleModal.textContent = title;
}

function validateButton(button, modal) {
    const btnModal = document.getElementById(button);
    const modalBody = document.getElementById(modal);
    const inputsModal = modalBody.querySelectorAll('input');
    const selectModal = modalBody.querySelector('select');
    
    function validate() {
        let disableBtn = false;

        inputsModal.forEach(function(x) {
            if (x.type === 'file') {
                if (x.files.length < 1) {
                    disableBtn = true;
                }
            } else {
                if (x.value === '') {
                    disableBtn = true;
                }
            }
        });

        if(selectModal != null){
            if(selectModal.value == ''){
                disableBtn = true;
            }
        }

        btnModal.disabled = disableBtn;
    }

    inputsModal.forEach(function(x) {
        if (x.type === 'file') {
            x.addEventListener('change', validate);
        } else {
            x.addEventListener('input', validate);
        }
    });

    if(selectModal != null){
        selectModal.addEventListener('change',validate);
    }

    validate();
}
function setupDropArea(dropAreaId, fileInputId, imgElementId, maxWidth, maxHeight) {
    const dropArea = document.getElementById(dropAreaId);
    const fileInput = document.getElementById(fileInputId);
    const imgElement = document.getElementById(imgElementId);

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
    });

    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        handleFiles(files);
    }

    function handleFiles(files) {
        const file = files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onloadend = () => {
                const image = new Image();
                image.src = reader.result;

                image.onload = () => {
                    if (image.width !== maxWidth || image.height !== maxHeight) {
                        alert('La imagen no coincide con las dimensiones permitidas ' + maxWidth + ' x ' + maxHeight + ' píxeles.');
                        fileInput.value = ''; // Limpiar el input si no coincide
                        return;
                    }

                    imgElement.src = reader.result;
                    fileInput.files = files;
                };
            };
        }
    }

    // Handle click to open file input
    dropArea.addEventListener('click', () => fileInput.click());

    // Handle file input change
    fileInput.addEventListener('change', (e) => {
        const files = e.target.files;
        handleFiles(files);
    });
}