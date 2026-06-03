@extends('layouts.app')

@section('title', 'Series Pendientes de Egresar (Envíos)')

@section('content')
<div class="container mt-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6 col-lg-8">
            <h2 class="mb-0">
                <a href="{{ route('egresos', date('Y-m')) }}" class="text-secondary">
                    <i class="bi bi-arrow-left-circle"></i>
                </a>
                <i class="bi bi-clock-history text-warning"></i> Pendientes de Egresar
                <span class="fs-5 text-muted">(Envíos)</span>
            </h2>
            <p class="text-muted mt-2">Productos escaneados en "Envíos a Provincias" que todavía figuran en stock y no han sido egresados del sistema.</p>
        </div>
        <div class="col-md-6 col-lg-4 text-end">
            <div class="d-flex justify-content-end mb-2 gap-2">
                <input type="date" class="form-control w-auto" id="filtro-dia" name="dia"
                    value="{{ request('dia') }}"
                    onblur="window.location.href='?dia='+this.value">
                <input type="month" class="form-control w-auto d-none d-md-block" id="month" name="month"
                    value="{{ request('month', $fecha->format('Y-m')) }}"
                    onblur="window.location.href='?month='+this.value">
            </div>
            <a href="{{ route('createegreso') }}" class="btn btn-success" target="_blank">
                <i class="bi bi-plus-lg"></i> Ir a Nuevo Egreso
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="tabla-pendientes">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" class="ps-3 text-center">Cant.</th>
                            <th scope="col">Cliente / Destino</th>
                            <th scope="col">Producto</th>
                            <th scope="col">Nro Serie</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($series as $item)
                        <tr>
                            <td class="ps-3 text-center fw-bold text-primary">
                                <span class="badge bg-primary fs-6">{{ $item['cantidad'] ?? 1 }}</span>
                                <br>
                                <small class="text-muted fw-normal" style="font-size:0.75rem;"><i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::parse($item['envio']['fecha_envio'])->format('d/m') }}</small>
                            </td>
                            <td>
                                @if(isset($item['envio']['cliente']))
                                {{ $item['envio']['cliente']['nombre'] ?? $item['envio']['cliente']['razonSocial'] ?? 'Sin Cliente' }}
                                @else
                                Sin Cliente
                                @endif
                                <br>
                                <small class="text-muted"><i class="bi bi-geo-alt-fill"></i> {{ $item['envio']['destino']['nombre'] ?? 'Sin Destino' }}</small>
                            </td>
                            <td>
                                <span class="d-inline-block text-truncate" style="max-width: 250px;" title="{{ $item['producto'] }}">
                                    {{ $item['producto'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary fs-6" id="serial-{{ $loop->index }}">{{ $item['serial'] }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="copiarSerial('{{ $item['serial'] }}', this)">
                                    <i class="bi bi-clipboard"></i> Copiar
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                                Todo al día. No hay productos pendientes de egresar desde los envíos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function copiarSerial(serial, btnElement) {
        navigator.clipboard.writeText(serial).then(() => {
            let originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="bi bi-check2"></i> Copiado!';
            btnElement.classList.replace('btn-outline-primary', 'btn-success');
            btnElement.classList.add('text-white');

            setTimeout(() => {
                btnElement.innerHTML = originalText;
                btnElement.classList.replace('btn-success', 'btn-outline-primary');
                btnElement.classList.remove('text-white');
            }, 2000);
        }).catch(err => {
            console.error('Error al copiar: ', err);
            alert('No se pudo copiar el serial. ' + err);
        });
    }
</script>
@endsection