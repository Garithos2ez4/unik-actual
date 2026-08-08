<div class="row border shadow rounded-3 pt-2 mb-4">
    <div class="col-md-12 pb-2">
        <div class="accordion accordion-flush" id="accordionAlertas">
            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="flush-headingAlertas">
                    <button class="accordion-button collapsed fs-5 fw-bold flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseAlertas" aria-expanded="false" aria-controls="flush-collapseAlertas">
                        <i class="bi bi-bell-fill text-warning me-2"></i> Alertas de Precios 
                        @if($alertas->count() > 0)
                            <span class="badge bg-danger ms-2">{{$alertas->count()}}</span>
                        @endif
                    </button>
                    <button class="btn btn-primary ms-2 me-3 my-2 text-nowrap" style="z-index: 10;" onclick="ejecutarBotPrecios(this)">
                        <i class="bi bi-robot"></i> Ejecutar Bot
                    </button>
                </h2>
                <div id="flush-collapseAlertas" class="accordion-collapse collapse" aria-labelledby="flush-headingAlertas" data-bs-parent="#accordionAlertas">
                    <div class="accordion-body">
                        <p class="text-secondary mb-3">Monitoreo de productos que requieren atención en su precio.</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Modelo</th>
                                        <th>Mi Precio</th>
                                        <th>Competidor</th>
                                        <th>Precio Competidor</th>
                                        <th>Diferencia (%)</th>
                                        <th>Sugerencia</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alertas as $alerta)
                                    <tr>
                                        <td class="fw-bold">{{$alerta->modelo}}</td>
                                        <td>S/ {{number_format($alerta->mi_precio, 2)}}</td>
                                        <td>{{$alerta->competidor}}</td>
                                        <td>S/ {{number_format($alerta->precio_competidor, 2)}}</td>
                                        <td>
                                            @if($alerta->diferencia_porcentaje < 0)
                                                <span class="text-danger fw-bold"><i class="bi bi-arrow-down-right"></i> {{$alerta->diferencia_porcentaje}}%</span>
                                            @elseif($alerta->diferencia_porcentaje > 0)
                                                <span class="text-success fw-bold"><i class="bi bi-arrow-up-right"></i> +{{$alerta->diferencia_porcentaje}}%</span>
                                            @else
                                                <span class="text-secondary fw-bold">0%</span>
                                            @endif
                                        </td>
                                        <td>{{$alerta->sugerencia}}</td>
                                        <td>
                                            <select class="form-select form-select-sm border-0 fw-bold @if($alerta->estado == 'pendiente') text-warning bg-light @elseif($alerta->estado == 'resuelto') text-success bg-light @elseif($alerta->estado == 'procesada') text-info bg-light @else text-secondary bg-light @endif" onchange="updateAlertaEstado({{$alerta->id}}, this.value)">
                                                <option value="pendiente" @if($alerta->estado == 'pendiente') selected @endif>Pendiente</option>
                                                <option value="resuelto" @if($alerta->estado == 'resuelto') selected @endif>Resuelto</option>
                                                <option value="procesada" @if($alerta->estado == 'procesada') selected @endif>Procesada</option>
                                                <option value="ignorado" @if($alerta->estado == 'ignorado') selected @endif>Ignorado</option>
                                            </select>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay alertas de precio registradas.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateAlertaEstado(id, estado) {
    fetch(`/configuracion/alerta/${id}/estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ estado: estado })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Estado actualizado',
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudo actualizar el estado', 'error');
    });
}

function ejecutarBotPrecios(btn) {
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Iniciando...';
    btn.disabled = true;

    fetch(`/configuracion/alerta/ejecutar-bot`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        
        if(data.success) {
            Swal.fire('Bot Iniciado', data.message, 'success');
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        Swal.fire('Error', 'Hubo un error al comunicarse con el servidor', 'error');
    });
}
</script>
