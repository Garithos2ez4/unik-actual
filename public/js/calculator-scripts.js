
        function chargeComision(){
            let promedio = document.getElementById('costo-promedio').textContent;
            let listPlataformas = document.querySelectorAll('.li-comision-plataforma');
            
            
            listPlataformas.forEach(function(x){
                let porcentaje = x.querySelector('.porcentaje-plataforma').dataset.porcent;
                let montoLabel = x.querySelector('.monto-plataforma');
                let costoLabel = x.querySelector('.costo-plataforma');
                let tsfactLabel = x.querySelector('.tsfact-plataforma');
                let tfactLabel = x.querySelector('.tfact-plataforma');
                let promedioLabel = x.querySelector('.promedio-plataforma');
                
                let monto = 0;
                let costo = 0;
                let tsfact = 0;
                let flete = parseFloat(x.querySelector('.flete-plataforma').dataset.flete);
                
                monto =  promedio * ((porcentaje / 100) + 1);
                costo = parseFloat(promedio) + (monto * (porcentaje / 100));
                tsfact = (parseFloat(costo) - parseFloat(promedio)) + parseFloat(promedio);
                tfact = tsfact * window.APP_DATA.facturacionFactor;
                promed = ((parseFloat(tfact) + parseFloat(tsfact))/2) + parseFloat(flete);
                
                montoLabel.textContent = monto.toFixed(1) + '0';
                costoLabel.textContent = costo.toFixed(1) + '0';    
                tsfactLabel.textContent = tsfact.toFixed(1) + '0'; 
                tfactLabel.textContent = tfact.toFixed(1) + '0';
                promedioLabel.textContent = promed.toFixed(1) + '0';
            });
        }
        let inputEntrada = document.getElementById('precio-entrada');
        let inputIgv = document.getElementById('costo-igv');
            
        function mostrarGrupos(cat){
            let divGrupo = document.querySelectorAll('.calc-category');
            
            
            divGrupo.forEach(function(x){
                if(x.dataset.cat == cat){
                    x.style.display = 'block';
                }else{
                    x.style.display = 'none';
                }
            });
            
        }
        
        function changeGroupValues(id,name){
            let titleGroup = document.getElementById('title-group');
            let hiddenGroup =  document.getElementById('hidden-group');
            
            titleGroup.textContent = name;
            hiddenGroup.value = id;
            
            let divGrupo = document.querySelectorAll('.calc-category');
            
            
            divGrupo.forEach(function(x){
                x.style.display = 'none';
            });
            
            const dropdown = bootstrap.Dropdown.getInstance(titleGroup);
            if (dropdown) {
                dropdown.hide();
            }
            
            calculateBackEnd();
        }
        
        function calcIgv(){
            inputIgv.value = (inputEntrada.value * window.APP_DATA.igvFactor).toFixed(2);
        }
        
        function resetIgv(){
            inputEntrada.value = (inputIgv.value / window.APP_DATA.igvFactor).toFixed(2);
        }
        
        function selectAllText(event) {
            event.target.select(); // Selecciona todo el contenido del campo de entrada
        }
        
        function calculateTc(input){
            let tipo = input.value;
            let entrada = inputEntrada.value;
            
            if(tipo == 'SOL'){
                inputEntrada.value = (entrada * window.APP_DATA.tasaCambio).toFixed(2);
            }else{
                inputEntrada.value = (entrada / window.APP_DATA.tasaCambio).toFixed(2);
            }
            
        }
        
        function showLoading() {
            let loadingElement = document.querySelectorAll('.loading');
            
            loadingElement.forEach(function(x){
                x.style.display = 'flex';
            });
        }
    
        function hideLoading() {
            let loadingElement = document.querySelectorAll('.loading');
            
            loadingElement.forEach(function(x){
                x.style.display = 'none';
            });
        }
        
        function calculateBackEnd() {
            let query = document.getElementById('precio-entrada').value;
            let type = document.getElementById('select-type').value;
            let grup = document.getElementById('hidden-group').value;
            
            if (query.length > 0 || query != null) {
                showLoading();
                let xhr = new XMLHttpRequest();
                xhr.open('GET', `/calculadora/calculate?query=${encodeURIComponent(query)}&type=${encodeURIComponent(type)}&grup=${encodeURIComponent(grup)}`, true);
        
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            let data = JSON.parse(xhr.responseText);
                            let costoTsFact = document.getElementById('costo-tsfact');
                            let costoTFact = document.getElementById('costo-tfact');
                            let costoGanancia = document.getElementById('costo-ganancia');
                            let costoPromedio = document.getElementById('costo-promedio');
                            
                            costoTsFact.textContent = data.tsfact.toFixed(1) + '0';
                            costoTFact.textContent = data.tfact.toFixed(1)+ '0';
                            costoGanancia.textContent = data.ganancia.toFixed(1)+ '0';
                            costoPromedio.textContent = data.promedio.toFixed(1)+ '0';
                            hideLoading();
                            chargeComision();
                        } else {
                            console.error('Error en la solicitud:', xhr.statusText);
                        }
                    }
                };
        
                xhr.send();
            } else {
                document.getElementById('dolar-igv').textContent = 'nada'; // Limpiar si no hay entrada
            }
        }
        
        document.getElementById('precio-entrada').addEventListener('blur',calculateBackEnd);
        document.getElementById('precio-entrada').addEventListener('input',calcIgv);
        document.getElementById('precio-entrada').addEventListener('focus',selectAllText);
        
        document.getElementById('costo-igv').addEventListener('blur',calculateBackEnd);
        document.getElementById('costo-igv').addEventListener('input',resetIgv);
        document.getElementById('costo-igv').addEventListener('focus',selectAllText);
        
        document.getElementById('select-type').addEventListener('change',calculateBackEnd);
        document.getElementById('select-type').addEventListener('change',calcIgv);