<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css" />
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const driver = window.driver && window.driver.js ? window.driver.js.driver : null;
        if (!driver) return;

        function startTour() {
            const tourObj = driver({
                showProgress: true,
                doneBtnText: 'Finalizar',
                closeBtnText: 'Omitir',
                nextBtnText: 'Siguiente',
                prevBtnText: 'Anterior',
                onDestroyStarted: () => {
                    if (!tourObj.hasNextStep() || confirm("¿Seguro que deseas saltar el tour?")) {
                        tourObj.destroy();

                        // Guardar estado en base de datos solo si no estaba completado
                        @if(isset($user) && !$user->tour_completado)
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        if (csrfMeta) {
                            fetch('{{ route("tour.completado") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfMeta.content
                                }
                            });
                        }
                        @endif
                    }
                },
                steps: [{
                        element: '#tour-movimientos',
                        popover: {
                            title: 'Movimientos',
                            description: 'Gestión de comprobantes y flujo de inventario: Ingresos (compras/packs), Egresos (salidas de stock), Licencias, Traslados y Garantías.',
                            side: 'right',
                            align: 'start'
                        }
                    },
                    {
                        element: '#tour-productos',
                        popover: {
                            title: 'Productos',
                            description: 'Aquí gestionas tu catálogo y precios de todos tus almacenes.',
                            side: 'right',
                            align: 'start'
                        }
                    },
                    {
                        element: '#tour-envios',
                        popover: {
                            title: 'Envíos',
                            description: 'Controla y genera las guías y rotulados para envíos a provincia (Shalom/Olva).',
                            side: 'right',
                            align: 'start'
                        }
                    },
                    {
                        element: '#tour-plataformas',
                        popover: {
                            title: 'Plataformas',
                            description: 'Aquí administras las ventas de canales externos como Mercado Libre, Falabella, Ripley, etc.',
                            side: 'right',
                            align: 'start'
                        }
                    }
                ]
            });
            tourObj.drive();
        }

        // Asignar evento al botón del ojo
        const btnTour = document.getElementById('btn-start-tour');
        if (btnTour) {
            btnTour.addEventListener('click', startTour);
        }
    });
</script>
