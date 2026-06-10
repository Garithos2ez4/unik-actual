@extends('layouts.app')

@section('title', 'Reviews')

@section('content')
<div class="container">
    <br>
    <div class="row align-items-center mb-3">
        <div class="col-12 col-md-6">
            <h2><i class="bi bi-star-half text-warning"></i> Moderación de Reviews</h2>
        </div>
    </div>
    <div class="row">
        @if($reviews->count() < 1)
            <div class="col-12 d-flex justify-content-center align-items-center" style="height: 70vh">
                <x-aviso_no_encontrado :mensaje="'reviews pendientes'" />
            </div>
        @else
            <div class="col-12">
                <ul class="list-group">
                    <li class="list-group-item bg-sistema-uno text-light">
                        <div class="row text-center">
                            <div class="col-2 text-start">
                                <h6 class="mt-1">Cliente</h6>
                            </div>
                            <div class="col-2">
                                <h6 class="mt-1">Producto</h6>
                            </div>
                            <div class="col-1">
                                <h6 class="mt-1">Puntuación</h6>
                            </div>
                            <div class="col-3">
                                <h6 class="mt-1">Comentario</h6>
                            </div>
                            <div class="col-1">
                                <h6 class="mt-1">Archivos</h6>
                            </div>
                            <div class="col-1">
                                <h6 class="mt-1">Estado</h6>
                            </div>
                            <div class="col-2">
                                <h6 class="mt-1">Acciones</h6>
                            </div>
                        </div>
                    </li>
                    @foreach ($reviews as $review)
                        <li class="list-group-item">
                            <div class="row text-center align-items-center">
                                <div class="col-2 text-start text-truncate">
                                    <small class="fw-bold">{{ $review->cliente ? $review->cliente->nombre . ' ' . $review->cliente->apellidoPaterno : 'Cliente no encontrado' }}</small>
                                    <br>
                                    <small class="text-muted">{{ $review->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <div class="col-2 text-truncate">
                                    <small title="{{ $review->producto ? $review->producto->nombreProducto : 'Producto no encontrado' }}">
                                        {{ $review->producto ? $review->producto->nombreProducto : 'N/A' }}
                                    </small>
                                </div>
                                <div class="col-1">
                                    <span class="text-warning">
                                        @for($i=1; $i<=5; $i++)
                                            @if($i <= $review->calificacion)
                                                <i class="bi bi-star-fill"></i>
                                            @else
                                                <i class="bi bi-star"></i>
                                            @endif
                                        @endfor
                                    </span>
                                </div>
                                <div class="col-3 text-start">
                                    <small style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;" title="{{ $review->comentario }}">
                                        {{ $review->comentario }}
                                    </small>
                                </div>
                                <div class="col-1">
                                    @if($review->imagen_setup)
                                        <a href="http://127.0.0.1:8001/storage/{{ $review->imagen_setup }}" target="_blank" class="btn btn-sm btn-outline-info" title="Ver Imagen">
                                            <i class="bi bi-image"></i>
                                        </a>
                                    @endif
                                    @if($review->video_url)
                                        <a href="{{ $review->video_url }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Ver Video">
                                            <i class="bi bi-youtube"></i>
                                        </a>
                                    @endif
                                    @if(!$review->imagen_setup && !$review->video_url)
                                        <small class="text-muted">-</small>
                                    @endif
                                </div>
                                <div class="col-1">
                                    @if($review->estado == 0)
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @elseif($review->estado == 1)
                                        <span class="badge bg-success">Aprobado</span>
                                    @else
                                        <span class="badge bg-danger">Rechazado</span>
                                    @endif
                                </div>
                                <div class="col-2">
                                    <form action="{{ route('reviews.estado', $review->idReview) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="estado" value="aprobado">
                                        <button type="submit" class="btn btn-sm btn-success" title="Aprobar" {{ $review->estado == 1 ? 'disabled' : '' }}>
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('reviews.estado', $review->idReview) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="estado" value="rechazado">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Rechazar" {{ $review->estado == 2 ? 'disabled' : '' }}>
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
