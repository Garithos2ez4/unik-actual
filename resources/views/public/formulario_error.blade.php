@extends('layouts.public')

@section('title', '{{ $titulo }} - Unik Technology')

@section('content')
<div class="public-card" style="text-align: center;">
    <div class="public-card-header" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
        <div style="font-size: 3.5rem; margin-bottom: 8px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3>{{ $titulo }}</h3>
    </div>
    <div class="public-card-body" style="padding: 32px 20px;">
        <div style="max-width: 400px; margin: 0 auto;">
            <p style="font-size: 1rem; color: var(--unik-text); line-height: 1.6;">
                {{ $mensaje }}
            </p>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; margin-top: 20px;">
                <p style="margin: 0; font-size: 0.9rem; color: #991b1b;">
                    <i class="bi bi-telephone-fill"></i>
                    Contacta a tu vendedor de Unik Technology para obtener ayuda.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
