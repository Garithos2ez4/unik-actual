document.getElementById('month').addEventListener('change', function() {
    let selectedMonth = this.value;
    let url = "/documentos/" + selectedMonth;
    
    if(selectedMonth == ""){
        alert('Fecha no valida.');
    }else{
        window.location.href = url;
    }
    
});

document.getElementById('month').addEventListener('keydown', function(event) {
    // Evita que la acción de borrado ocurra si se presiona Backspace o Delete
    if (event.key === 'Backspace' || event.key === 'Delete') {
        event.preventDefault();
    }
});

document.getElementById('search').addEventListener('input', function() {
    let query = this.value;
    let hiddenBody = document.getElementById('hidden-body');
    if (query.length > 2) { // Comenzar la búsqueda después de 3 caracteres
        let xhr = new XMLHttpRequest();
        xhr.open('GET', `/documento/searchdocument?query=${query}`, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);
                let suggestions = document.getElementById('suggestions');
                hiddenBody.style.display = 'block';
                suggestions.innerHTML = '';

                    data.forEach(item => {
                        let li = document.createElement('li');
                        li.classList.add('list-group-item', 'hover-sistema-uno');
                        li.style.cursor = "pointer";
                        
                        let divRow = document.createElement('div');
                        divRow.classList.add('row');
                        
                        let divColProv = document.createElement('div');
                        divColProv.classList.add('d-none','d-md-block','col-md-3', 'text-start');
                        let smallProv = document.createElement('small');
                        smallProv.textContent = item.preveedor.nombreProveedor;
                        divColProv.appendChild(smallProv);
                        
                        let divColNum = document.createElement('div');
                        divColNum.classList.add('col-12','col-md-6');
                        let smallNum = document.createElement('small');
                        smallNum.textContent = item.numeroComprobante;
                        divColNum.appendChild(smallNum);
                        
                        let divColDate = document.createElement('div');
                        divColDate.classList.add('col-12','col-md-3');
                        let smallDate = document.createElement('small');
                        smallDate.textContent = item.fechaPersonalizada;
                        divColDate.appendChild(smallDate);
                        
                        li.addEventListener('click', function() {
                            document.getElementById('search').value = item.numeroComprobante; // Mostrar solo el número de documento
                            suggestions.innerHTML = ''; // Limpiar sugerencias después de seleccionar una
                            let encryptedId = item.encryptId; // Encriptar el ID del documento
                            let boolValue = 0;
                            let url =  '/documento/' + encryptedId + '/' + boolValue;
                            window.location.href = url;
                        });
                        
                        divRow.appendChild(divColProv);
                        divRow.appendChild(divColNum);
                        divRow.appendChild(divColDate);
                        li.appendChild(divRow);
                        suggestions.appendChild(li);
                    });
            }
        };
        xhr.send();
    } else {
        document.getElementById('suggestions').innerHTML = ''; // Limpiar si hay menos de 3 caracteres
        hiddenBody.style.display = 'none';
    }
});

function hideSuggestions(event) {
    let suggestions = document.getElementById('suggestions');
    let hiddenBody = document.getElementById('hidden-body');
    if (!suggestions.contains(event.target) && event.target.id !== 'search') {
        suggestions.innerHTML = ''; // Oculta las sugerencias
        hiddenBody.style.display = 'none';
    }
}

// Añadir manejador de eventos para el clic en el documento
document.addEventListener('click', hideSuggestions);