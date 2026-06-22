@extends('layouts.app')

@section('title', 'Envíos a Provincias')

@section('content')
<div class="container-fluid px-4">
    <br>
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="display-6"><i class="bi bi-truck text-primary"></i> Envíos a Provincias</h2>
            <p class="text-muted">Registro de despachos por agencias de transporte a nivel nacional.</p>
        </div>
        <div class="col-md-6 text-end">
            <form action="{{ route('envios.index') }}" method="GET" class="d-inline-block me-2">
                <div class="input-group">
                    <input type="date" name="fecha" id="input_fecha" class="form-control" value="{{ request('fecha', date('Y-m-d')) }}" onchange="this.form.submit()" required>
                    <div class="dropdown">
                        <button class="btn btn-danger dropdown-toggle shadow-sm" type="button" id="dropdownImprimir" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-printer-fill"></i> Imprimir / Exportar
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownImprimir">
                            <li><a class="dropdown-item" href="#" onclick="accionSeleccionados('pdf')"><i class="bi bi-file-earmark-pdf text-danger"></i> Lista PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="accionSeleccionados('lista-productos')"><i class="bi bi-card-image text-info"></i> Lista Productos (Imágenes)</a></li>
                            <li><a class="dropdown-item" href="#" onclick="accionSeleccionados('etiquetas')"><i class="bi bi-tags text-primary"></i> Etiquetas</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="accionSeleccionados('excel')"><i class="bi bi-file-earmark-excel text-success"></i> Exportar Excel</a></li>
                        </ul>
                    </div>
                </div>
            </form>
            <a href="{{ route('envios.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle-fill"></i> Nuevo Envío
            </a>
            @if($user->Accesos->contains('idVista', 14))
            <button type="button" class="btn btn-success shadow-sm" onclick="generarLinkCliente()">
                <i class="bi bi-link-45deg"></i> Generar Link Cliente
            </button>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.envio-checkbox').forEach(cb => cb.checked = this.checked)" title="Seleccionar todos">
                            </th>
                            <th>Agencia</th>
                            <th>Guía</th>
                            <th>Clave</th>
                            <th>Destino</th>
                            <th>Modalidad</th>
                            <th>Cliente</th>
                            <th>Plataforma/Cuenta</th>
                            <th>Producto/Cant</th>
                            <th>Registrado Por</th>
                            <th>Sede / Oficina</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($envios as $envio)
                        <tr>
                            <td class="ps-3">
                                <input type="checkbox" class="form-check-input envio-checkbox" value="{{ $envio->idEnvioProvincia }}">
                            </td>
                            <td class="fw-bold">{{ $envio->Agencia->nombre ?? 'N/A' }}</td>
                            <td>{{ $envio->numero_guia ?? '-' }}</td>
                            <td>{{ $envio->clave ?? '-' }}</td>
                            <td>
                                {{ $envio->Destino->nombre ?? 'N/A' }}
                                @if($envio->Detalle)
                                <br>
                                <small class="text-muted d-block text-truncate" style="max-width: 180px;" title="Dir: {{ $envio->Detalle->dir }} {{ $envio->Detalle->ref ? '| Ref: '.$envio->Detalle->ref : '' }}">
                                    <i class="bi bi-geo-alt text-primary"></i> {{ $envio->Detalle->dir }}
                                    @if($envio->Detalle->ref)
                                    <span class="text-secondary">({{ $envio->Detalle->ref }})</span>
                                    @endif
                                </small>
                                @endif
                            </td>
                            <td>
                                @if($envio->pago_destino)
                                    <span class="badge bg-warning text-dark border mb-1"><i class="bi bi-cash-coin"></i> Pago Destino</span>
                                @else
                                    <span class="badge bg-success border mb-1"><i class="bi bi-cash"></i> Pago Origen</span>
                                @endif
                                <br>
                                @if($envio->Detalle && $envio->Detalle->entrega_domicilio)
                                    <span class="badge bg-primary"><i class="bi bi-house-door-fill"></i> Domicilio</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-shop"></i> Agencia</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $envio->Cliente->nombre ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $envio->Cliente->telefono ?? '-' }} / {{ $envio->Cliente->numeroDocumento ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $envio->Plataforma->nombrePlataforma ?? 'N/A' }}</span>
                                @if($envio->CuentaPlataforma)
                                <br><small class="text-muted">{{ $envio->CuentaPlataforma->nombreCuenta }}</small>
                                @endif
                            </td>
                            <td>
                                @if($envio->Productos->isEmpty())
                                <span class="text-muted">Sin productos</span>
                                @else
                                <div class="pe-2" style="max-height: 120px; overflow-y: auto; scrollbar-width: thin;">
                                    @foreach($envio->Productos as $envioProd)
                                    <div class="mb-1 text-truncate" style="max-width: 220px;" title="{{ $envioProd->Producto->nombreProducto ?? 'N/A' }}">
                                        • {{ $envioProd->Producto->nombreProducto ?? 'N/A' }}
                                        <span class="badge bg-secondary">x{{ $envioProd->cantidad }}</span>
                                        @if($envioProd->nota_producto)
                                        <small class="d-block text-muted ps-2 text-truncate" style="font-size: 0.75rem;" title="{{ $envioProd->nota_producto }}">
                                            Nota: {{ $envioProd->nota_producto }}
                                        </small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><i class="bi bi-person-fill"></i> {{ $envio->Usuario->user ?? 'Sistema' }}</span>
                            </td>
                            <td>
                                @if($envio->SubAgencia)
                                    @php
                                        $partes = explode(' / ', $envio->SubAgencia->nombre_oficina);
                                        $nombreTerminal = end($partes);
                                    @endphp
                                     <div class="fw-bold">{{ $nombreTerminal }}</div>
                                     <small class="text-muted d-block" style="font-size: 10px; max-width: 180px; white-space: normal; word-break: break-word;">{{ $envio->SubAgencia->direccion }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('envios.edit', $envio->idEnvioProvincia) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                @if(stripos($envio->Agencia->nombre ?? '', 'flore') !== false && !empty($envio->numero_guia))
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="rastrearFlores({{ $envio->idEnvioProvincia }}, '{{ $envio->numero_guia }}')" title="Rastrear Paquete Flores">
                                    <i class="bi bi-geo-alt-fill"></i> Rastrear
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-2 text-muted">No se han registrado envíos aún.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('envios.logic.tracking-flores')

<script>
    function accionSeleccionados(tipo) {
        let checkboxes = document.querySelectorAll('.envio-checkbox:checked');
        let seleccionados = Array.from(checkboxes).map(cb => cb.value);
        let baseUrl = '';

        if (tipo === 'pdf') baseUrl = '{{ route("envios.pdf") }}';
        if (tipo === 'lista-productos') baseUrl = '{{ route("envios.listaProductos") }}';
        if (tipo === 'etiquetas') baseUrl = '{{ route("envios.etiquetas") }}';
        if (tipo === 'excel') baseUrl = '{{ route("envios.excel") }}';

        if (seleccionados.length > 0) {
            window.open(baseUrl + '?ids=' + seleccionados.join(','), tipo === 'excel' ? '_self' : '_blank');
        } else {
            let fecha = document.getElementById('input_fecha').value;
            window.open(baseUrl + '?fecha=' + fecha, tipo === 'excel' ? '_self' : '_blank');
        }
    }

    function generarLinkCliente() {
        Swal.fire({
            title: 'Generar Link para Cliente',
            text: 'Se creará un formulario público que el cliente podrá llenar. El link expira en 20 minutos.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Generar Link',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#00b1b9'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("envios.generar-link") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        navigator.clipboard.writeText(data.link).then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Link copiado!',
                                html: `<p>El link fue copiado al portapapeles.</p>
                                       <div class="input-group mt-3">
                                           <input type="text" class="form-control form-control-sm" value="${data.link}" readonly id="link-generado">
                                           <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('link-generado').value)">
                                               <i class="bi bi-clipboard"></i>
                                           </button>
                                       </div>
                                       <small class="text-muted d-block mt-2">Expira en ${data.expira_en}</small>`,
                                confirmButtonColor: '#00b1b9'
                            }).then(() => location.reload());
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function regenerarLink(idEnvio) {
        fetch(`/envios-provincias/regenerar-link/${idEnvio}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                navigator.clipboard.writeText(data.link).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Nuevo link copiado!',
                        html: `<div class="input-group mt-2">
                                   <input type="text" class="form-control form-control-sm" value="${data.link}" readonly id="link-regen">
                                   <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('link-regen').value)">
                                       <i class="bi bi-clipboard"></i>
                                   </button>
                               </div>
                               <small class="text-muted d-block mt-2">Nuevo timer: ${data.expira_en}</small>`,
                        confirmButtonColor: '#00b1b9'
                    });
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }
</script>
@endsection