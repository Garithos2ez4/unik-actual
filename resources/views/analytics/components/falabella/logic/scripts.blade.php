<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('salesTrendChartFalabella');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const labels = @json(collect($ventasMes)->pluck('fecha'));
        const dataMonto = @json(collect($ventasMes)->pluck('monto'));
        const dataUnidades = @json(collect($ventasMes)->pluck('total'));

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(255, 193, 7, 0.3)'); // Amarillo (warning) para Falabella
        gradient.addColorStop(1, 'rgba(255, 193, 7, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos Falabella (S/)',
                    data: dataMonto,
                    borderColor: '#ffc107',
                    backgroundColor: gradient,
                    borderWidth: 4,
                    fill: true,
                    tension: 0.45,
                    pointRadius: 6,
                    pointHoverRadius: 9,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#ffc107',
                    pointBorderWidth: 3,
                    cubicInterpolationMode: 'monotone'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2d3436',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const units = dataUnidades[context.dataIndex];
                                return [
                                    ' Ingreso: S/ ' + context.parsed.y.toLocaleString('es-PE', { minimumFractionDigits: 2 }),
                                    ' Unidades: ' + units
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' }, color: '#636e72' }
                    },
                    y: { 
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false },
                        ticks: { 
                            color: '#636e72',
                            callback: (value) => 'S/ ' + value.toLocaleString()
                        }
                    }
                }
            }
        });
    });


</script>
