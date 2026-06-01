@extends('layouts.public')

@section('title', '¡Datos registrados! - Unik Technology')

@section('content')
<div class="public-card" style="text-align: center;">
    <div class="public-card-header" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div style="font-size: 3.5rem; margin-bottom: 8px;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h3>¡Gracias, {{ $nombre }}!</h3>
        <p>Tus datos fueron registrados correctamente</p>
    </div>
    <div class="public-card-body" style="padding: 32px 20px;">
        <div style="max-width: 400px; margin: 0 auto;">
            <p style="font-size: 1rem; color: var(--unik-text); line-height: 1.6;">
                Hemos recibido tu información de envío. Nuestro equipo procesará tu pedido a la brevedad.
            </p>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-top: 20px;">
                <p style="margin: 0; font-size: 0.9rem; color: #166534;">
                    <i class="bi bi-info-circle-fill"></i>
                    Si necesitas hacer cambios, comunícate con tu vendedor.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
