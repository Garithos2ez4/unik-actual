@extends('layouts.app')

@section('title', 'Reclamos de Plataforma')

@section('content')
<div class="container-fluid px-4">
    <br>
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="display-6"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Reclamos Plataforma</h2>
            <p class="text-muted">Seguimiento de reclamos y discrepancias por canal de venta.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('reclamos.create') }}" class="btn btn-primary btn-lg shadow-sm">
                <i class="bi bi-plus-circle-fill"></i> Registrar Reclamo
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-3">Colaborador</th>
                            <th># Caso</th>
                            <th>Fecha / Plazo (+3d)</th>
                            <th>Producto / Estado</th>
                            <th>Plataforma / Cuenta</th>
                            <th>Orden / Compra</th>
                            <th class="text-center">Evidencia</th>
                            <th>Estado Reclamo</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reclamos as $reclamo)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                        {{ substr($reclamo->Usuario->user ?? 'U', 0, 1) }}
                                    </div>
                                    <span>{{ $reclamo->Usuario->user ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>{{ $reclamo->numeroCaso ?? '-' }}</td>
                            <td>
                                <div>{{ $reclamo->fechaReclamo->format('d-m-Y') }}</div>
                                <small class="text-danger fw-bold">{{ $reclamo->fechaReclamo->copy()->addDays(3)->format('d-m-Y') }}</small>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 180px;" title="{{ $reclamo->ProductoFisico->nombreProducto ?? '' }}">
                                    {{ $reclamo->ProductoFisico->nombreProducto ?? ($reclamo->Publicacion->titulo ?? 'N/A') }}
                                </div>
                                <small class="text-muted">{{ $reclamo->Diagnosticos->last()->respuestaSolucion ?? 'Sin diagnóstico' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $reclamo->Plataforma->nombrePlataforma ?? 'N/A' }}</span><br>
                                <small class="text-muted">{{ $reclamo->CuentaPlataforma->nombreCuenta ?? 'N/A' }}</small>
                            </td>
                            <td><small class="fw-bold">{{ $reclamo->ordenCompra }}</small></td>
                            <td class="text-center">
                                @php 
                                    $hasVideo = false; $hasFoto = false; 
                                    foreach($reclamo->Seguimientos as $seg) {
                                        if($seg->Evidencias->where('tipoEvidencia', 'VIDEO')->count() > 0) $hasVideo = true;
                                        if($seg->Evidencias->where('tipoEvidencia', 'FOTO')->count() > 0) $hasFoto = true;
                                    }
                                @endphp
                                @if($hasVideo) <i class="bi bi-camera-video-fill text-info" title="Tiene Video"></i> @endif
                                @if($hasFoto) <i class="bi bi-image-fill text-primary" title="Tiene Foto"></i> @endif
                                @if(!$hasVideo && !$hasFoto) <span class="text-muted">-</span> @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = match($reclamo->estadoGeneral) {
                                        'ABIERTO' => 'bg-danger',
                                        'EN ATENCION' => 'bg-warning text-dark',
                                        'CERRADO' => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $reclamo->estadoGeneral }}</span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('reclamos.edit', $reclamo->idReclamoPlataforma) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-2 text-muted">No se han registrado reclamos aún.</p>
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
