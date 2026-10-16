@extends('layouts.app')
@section('title', 'Mercado Libre - Preguntas Pendientes')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-xl-6 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-chat-dots-fill me-2" style="color:#FFE600"></i>Preguntas sin responder (ML)
                    </h1>
                </div>
                <div class="col-xl-6 col-lg-12 text-end">
                    <button class="btn btn-dark fw-bold shadow-sm px-4" onclick="loadPreguntasDetalle()">
                        <i class="bi bi-arrow-repeat"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="preguntas-container" class="row">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted fw-bold">Cargando preguntas de Mercado Libre...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('plataformas.mercadolibre.logic.preguntas')
@endpush