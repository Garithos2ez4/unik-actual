     document.addEventListener('keydown', function(event) {
            
            if (event.key === 'Enter') {
                const activeElement = document.activeElement;
                const isInput = activeElement.tagName == 'INPUT';
                const isSelect = activeElement.tagName == 'SELECT';
                
                if (isInput || isSelect) {
                    event.preventDefault();
                }
            }
        });
        
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
                            precioCalculado.value = data[0].calculado.toFixed(2);
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
            let priceProduct = document.querySelectorAll('.price-product');
            
            priceProduct.forEach(function(x){
                if(selectPrice == 'SOL'){
                    x.value = (x.value * {{$tc}}).toFixed(2);
                }else{
                    x.value = (x.value / {{$tc}}).toFixed(2);
                }
            });
            
            
        }
        
        document.addEventListener('DOMContentLoaded', calcPrices);
        
        document.getElementById('precio-product-igv').addEventListener('input',removeIgv);
        document.getElementById('precio-product-igv').addEventListener('blur',calcPrices);
        
        document.getElementById('precio-product').addEventListener('input',calcIgv);
        document.getElementById('precio-product').addEventListener('blur',calcPrices);
        
        document.getElementById('precio-product-ganancia').addEventListener('blur',calcPrices);
        
        document.getElementById('select-tipoprecio').addEventListener('change',calcPrices);
        document.getElementById('estado-product').addEventListener('change',calcPrices);
        document.getElementById('grupo-label').addEventListener('change',calcPrices);
        
        function manejarSubmit(){
            if (!validarCodigos()) {
                event.preventDefault();
            }
        }
        
        function validarCodigos() {
            const selectGrup = document.getElementById('grupo-product');
            const grupo = selectGrup.value.trim();
            const nomGrupo = selectGrup.options[selectGrup.selectedIndex].textContent.trim();
            
            const inputCod = document.getElementById('codigo-product');
            
            const codigos = @json($codigos->mapWithKeys(function($cod) {
                return [$cod->codigoProducto => $cod->idGrupo]; 
            }));
            
            let find = false; 
            let cod = '';
            
            for (const [codigoProducto, idGrupo] of Object.entries(codigos)) {
                if (idGrupo == grupo) {
                    cod = codigoProducto;
                    find = true;
                    break;
                }
            }
            
            if (!find) {
                cod = prompt('No se encontró el código para ' + nomGrupo + '. Por favor, ingrese un nuevo código:') + '0000';
                if (cod == null || cod.trim() == '' || cod.trim().length != 10) {
                    return false;
                }
            }
            
            inputCod.value = cod;
            
            return true;
        }
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
                input.files = files; // Asignar los archivos al input
                changeImage({ target: { files } }, input, img);
            }
        }
        
        function changeImage(event, input, img) {
            const file = event.target.files ? event.target.files[0] : null;
        
        
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
                            return;
                        }
        
                        // Si la imagen cumple con las dimensiones, actualiza la vista previa
                        img.src = e.target.result;
                    }
                };
        
                reader.readAsDataURL(file);
            }
            
            disableButton();
        }
        
        function validateForm() {
            let isValid = true;
            
            const name = document.getElementById('name-product').value.trim();
            const precio = document.getElementById('precio-product').value.trim();
            const ganancia = document.getElementById('precio-product-ganancia').value.trim();
            const upc = document.getElementById('upc-product').value.trim();
            const modelo = document.getElementById('modelo-product').value.trim();
            const partnumber = document.getElementById('partnumber-product').value.trim();
            const stock = document.querySelectorAll('.stock-product');
            const stockproveedor = document.getElementById('stockproveedor-product').value.trim();
            const descripcion = document.getElementById('descripcion-product').value.trim(); 
            const grupo = document.getElementById('grupo-product').value.trim();
            const marca = document.getElementById('marca-product').value.trim();
            const estado = document.getElementById('estado-product').value.trim();
            const garantia = document.getElementById('garantia-product').value.trim();
            const proveedor = document.getElementById('proveedor-product').value.trim();
            const imgone = document.getElementById('imgone-product').files;
            const imgtwo = document.getElementById('imgtwo-product').files.length; 
            const imgtree = document.getElementById('imgtree-product').files.length; 
            const imgfour = document.getElementById('imgfour-product').files.length; 
            
            const lblgrupo = document.getElementById('grupo-label');
            const lblmarca = document.getElementById('marca-label');
            const lblestado = document.getElementById('estado-label');
            const lblgarantia = document.getElementById('garantia-label');
            const lblproveedor = document.getElementById('proveedor-label');
            
            if (name == '') {
                isValid = false;
            }
            
            if (ganancia == '') {
                isValid = false;
            }
            
            if (precio == '') {
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
            
            stock.forEach(function(x){
                if(x.value == ''){
                    isValid = false;
                }
            });
            
            if (stockproveedor == '') {
                isValid = false;
            }
            if (descripcion == '') {
                isValid = false;
            }
            
            if (grupo == '') {
                isValid = false;
                lblgrupo.classList.add('text-danger');
            }else{
                lblgrupo.classList.remove('text-danger');
            }
            
            if (marca == '') {
                isValid = false;
                lblmarca.classList.add('text-danger');
            }else{
                lblmarca.classList.remove('text-danger');
            }
            
            if (estado == '') {
                isValid = false;
                lblestado.classList.add('text-danger');
            }else{
                lblestado.classList.remove('text-danger');
            }
            
            if (garantia == '') {
                isValid = false;
                lblgarantia.classList.add('text-danger');
            }else{
                lblgarantia.classList.remove('text-danger');
            }
            
            if (proveedor == '') {
                isValid = false;
                lblproveedor.classList.add('text-danger');
            }else{
                lblproveedor.classList.remove('text-danger');
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
        
        function disableButton() {
            let btnRegistrar = document.getElementById('btnRegistrar');
            if (validateForm()) {
                btnRegistrar.classList.remove('disabled');
            } else {
                btnRegistrar.classList.add('disabled');
            }
        }
        
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
            validateForm
          }
          
        function checkException(check,id){
            let inputText = document.getElementById(id);
            
            if(check.checked){
                inputText.value = 0;
                inputText.readOnly   = true;
            }else{
                inputText.value = "";
                inputText.readOnly   = false;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Ejecutar disableButton() al cargar la página para establecer el estado inicial del botón
            disableButton();

            // Calcular IGV inicial si hay precio
            let priceInput = document.getElementById('precio-product');
            if (priceInput && priceInput.value > 0) {
                let igvInput = document.getElementById('precio-product-igv');
                if (igvInput) {
                    igvInput.value = (priceInput.value * 1.18).toFixed(2);
                }
            }

            // Agregar listeners a los campos para validar al cambiar
            document.getElementById('name-product').addEventListener('input', disableButton);
            document.getElementById('precio-product').addEventListener('input', disableButton);
            document.getElementById('upc-product').addEventListener('input', disableButton);
            document.getElementById('modelo-product').addEventListener('input', disableButton);
            document.getElementById('partnumber-product').addEventListener('input', disableButton);
            document.getElementById('stockproveedor-product').addEventListener('input', disableButton);
            document.getElementById('descripcion-product').addEventListener('input', disableButton);
            document.getElementById('grupo-product').addEventListener('input', disableButton);
            document.getElementById('marca-product').addEventListener('input', disableButton);
            document.getElementById('estado-product').addEventListener('input', disableButton);
            document.getElementById('garantia-product').addEventListener('input', disableButton);
            document.getElementById('proveedor-product').addEventListener('input', disableButton);
            document.getElementById('imgone-product').addEventListener('input', disableButton);
            document.getElementById('imgtwo-product').addEventListener('input', disableButton);
            document.getElementById('imgtree-product').addEventListener('input', disableButton);
            document.getElementById('imgfour-product').addEventListener('input', disableButton);
            document.querySelectorAll('.stock-product').forEach(function(x){
                x.addEventListener('input', disableButton);
            });
            
            var textarea = document.getElementById('descripcion-product');
                if (textarea) {
                  autoResize(textarea);
                }
                
        });
        
        