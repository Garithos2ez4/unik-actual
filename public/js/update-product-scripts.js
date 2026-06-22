        function calcPrices(){
            let price = document.getElementById('precio-product').value;
            let type = document.getElementById('select-tipoprecio').value;
            let idGrupo = document.getElementById('grupo-product').value;
            let state = document.getElementById('estado-product').value;
            let ganancia = document.getElementById('precio-product-ganancia').value;

            if (price > -1) {
                let xhr = new XMLHttpRequest();
                xhr.open('GET', `/producto/calculate?price=${encodeURIComponent(price)}&type=${encodeURIComponent(type)}&idGrupo=${encodeURIComponent(idGrupo)}&state=${encodeURIComponent(state)}&ganancia=${encodeURIComponent(ganancia)}`, true);

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            let data = JSON.parse(xhr.responseText);
                            let precioCalculado = document.getElementById('precio-product-calculado');
                            let divTotal = document.getElementById('div-total-price');

                            let gananciaVal = parseFloat(ganancia) || 0;
                            precioCalculado.value = (data[0].calculado + gananciaVal).toFixed(2);

                            divTotal.innerHTML = '';

                            data[1].total.forEach(function(x){
                                let divPrecio = document.createElement('div');
                                divPrecio.classList.add('col-lg-4','col-md-8');

                                let labelEmpresa = document.createElement('label');
                                labelEmpresa.classList.add('form-label');
                                labelEmpresa.textContent = x.empresa;

                                let labelPrecio = document.createElement('label');
                                labelPrecio.classList.add('form-label');
                                labelPrecio.textContent = 'Precio Total:';

                                let inputPrecio = document.createElement('input');
                                inputPrecio.type = 'number';
                                inputPrecio.disabled = true;
                                inputPrecio.step = '0.01';
                                inputPrecio.classList.add('form-control');
                                inputPrecio.classList.add('price-product');
                                inputPrecio.value = x.precio.toFixed(2);

                                divPrecio.appendChild(labelEmpresa);
                                divPrecio.appendChild(labelPrecio);
                                divPrecio.appendChild(inputPrecio);
                                divTotal.appendChild(divPrecio);
                            });

                            // Disparar evento personalizado cuando calcPrices termine
                            document.dispatchEvent(new CustomEvent('calcPricesCompleted'));

                        } else {
                            console.error('Error en la solicitud:', xhr.statusText);
                        }
                    }
                };

                xhr.send();
            } else {
                document.getElementById('precio-product-igv').value = 0.00; // Limpiar si no hay entrada
            }
        }

        function removeIgv(){
            let price = this.value;
            let dolarSinIgv = document.getElementById('precio-product');

            if(price > 0){


                dolarSinIgv.value = (price / 1.18).toFixed(2);
            }else{
                dolarSinIgv.value = 0.00;
            }
        }

        function calcIgv(){
            let price = this.value;
            let dolarSinIgv = document.getElementById('precio-product-igv');

            if(price > 0){


                dolarSinIgv.value = (price * 1.18).toFixed(2);
            }else{
                dolarSinIgv.value = 0.00;
            }
        }


        function changeTC(){
            let selectPrice = document.getElementById('select-tipoprecio').value;
            // Excluir explícitamente el precio total fijo
            let priceProduct = document.querySelectorAll('.price-product:not(#precio-total-fijo)');

            priceProduct.forEach(function(x){
                if(selectPrice == 'SOL'){
                    x.value = (x.value * window.APP_DATA.tc).toFixed(2);
                }else{
                    x.value = (x.value / window.APP_DATA.tc).toFixed(2);
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', calcPrices);

        document.getElementById('precio-product-igv').addEventListener('input',removeIgv);
        document.getElementById('precio-product-igv').addEventListener('input',calcPrices);

        document.getElementById('precio-product').addEventListener('input',calcIgv);
        document.getElementById('precio-product').addEventListener('input',calcPrices);

        document.getElementById('precio-product-ganancia').addEventListener('input',calcPrices);

        document.getElementById('select-tipoprecio').addEventListener('change', changeTC);
        document.getElementById('select-tipoprecio').addEventListener('change', calcPrices);
        document.getElementById('estado-product').addEventListener('change',calcPrices);
        
    /*Habilitar los campos al darle click a EDITAR*/
    /*By Franklin*/
    const segmentosFormulario = document.querySelectorAll(".editButton");
    segmentosFormulario.forEach(function(segmento) {
        const boton = segmento.querySelector(".btn-edit");
        const inputs = segmento.querySelectorAll(".input-edit");
        
        let aux = true;

        if(boton){
            boton.addEventListener('click', () => {
                if(aux){
                    inputs.forEach(function(input) {
                        input.disabled = false;
                    });
                }else{
                    inputs.forEach(function(input) {
                        input.disabled = true;
                    });
                }
                
                if(aux){
                    aux = false;
                }else{
                    aux = true;
                }
                
                disableSave();
                
            });
        }
        
        
    });
    document.getElementById('usar_tc_fijo').addEventListener('change', disableSave);
    function disableSave() {
        const btnSave = document.getElementById('btnSave');
        const btnEdit = document.querySelectorAll('.input-edit');
        
        const allDisabled = Array.from(btnEdit).every(btn => btn.disabled);
        
        btnSave.disabled = allDisabled;
    }
    
  function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
  }

  // Ajustar el tamaño del textarea al cargar la página
  document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('desc-producto');
    if (textarea) {
      autoResize(textarea);
    }
  });
  
    
        const dropAreas = document.querySelectorAll('.img-div');

        dropAreas.forEach(function(dropArea) {
            let input = dropArea.querySelector('.img-input');
            let img = dropArea.querySelector('.img-preview');
            
            img.addEventListener('click', function() {
                input.click();
            });
        
            dataImage(input, dropArea, img);
            input.addEventListener('change', function(event) {
                changeImage(event, input, img);
            });
        });
        
        function dataImage(input, dropArea, img) {
            // Prevenir el comportamiento por defecto
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });
        
            // Resaltar el área de arrastre
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });
        
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });
        
            // Manejar el drop
            dropArea.addEventListener('drop', (e) => handleDrop(e, input, img), false);
        }
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        function highlight() {
            this.classList.add('hover');
        }
        
        function unhighlight() {
            this.classList.remove('hover');
        }
        
        function handleDrop(event, input, img) {
            const dt = event.dataTransfer;
            const files = dt.files;
        
            if (files.length) {
                const newDt = new DataTransfer();
                newDt.items.add(files[0]);
                input.files = newDt.files; // Asignar los archivos al input usando un DataTransfer persistente
                changeImage({ target: { files: newDt.files } }, input, img);
            }
        }
        
        function changeImage(event, input, img) {
            const file = event.target.files ? event.target.files[0] : null;
        
            let imgElement = img;
            if (typeof img === 'string') {
                let triggerId = arguments[3]; 
                if (triggerId) {
                    imgElement = document.getElementById(triggerId);
                } else {
                    imgElement = document.getElementById(img);
                }
            }
        
            if (file) {
        
                const reader = new FileReader();
        
                reader.onload = function(e) {
                    const image = new Image();
                    image.src = e.target.result;
        
                    image.onload = function() {
                        const maxWidth = 1000; // Ancho máximo permitido
                        const maxHeight = 1000; // Alto máximo permitido
        
                        if (image.width !== maxWidth || image.height !== maxHeight) {
                            alert('La imagen no coincide con las dimensiones permitidas ' + maxWidth + ' x ' + maxHeight + ' píxeles.');
                            input.value = ''; // Limpiar el input si no coincide
                            if (typeof disableSave === 'function') disableSave();
                            return;
                        }
        
                        // Si la imagen cumple con las dimensiones, actualiza la vista previa
                        if (imgElement) imgElement.src = e.target.result;
                        if (typeof disableSave === 'function') disableSave();
                    }
                    
                    image.onerror = function() {
                        alert('El archivo seleccionado no es una imagen válida.');
                        input.value = '';
                        if (typeof disableSave === 'function') disableSave();
                    }
                };
        
                reader.readAsDataURL(file);
            } else {
                if (typeof disableSave === 'function') disableSave();
            }
        }
        
        function validateForm() {
            let isValid = true;
            
            const name = document.getElementById('name-product').value.trim();
            const upc = document.getElementById('upc-product').value.trim();
            const modelo = document.getElementById('modelo-producto').value.trim();
            const partnumber = document.getElementById('partnumber-product').value.trim();
            const decripcion = document.getElementById('desc-producto').value.trim();
            const precio = document.getElementById('precio-producto').value.trim();
            
            const imgone = document.getElementById('imgone-product').files.length;
            const imgtwo = document.getElementById('imgtwo-product').files.length;
            const imgtree = document.getElementById('imgtree-product').files.length;
            const imgfour = document.getElementById('imgfour-product').files.length;
            
            if (name == '') {
                isValid = false;
            }
            
            if (upc == '') {
                isValid = false;
            } else if(upc.length > 13){
                isValid = false;
                upcError.textContent = 'El UPC/EAN no acepta más de 13 digitos';
            }else{
                upcError.textContent = '';
            }
            
            if (modelo == '') {
                isValid = false;
            }
            
            if (partnumber == '') {
                isValid = false;
            }
            
            if (decripcion == '') {
                isValid = false;
            }
            
            if (precio == '') {
                isValid = false;
            }
            
            if (imgone == 0) {
                isValid = false;
            }
            
            if (imgtwo == 0) {
                isValid = false;
            }
            
            if (imgtree == 0) {
                isValid = false;
            }
            
            if (imgfour == 0) {
                isValid = false;
            }


            return isValid;
        }