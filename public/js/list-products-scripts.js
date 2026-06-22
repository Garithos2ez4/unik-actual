let clickPrices = false;
function changePriceList(){
    let prices = document.querySelectorAll('.price-list-product');
    if(clickPrices){
        prices.forEach(function(x){
            let preciesito = x.dataset.value;
            x.textContent = '$' + preciesito;
        });
        clickPrices = false;
    }else{
        prices.forEach(function(x){
            let preciesito = x.dataset.value;
            x.textContent = 'S/.' + (preciesito * window.APP_DATA.tc).toFixed(2);
        });
        clickPrices = true;
    }
    
}

function mostrarImg(id) {
    var imgDiv = document.getElementById('img-' + id);
    imgDiv.style.display = 'block';
}

function ocultarImg(id) {
    var imgDiv = document.getElementById('img-' + id);
    imgDiv.style.display = 'none';
}
        
        
          