@extends('layouts.app')

@section('title', 'Falabella Seller Center')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                            <i class="bi bi-shop fs-2"></i>
                        </div>
                        <div>
                            <h2 class="h4 mb-1">Integración Falabella.com</h2>
                            <p class="mb-0 opacity-75">Gestione sus pedidos, picking y etiquetas directamente desde aquí.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($modules as $module)
        <div class="col-12 col-sm-6 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm border-0 transition-hover">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge bg-{{ $module['priority'] == 'Critica' ? 'danger' : 'info' }} mb-2">
                            {{ $module['priority'] }}
                        </span>
                        <h5 class="card-title fw-bold text-dark">{{ $module['title'] }}</h5>
                        <p class="card-text text-muted small">{{ $module['description'] }}</p>
                    </div>
                    
                    <div class="mt-auto pt-3 d-flex align-items-center justify-content-between">
                        <span class="badge {{ $module['disabled'] ? 'bg-secondary' : 'bg-success' }} rounded-pill px-3">
                            {{ $module['status'] }}
                        </span>
                        
                        @if($module['disabled'])
                            <button class="btn btn-outline-secondary btn-sm rounded-circle" disabled>
                                <i class="bi bi-lock-fill"></i>
                            </button>
                        @else
                            <a href="{{ $module['url'] }}" class="btn btn-primary btn-sm rounded-circle shadow-sm">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
    .transition-hover {
        transition: transform 0.2s ease-in-out, shadow 0.2s ease-in-out;
    }
    .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style>
@endsection
