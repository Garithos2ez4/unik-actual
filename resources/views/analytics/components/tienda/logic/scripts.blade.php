<script>
    if (typeof Chart !== 'undefined') {
        const canvas = document.getElementById('salesTrendChartTienda');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            
            // Si ya existe un gráfico previo, lo destruimos
            if (window.salesTrendChartTienda_instance) {
                window.salesTrendChartTienda_instance.destroy();
            }

            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(54, 162, 235, 0.5)');
            gradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

            window.salesTrendChartTienda_instance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($ventasMes, 'fecha')) !!},
                    datasets: [{
                        label: 'Ingresos S/ ',
                        data: {!! json_encode(array_column($ventasMes, 'monto')) !!},
                        borderColor: '#36A2EB',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#36A2EB',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 12,
                            titleFont: { size: 14, family: "'Inter', sans-serif" },
                            bodyFont: { size: 13, family: "'Inter', sans-serif" },
                            callbacks: {
                                label: function(context) {
                                    return 'S/ ' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { family: "'Inter', sans-serif", size: 11 }, color: '#6c757d' }
                        },
                        y: {
                            grid: { color: '#f8f9fa', drawBorder: false },
                            ticks: {
                                font: { family: "'Inter', sans-serif", size: 11 },
                                color: '#6c757d',
                                callback: function(value) { return 'S/ ' + value; }
                            },
                            beginAtZero: true
                        }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }
    }
</script>
