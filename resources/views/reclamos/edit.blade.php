@extends('layouts.app')

@section('title', 'Seguimiento de Reclamo')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <!-- Sidebar de Resumen -->
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Resumen del Caso</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="badge bg-primary fs-5">{{ $reclamo->codigoReclamo }}</span>
                    </div>
                    <hr>
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Cliente:</small><br>
                        @if($reclamo->Cliente)
                        <strong>{{ $reclamo->Cliente->nombre }} {{ $reclamo->Cliente->apellidoPaterno }}</strong>
                        @else
                        {{ $reclamo->nombresCliente ?? 'N/A' }}
                        @endif
                    </p>
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Origen / Plataforma:</small><br>
                        <span class="badge bg-secondary">{{ $reclamo->Plataforma->nombrePlataforma ?? 'N/A' }}</span>
                    </p>
                    @if($reclamo->Publicacion)
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">SKU Publicación:</small><br>
                        <span class="badge bg-info text-dark">{{ $reclamo->Publicacion->sku }}</span><br>
                        <small class="text-muted">{{ $reclamo->Publicacion->titulo }}</small>
                    </p>
                    @endif
                    @if($reclamo->ProductoFisico)
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Producto Físico:</small><br>
                        <strong>{{ $reclamo->ProductoFisico->nombreProducto }}</strong><br>
                        <small class="text-muted">S/N: {{ $reclamo->ProductoFisico->numeroSerie }}</small>
                    </p>
                    @endif
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Orden:</small><br><strong>{{ $reclamo->ordenCompra ?? 'VENTA DIRECTA' }}</strong></p>
                    <p class="mb-1"><small class="text-muted text-uppercase fw-bold">Plataforma:</small><br>{{ $reclamo->Plataforma->nombrePlataforma ?? 'N/A' }}</p>
                    <p class="mb-3"><small class="text-muted text-uppercase fw-bold">Fecha Reg:</small><br>{{ $reclamo->fechaReclamo->format('d-m-Y') }}</p>
                    <a href="{{ route('reclamos.reporte.pdf', $reclamo->idReclamoPlataforma) }}" target="_blank" class="btn btn-outline-danger w-100 fw-bold">
                        <i class="bi bi-file-earmark-pdf"></i> Generar Informe Técnico
                    </a>
                </div>
            </div>

            <!-- Estados Globales -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="{{ route('reclamos.update', $reclamo->idReclamoPlataforma) }}" method="POST">
                        @csrf
                        <label class="form-label fw-bold">Estado General</label>
                        <select name="estadoGeneral" class="form-select mb-3 border-primary">
                            <option value="ABIERTO" {{ $reclamo->estadoGeneral == 'ABIERTO' ? 'selected' : '' }}>ABIERTO</option>
                            <option value="EN ATENCION" {{ $reclamo->estadoGeneral == 'EN ATENCION' ? 'selected' : '' }}>EN ATENCION</option>
                            <option value="CERRADO" {{ $reclamo->estadoGeneral == 'CERRADO' ? 'selected' : '' }}>CERRADO</option>
                        </select>

                        <label class="form-label fw-bold">Resultado Final</label>
                        <select name="resultadoReclamo" class="form-select mb-3">
                            <option value="PENDIENTE" {{ $reclamo->resultadoReclamo == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                            <option value="FAVORABLE" {{ $reclamo->resultadoReclamo == 'FAVORABLE' ? 'selected' : '' }}>FAVORABLE</option>
                            <option value="DESFAVORABLE" {{ $reclamo->resultadoReclamo == 'DESFAVORABLE' ? 'selected' : '' }}>DESFAVORABLE</option>
                        </select>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">Actualizar Estado</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="col-lg-9">

            <!-- Tabs para organización -->
            <ul class="nav nav-tabs mb-4" id="reclamoTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">1. Información Inicial</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="seguimiento-tab" data-bs-toggle="tab" data-bs-target="#seguimiento" type="button">2. Seguimiento y Evidencia</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="diagnostico-tab" data-bs-toggle="tab" data-bs-target="#diagnostico" type="button">3. Diagnóstico Técnico</button>
                </li>
            </ul>

            <div class="tab-content" id="reclamoTabsContent">

                <!-- TAB 1: INFO INICIAL -->
                <div class="tab-pane fade show active" id="info" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Detalle / Relato del Reclamo</label>
                                    <div class="p-3 bg-light rounded border">{{ $reclamo->detalleReclamo }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('reclamos.components.tab_seguimiento')

                @include('reclamos.components.tab_diagnostico')
            </div>
        </div>
    </div>
</div>

@include('reclamos.logic.edit')
@endsection