@extends('layouts.app')
@section('title', 'Egresos Pendientes')
@section('content')

<style>
    /* =========================================
       TARJETA PRINCIPAL (Integrada al fondo blanco)
       ========================================= */
    .card-sistema {
        background-color: #ffffff;
        border: 1px solid #e0e4e8;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
    }

    /* =========================================
       CABECERAS DE TABLA (Tu azul intacto)
       ========================================= */
    th.bg-sistema-uno {
        background-color: #043e69 !important;
        color: #ffffff !important;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-bottom: 0 !important;
        padding: 15px 12px !important;
    }

    /* =========================================
       PESTAÑAS MODERNAS (Estilo Underline)
       ========================================= */
    .custom-tabs {
        border-bottom: 2px solid #eef2f5;
        gap: 15px;
        padding: 0 20px;
    }

    .custom-tabs .nav-item {
        margin-bottom: -2px;
    }

    .custom-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        padding: 15px 10px;
        font-weight: 500;
        background-color: transparent;
        transition: all 0.2s ease-in-out;
    }

    .custom-tabs .nav-link:hover:not(.active) {
        color: #333;
        border-bottom: 3px solid #d1d5db;
    }

    /* Colores corporativos en tabs activos */
    .custom-tabs .nav-link.active#falabella-tab {
        color: #e58a00;
        border-bottom: 3px solid #ff9900;
        font-weight: 700;
    }

    .custom-tabs .nav-link.active#ml-tab {
        color: #b3a100;
        border-bottom: 3px solid #FFE600;
        font-weight: 700;
    }

    .custom-tabs .nav-link.active#ripley-tab {
        color: #6C22A6;
        border-bottom: 3px solid #6C22A6;
        font-weight: 700;
    }

    /* Badges contadores dentro de los tabs */
    .custom-tabs .nav-link .badge {
        background-color: #f1f3f5 !important;
        color: #6c757d !important;
        font-weight: 600;
        transition: all 0.2s;
    }

    .custom-tabs .nav-link.active#falabella-tab .badge {
        background-color: #ff9900 !important;
        color: #fff !important;
    }

    .custom-tabs .nav-link.active#ml-tab .badge {
        background-color: #FFE600 !important;
        color: #000 !important;
    }

    .custom-tabs .nav-link.active#ripley-tab .badge {
        background-color: #6C22A6 !important;
        color: #fff !important;
    }

    /* =========================================
       ESTILOS DE TABLA CLARA
       ========================================= */
    .table-custom {
        margin-bottom: 0;
    }

    .table-custom td {
        vertical-align: middle;
        color: #333;
        border-bottom: 1px solid #f1f3f5;
        padding: 12px;
    }

    .table-custom tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge-ripley {
        background-color: #6C22A6 !important;
        color: white;
    }

    /* =========================================
       TOAST DE NOTIFICACIÓN
       ========================================= */
    #copyToast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #043e69;
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(4, 62, 105, 0.3);
        display: flex;
        align-items: center;
        gap: 12px;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 1050;
    }

    #copyToast.show {
        transform: translateY(0);
        opacity: 1;
    }
</style>

<div class="container-fluid pt-4">
    <!-- Encabezado Limpio -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-dark fw-bold m-0" style="color: #043e69 !important;">
                <i class="bi bi-clock-history text-warning me-2"></i> Egresos Pendientes
            </h3>
        </div>
    </div>

    <!-- Contenedor Principal Blanco -->
    <div class="card card-sistema border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0 pt-3">

            <!-- Pestañas -->
            <ul class="nav nav-tabs custom-tabs" id="pendientesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center" id="falabella-tab" data-bs-toggle="tab" data-bs-target="#falabella" type="button" role="tab">
                        <i class="bi bi-cart-fill me-2"></i> Falabella
                        <span class="badge rounded-pill ms-2">{{ $falabellaPendientes->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center" id="ml-tab" data-bs-toggle="tab" data-bs-target="#ml" type="button" role="tab">
                        <i class="bi bi-bag-check me-2"></i> Mercado Libre (Cta. 3)
                        <span class="badge rounded-pill ms-2">{{ $mlPendientes->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center" id="ripley-tab" data-bs-toggle="tab" data-bs-target="#ripley" type="button" role="tab">
                        <i class="bi bi-shop-window me-2"></i> Ripley
                        <span class="badge rounded-pill ms-2">{{ $ripleyPendientes->count() }}</span>
                    </button>
                </li>
            </ul>

            <!-- Contenido de las Pestañas -->
            <div class="tab-content" id="pendientesTabContent">

                <!-- Pestaña Falabella -->
                <div class="tab-pane fade show active" id="falabella" role="tabpanel">
                    @include('egresos.partials.falabella')
                </div>

                <!-- Pestaña Mercado Libre -->
                <div class="tab-pane fade" id="ml" role="tabpanel">
                    @include('egresos.partials.mercadolibre')
                </div>

                <!-- Pestaña Ripley -->
                <div class="tab-pane fade" id="ripley" role="tabpanel">
                    @include('egresos.partials.ripley')
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Div oculto para el Toast de copiado -->
<div id="copyToast">
    <i class="bi bi-check-circle-fill text-info fs-5"></i>
    <div>
        <strong class="d-block mb-1">¡Copiado con éxito!</strong>
        <span id="copyToastText" class="small text-white-50"></span>
    </div>
</div>

<script>
    // Inicializar tooltips de Bootstrap
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    // Función de copiado con Toast
    function copiarTexto(texto) {
        navigator.clipboard.writeText(texto).then(() => {
            const toast = document.getElementById('copyToast');
            const toastText = document.getElementById('copyToastText');

            toastText.textContent = "La orden " + texto + " está en el portapapeles";
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);

        }).catch(err => {
            console.error('Error al copiar: ', err);
            alert("No se pudo copiar el texto");
        });
    }
</script>

@endsection