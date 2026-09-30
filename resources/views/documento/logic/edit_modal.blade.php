<script>
    function cambiarSimboloMoneda(moneda) {
        const symbol = moneda === 'DOLAR' ? '$' : 'S/';
        document.querySelectorAll('.span-simbolo-moneda').forEach(el => el.textContent = symbol);
    }

    function recalcularFilaYTotal(input) {
        let pu = parseFloat(input.value) || 0;
        let qty = parseFloat(input.dataset.qty) || 0;
        let index = input.dataset.index;
        
        let subtotalSpan = document.getElementById(`subtotal-row-${index}`);
        if (subtotalSpan) {
            subtotalSpan.textContent = (pu * qty).toFixed(2);
        }

        recalcularTotalComprobante();
    }

    function recalcularTotalComprobante() {
        let total = 0;
        document.querySelectorAll('.input-detalle-pu').forEach(input => {
            let pu = parseFloat(input.value) || 0;
            let qty = parseFloat(input.dataset.qty) || 0;
            total += (pu * qty);
        });

        const totalInput = document.getElementById('edit-comprobante-total');
        if (totalInput) {
            totalInput.value = total.toFixed(2);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById('addIgvCheckbox');
        if (checkbox) {
            checkbox.addEventListener('change', function () {
                const inputs = document.querySelectorAll('.input-detalle-pu');
                inputs.forEach(input => {
                    if (input.value) {
                        let val = parseFloat(input.value);
                        if (this.checked) {
                            input.dataset.original = val;
                            input.value = (val * 1.18).toFixed(2);
                        } else {
                            if (input.dataset.original) {
                                input.value = parseFloat(input.dataset.original).toFixed(2);
                            } else {
                                input.value = (val / 1.18).toFixed(2);
                            }
                        }
                        let qty = parseFloat(input.dataset.qty) || 0;
                        let index = input.dataset.index;
                        let subtotalSpan = document.getElementById(`subtotal-row-${index}`);
                        if (subtotalSpan) {
                            subtotalSpan.textContent = (parseFloat(input.value) * qty).toFixed(2);
                        }
                    }
                });
                recalcularTotalComprobante();
            });
        }
    });

    function calcularProrrateo() {
        let totalPagado = parseFloat(document.getElementById('prorrateoTotalPagado').value);
        if (!totalPagado || totalPagado <= 0) {
            alert("Por favor ingresa el monto total de la factura.");
            return;
        }

        let items = [];
        let costoTeoricoTotal = 0;

        document.querySelectorAll('.prorrateo-ref').forEach(input => {
            let index = input.getAttribute('data-index');
            let refPrice = parseFloat(input.value) || 0;
            let spanCant = document.querySelector(`.prorrateo-cant[data-index="${index}"]`);
            let qty = parseFloat(spanCant.innerText) || 0;

            let rowTeorico = refPrice * qty;
            costoTeoricoTotal += rowTeorico;

            items.push({
                index: index,
                refPrice: refPrice,
                qty: qty,
                rowTeorico: rowTeorico
            });
        });

        if (costoTeoricoTotal <= 0) {
            alert("Los precios referenciales no pueden ser cero en todos los productos.");
            return;
        }

        items.forEach(item => {
            let porcentaje = item.rowTeorico / costoTeoricoTotal;
            let badge = document.getElementById(`peso-${item.index}`);
            if (badge) {
                badge.innerText = (porcentaje * 100).toFixed(2) + '%';
                badge.classList.remove('bg-secondary');
                badge.classList.add('bg-success');
            }

            let costoProrrateadoFila = totalPagado * porcentaje;
            let costoUnitarioFinal = item.qty > 0 ? (costoProrrateadoFila / item.qty) : 0;

            let mainInput = document.querySelector(`input[name="detalles[${item.index}][precioUnitario]"]`);
            if (mainInput) {
                mainInput.value = costoUnitarioFinal.toFixed(2);
                
                let checkbox = document.getElementById('addIgvCheckbox');
                if(checkbox && checkbox.checked) {
                    mainInput.dataset.original = costoUnitarioFinal.toFixed(2);
                    mainInput.value = (costoUnitarioFinal * 1.18).toFixed(2);
                }

                let subtotalSpan = document.getElementById(`subtotal-row-${item.index}`);
                if (subtotalSpan) {
                    subtotalSpan.textContent = (parseFloat(mainInput.value) * item.qty).toFixed(2);
                }
            }
        });

        recalcularTotalComprobante();
    }
</script>
