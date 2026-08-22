document.addEventListener('DOMContentLoaded', function() {
    let today = new Date().toISOString().split('T')[0];
    
    // Web: Usa reseteo diario para recordar pedidos pendientes
    let storedWebData = JSON.parse(localStorage.getItem('notifiedWebData')) || { date: '', orders: [] };
    if (storedWebData.date !== today) {
        storedWebData = { date: today, orders: [] };
        localStorage.setItem('notifiedWebData', JSON.stringify(storedWebData));
    }
    let notifiedWebOrders = storedWebData.orders;

    // Ripley: Solo 1 vez por orden y nunca más (persistente)
    let notifiedRipleyOrders = JSON.parse(localStorage.getItem('notifiedRipleyOrders_v2')) || [];

    function checkOrders() {
        fetch(`/api/check-new-orders`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let showWebAlert = false;
                let showRipleyAlert = false;
                
                let webHtml = '';
                if (data.new_orders && data.new_orders.length > 0) {
                    data.new_orders.forEach(order => {
                        if (!notifiedWebOrders.includes(order.idPedidoWeb)) {
                            showWebAlert = true;
                            let clienteNombre = order.cliente ? (order.cliente.nombre + ' ' + order.cliente.apellidos) : 'Desconocido';
                            webHtml += `<div style="text-align: left; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                                <b>Pedido #${order.idPedidoWeb}</b> (Web) - S/ ${parseFloat(order.total).toFixed(2)}<br>
                                <small style="color: #666;">Cliente: ${clienteNombre}</small>
                            </div>`;
                            notifiedWebOrders.push(order.idPedidoWeb);
                        }
                    });
                }

                let ripleyHtml = '';
                if (data.ripley_orders && data.ripley_orders.length > 0) {
                    data.ripley_orders.forEach(order => {
                        if (!notifiedRipleyOrders.includes(order.order_id)) {
                            showRipleyAlert = true;
                            ripleyHtml += `<div style="text-align: left; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                                <b>Orden #${order.order_id}</b> (Ripley) - S/ ${parseFloat(order.price).toFixed(2)}<br>
                                <small style="color: #666;">Cliente: ${order.customer_name}</small>
                            </div>`;
                            notifiedRipleyOrders.push(order.order_id);
                        }
                    });
                }
                
                if (showWebAlert || showRipleyAlert) {
                    Swal.fire({
                        icon: 'info',
                        title: '¡Tienes pedidos pendientes!',
                        html: `<div style="max-height: 300px; overflow-y: auto;">${webHtml}${ripleyHtml}</div>`,
                        showConfirmButton: true,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#0dcaf0',
                        backdrop: `rgba(0,0,0,0.6)`
                    });
                }

                // Guardar Web (Con fecha)
                storedWebData.orders = notifiedWebOrders;
                localStorage.setItem('notifiedWebData', JSON.stringify(storedWebData));

                // Guardar Ripley (Persistente)
                localStorage.setItem('notifiedRipleyOrders_v2', JSON.stringify(notifiedRipleyOrders));
            }
        })
        .catch(error => console.error('Error verificando nuevos pedidos:', error));
    }

    // Check immediately on load, then every hour
    checkOrders();
    setInterval(checkOrders, 3600000); 
});
