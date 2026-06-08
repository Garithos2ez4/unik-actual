<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart !== 'undefined') {
        const canvas = document.getElementById('salesTrendChartRipley');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            
            if (window.salesTrendChartRipley_instance) {
                window.salesTrendChartRipley_instance.destroy();
            }

            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(255, 193, 7, 0.5)'); // Warning color for Ripley
            gradient.addColorStop(1, 'rgba(255, 193, 7, 0)');

            window.salesTrendChartRipley_instance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($ventasMes ?? [], 'fecha')) !!},
                    datasets: [{
                        label: 'Ingresos S/ ',
                        data: {!! json_encode(array_column($ventasMes ?? [], 'monto')) !!},
                        borderColor: '#ffc107',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#ffc107',
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
});
</script>
