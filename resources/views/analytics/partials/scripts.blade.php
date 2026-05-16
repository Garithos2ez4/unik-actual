<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('salesTrendChartPage');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const labels = @json(collect($ventasMes)->pluck('fecha'));
        const dataMonto = @json(collect($ventasMes)->pluck('monto'));
        const dataUnidades = @json(collect($ventasMes)->pluck('total'));

        // Gradiente para el fondo
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(0, 177, 185, 0.3)');
        gradient.addColorStop(1, 'rgba(0, 177, 185, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: dataMonto,
                    borderColor: '#00b1b9',
                    backgroundColor: gradient,
                    borderWidth: 4,
                    fill: true,
                    tension: 0.45,
                    pointRadius: 6,
                    pointHoverRadius: 9,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#00b1b9',
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
