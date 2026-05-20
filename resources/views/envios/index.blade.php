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
            <form action="{{ route('envios.pdf') }}" method="GET" target="_blank" class="d-inline-block me-2">
                <div class="input-group">
                    <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    <button type="submit" class="btn btn-danger shadow-sm">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Exportar PDF del Día
                    </button>
                </div>
            </form>
            <a href="{{ route('envios.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle-fill"></i> Nuevo Envío
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-3">Agencia</th>
                            <th>Guía</th>
                            <th>Clave</th>
                            <th>Destino</th>
                            <th>Cliente</th>
                            <th>Plataforma/Cuenta</th>
                            <th>Producto/Cant</th>
                            <th>Fecha</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($envios as $envio)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $envio->Agencia->nombre ?? 'N/A' }}</td>
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
                                    @foreach($envio->Productos as $envioProd)
                                        <div class="mb-1 text-truncate" style="max-width: 200px;" title="{{ $envioProd->Producto->nombreProducto ?? 'N/A' }}">
                                            • {{ $envioProd->Producto->nombreProducto ?? 'N/A' }}
                                            <span class="badge bg-secondary">x{{ $envioProd->cantidad }}</span>
                                            @if($envioProd->nota_producto)
                                                <small class="d-block text-muted ps-2" style="font-size: 0.75rem;">Nota: {{ $envioProd->nota_producto }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                            <td>{{ $envio->fecha_envio->format('d-m-Y') }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('envios.edit', $envio->idEnvioProvincia) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
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
@endsection
