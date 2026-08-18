document.addEventListener('DOMContentLoaded', function() {
    let notifiedWebOrders = JSON.parse(localStorage.getItem('notifiedWebOrders')) || [];
    let notifiedRipleyOrders = JSON.parse(localStorage.getItem('notifiedRipleyOrders')) || [];

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
                // Alertas Pedidos Web
                if (data.new_orders && data.new_orders.length > 0) {
                    data.new_orders.forEach(order => {
                        if (!notifiedWebOrders.includes(order.idPedidoWeb)) {
                            let clienteNombre = order.cliente ? (order.cliente.nombre + ' ' + order.cliente.apellidos) : 'Desconocido';
                            Swal.fire({
                                icon: 'success',
                                title: '¡Nuevo Pedido Web!',
                                html: `<div style="font-size: 1.1em; margin-bottom: 15px;">Pedido <b>#${order.idPedidoWeb}</b> por <b>S/ ${parseFloat(order.total).toFixed(2)}</b></div><div style="color: #555;">Cliente: ${clienteNombre}</div>`,
                                showConfirmButton: true,
                                showCancelButton: true,
                                confirmButtonText: '<i class="bi bi-box-arrow-up-right"></i> Ir a Pedidos Web',
                                cancelButtonText: 'Cerrar',
                                confirmButtonColor: '#28a745',
                                backdrop: `rgba(0,0,0,0.6)`
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "/configuracion/pedidos-web";
                                }
                            });
                            notifiedWebOrders.push(order.idPedidoWeb);
                        }
                    });
                    localStorage.setItem('notifiedWebOrders', JSON.stringify(notifiedWebOrders));
                }

                // Alertas Pedidos Ripley
                if (data.ripley_orders && data.ripley_orders.length > 0) {
                    data.ripley_orders.forEach(order => {
                        if (!notifiedRipleyOrders.includes(order.order_id)) {
                            Swal.fire({
                                icon: 'info',
                                title: '¡Nuevo Pedido en Ripley!',
                                html: `<div style="font-size: 1.1em; margin-bottom: 15px;">Orden <b>#${order.order_id}</b> por <b>S/ ${parseFloat(order.price).toFixed(2)}</b></div><div style="color: #555;">Cliente: ${order.customer_name}</div>`,
                                showConfirmButton: true,
                                showCancelButton: true,
                                confirmButtonText: '<i class="bi bi-graph-up"></i> Ver Analítica Ripley',
                                cancelButtonText: 'Cerrar',
                                confirmButtonColor: '#0dcaf0',
                                backdrop: `rgba(0,0,0,0.6)`
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "/dashboard/analitica/ripley";
                                }
                            });
                            notifiedRipleyOrders.push(order.order_id);
                        }
                    });
                    localStorage.setItem('notifiedRipleyOrders', JSON.stringify(notifiedRipleyOrders));
                }
            }
        })
        .catch(error => console.error('Error verificando nuevos pedidos:', error));
    }

    // Check immediately on load, then every hour
    checkOrders();
    setInterval(checkOrders, 3600000); 
});
