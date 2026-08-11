document.addEventListener('DOMContentLoaded', function() {
    let checkOrdersMeta = document.querySelector('meta[name="last-order-id"]');
    if (!checkOrdersMeta) return;
    
    let lastOrderId = parseInt(checkOrdersMeta.getAttribute('content')) || 0;

    setInterval(() => {
        fetch(`/api/check-new-orders?last_id=${lastOrderId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.new_orders && data.new_orders.length > 0) {
                    data.new_orders.forEach(order => {
                        let clienteNombre = order.cliente ? (order.cliente.nombre + ' ' + order.cliente.apellidos) : 'Desconocido';
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: '¡Nuevo Pedido Web!',
                            html: `Pedido <b>#${order.idPedidoWeb}</b> por S/ ${parseFloat(order.total).toFixed(2)}<br><small>Cliente: ${clienteNombre}</small>`,
                            showConfirmButton: true,
                            confirmButtonText: 'Ir a Pedidos',
                            timer: 15000,
                            timerProgressBar: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "/configuracion/pedidos-web";
                            }
                        });
                    });
                }
                
                if (data.max_id > lastOrderId) {
                    lastOrderId = data.max_id;
                }
            }
        })
        .catch(error => console.error('Error verificando nuevos pedidos:', error));
    }, 3600000); // 1 hora
});
