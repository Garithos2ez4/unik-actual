<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Fetch Top Provincias
        fetch('{{ route("dashboard.analitica.envios.provincias") }}' + window.location.search)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#tabla-top-provincias tbody');
                tbody.innerHTML = ''; // Limpiar loader
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay datos disponibles</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">${index + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.provincia}</div>
                                    <small class="text-muted">${item.destino}</small>
                                </td>
                                <td class="text-end pe-4 fw-bold text-primary">${item.total}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error fetching top provincias:', error));

        // Fetch Top Clientes
        fetch('{{ route("dashboard.analitica.envios.clientes") }}' + window.location.search)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#tabla-top-clientes tbody');
                tbody.innerHTML = ''; // Limpiar loader
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay datos disponibles</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">${index + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.cliente}</div>
                                    <small class="text-muted">Doc: ${item.documento}</small>
                                </td>
                                <td class="text-end pe-4 fw-bold text-info">${item.total}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error fetching top clientes:', error));
            
        // Fetch Top Agencias
        fetch('{{ route("dashboard.analitica.envios.agencias") }}' + window.location.search)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#tabla-top-agencias tbody');
                tbody.innerHTML = ''; // Limpiar loader
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay datos disponibles</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">${index + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.agencia}</div>
                                    <span class="badge ${item.estado === 'Activo' ? 'bg-success' : 'bg-secondary'}">${item.estado}</span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-warning">${item.total}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error fetching top agencias:', error));
            
        // Fetch Top Usuarios
        fetch('{{ route("dashboard.analitica.envios.usuarios") }}' + window.location.search)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#tabla-top-usuarios tbody');
                tbody.innerHTML = ''; // Limpiar loader
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay datos disponibles</td></tr>';
                } else {
                    data.forEach((item, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">${index + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.usuario}</div>
                                    <span class="badge ${item.estado === 'Activo' ? 'bg-success' : 'bg-secondary'}">${item.estado}</span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-primary">${item.total}</td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error fetching top usuarios:', error));

        // Inicializar gráfico de tendencias
        const enviosData = @json($enviosMes);
        
        if (enviosData && enviosData.length > 0 && document.getElementById('enviosTrendChartPage')) {
            const ctxEnvios = document.getElementById('enviosTrendChartPage').getContext('2d');
            
            const labels = enviosData.map(item => item.fecha);
            const dataTotal = enviosData.map(item => item.total);

            // Crear gradiente
            let gradient = ctxEnvios.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(54, 162, 235, 0.5)');   
            gradient.addColorStop(1, 'rgba(54, 162, 235, 0.05)');

            new Chart(ctxEnvios, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Envíos',
                        data: dataTotal,
                        borderColor: '#36a2eb',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#36a2eb',
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
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' envíos';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11 },
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11 },
                                maxTicksLimit: 15
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                }
            });
        }
            
    });
</script>
