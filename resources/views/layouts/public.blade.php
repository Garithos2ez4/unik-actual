<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Formulario de Envío - Unik Technology')</title>
    <meta name="description" content="Completa tus datos de envío para recibir tu pedido de Unik Technology.">
    <link rel="icon" href="{{ asset('storage/logos/logosysredondo.webp') }}" type="image/webp">
    <link rel="stylesheet" href="{{ asset('css/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons/bootstrap-icons.css') }}">
    <script src="{{ asset('js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert/sweetalert2.min.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --unik-primary: #0d1b2a;
            --unik-accent: #00b1b9;
            --unik-accent-hover: #009da5;
            --unik-bg: #f0f4f8;
            --unik-card: #ffffff;
            --unik-text: #1b2838;
            --unik-muted: #6c757d;
            --unik-border: #e2e8f0;
            --unik-success: #10b981;
            --unik-danger: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--unik-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--unik-text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .public-header {
            background: var(--unik-primary);
            padding: 16px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .public-header .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }

        .public-header .brand img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .public-header .brand h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.3px;
        }

        .public-header .brand small {
            color: var(--unik-accent);
            font-size: 0.75rem;
        }

        .public-main {
            flex: 1;
            padding: 20px 12px 100px;
        }

        .public-card {
            background: var(--unik-card);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--unik-border);
            overflow: hidden;
            max-width: 700px;
            margin: 0 auto;
        }

        .public-card-header {
            background: linear-gradient(135deg, var(--unik-accent), #0891b2);
            color: #fff;
            padding: 24px 20px;
            text-align: center;
        }

        .public-card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .public-card-header p {
            margin: 8px 0 0;
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .public-card-body {
            padding: 24px 20px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--unik-accent);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--unik-border);
        }

        .section-title i {
            font-size: 1.1rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--unik-text);
            margin-bottom: 4px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1.5px solid var(--unik-border);
            padding: 12px 14px;
            font-size: 1rem;
            min-height: 48px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--unik-accent);
            box-shadow: 0 0 0 3px rgba(0, 177, 185, 0.15);
        }

        .form-control::placeholder {
            color: #adb5bd;
            font-size: 0.9rem;
        }

        .required-star {
            color: var(--unik-danger);
            font-weight: 700;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--unik-accent), #0891b2);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px 32px;
            font-size: 1.05rem;
            font-weight: 700;
            width: 100%;
            min-height: 54px;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(0, 177, 185, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 177, 185, 0.4);
            color: #fff;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 8px;
        }

        .timer-badge.expired {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .public-footer {
            text-align: center;
            padding: 16px;
            color: var(--unik-muted);
            font-size: 0.78rem;
        }

        /* Mobile optimizations */
        @media (max-width: 576px) {
            .public-main {
                padding: 12px 8px 100px;
            }

            .public-card {
                border-radius: 12px;
            }

            .public-card-body {
                padding: 18px 14px;
            }

            .public-card-header {
                padding: 20px 16px;
            }

            .public-card-header h3 {
                font-size: 1.1rem;
            }

            .form-control,
            .form-select {
                font-size: 16px;
                /* Prevents zoom on iOS */
            }
        }
    </style>
</head>

<body>
    <header class="public-header">
        <div class="container">
            <div class="brand">
                <img src="{{ asset('storage/logos/logosysredondo.webp') }}" alt="Unik Technology">
                <div>
                    <h5>Unik Technology</h5>
                    <small>Formulario de Envío</small>
                </div>
            </div>
        </div>
    </header>

    <main class="public-main">
        @yield('content')
    </main>

    <footer class="public-footer">
        <p>&copy; {{ date('Y') }} Unik Technology. Todos los derechos reservados.</p>
        <p>&copy; {{ date('Y') }} luigui2ez4me@gmail.com</p>
    </footer>

    @stack('scripts')
</body>

</html>