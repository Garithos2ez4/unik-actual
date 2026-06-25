<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tittulo por defecto')</title>
    <link rel="icon" href="{{ asset('storage/logos/logosysredondo.webp') }}" type="image/webp">
    <link rel="stylesheet" href="{{ asset('css/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/boxicons/boxicons.min.css') }}">
    <!-- Added CDN to support newer icons like bi-openai -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/quill.css') }}">

    <script src="{{ asset('js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/sheetjs/xlsx.full.min.js') }}"></script>
    <script src="{{ asset('js/quill.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/header-scripts.js') }}?v=1.02"></script>

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        .ts-dropdown {
            z-index: 9999 !important;
        }
    </style>
</head>

<body style="position: relative">
    @if (session('title') && session('message') && session('icon') && session('button'))
    <script>
        Swal.fire({
            title: @json(session('title').
                '!'),
            text: @json(session('message')),
            icon: "{{ session('icon') }}",
            iconColor: '#00b1b9',
            confirmButtonText: 'Aceptar',
            customClass: {
                confirmButton: "{{ session('button') }}"
            }
        });
    </script>
    @endif

    <header style="position:sticky;top:0;z-index:1001">
        <div class="text-light bg-sistema-uno">
            <div class="container">
                <div class="row" style="z-index:1000">
                    <div class="col-2 col-lg-1">
                        <a class="btn text-light hidden-button" data-bs-toggle="offcanvas" href="#offcanvasDashboard"
                            role="button" aria-controls="offcanvasDashboard">
                            <i class="bi bi-list" style="font-size:2rem"></i>
                        </a>
                    </div>
                    <div class="col-4 col-lg-9 d-flex justify-content-start align-items-center">
                        <img class="d-none d-lg-block" alt="logo" src="{{ asset('storage/logos/logosysfondo.webp') }}"
                            style="width:50px">
                        <h5 class="d-none d-lg-flex justify-content-start align-items-center mb-0 h-100">Unik Technology
                            &nbsp;<span class="text-secondary"> </span></h5>
                    </div>
                    <div class="col-6 col-lg-2" style="position:relative;z-index:9000">
                        <div class="row h-100 d-flex align-items-center text-end pt-2" id="header-user-nav"
                            style="cursor:pointer">
                            @php
                            $bandejaText = trim(strip_tags(str_replace('&nbsp;', '', $user->bandeja)));
                            $bandejaHash = md5($bandejaText);
                            $cookieName = 'bandeja_read_' . $user->idUser;
                            $cookieValue = $_COOKIE[$cookieName] ?? null;
                            $hasPendientes = !empty($bandejaText) && $cookieValue !== $bandejaHash;
                            @endphp
                            <h5 class="w-100"><i class="bi bi-person-circle"></i> {{ $user->user }}
                                @if($hasPendientes)
                                <span class="badge bg-danger rounded-circle p-1 ms-1" style="font-size: 0.5rem;" title="Tienes pendientes asignados" id="nav-alert-icon">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                </span>
                                @endif
                            </h5>
                        </div>
                        <div class="border shadow pt-2 pb-3 rounded-3 bg-light"
                            style="position:absolute;width:100%;left:-10%;z-index:9000;display:none" id="options-user">
                            <div class="row text-dark text-center">
                                <div class="col-md-12 mt-1">
                                    <small>
                                        <a href="#" id="nav-pendientes-link"
                                            onclick="openMyPendientes('{{ $user->idUser }}', '{{ route('getbandeja') }}', '{{ $bandejaHash }}'); return false;"
                                            class=" text-decoration-none {{ $hasPendientes ? 'text-danger fw-bold' : 'text-secondary' }} link-hover"
                                            data-bs-toggle="modal" data-bs-target="#modalBandeja">
                                            <i class="bi bi-journal-bookmark-fill"></i> Pendientes
                                            @if(!empty($hasPendientes))
                                            <i class="bi bi-exclamation-circle-fill ms-1" id="menu-alert-icon"></i>
                                            @endif
                                        </a>
                                    </small>
                                </div>
                                <div class="col-md-12 mt-1">
                                    <small>
                                        <a href="#"
                                            onclick="getIdPass('{{ $user->idUser }}','id-modal-password'); return false;"
                                            class="text-decoration-none text-secondary link-hover"
                                            data-bs-toggle="modal" data-bs-target="#modalNewPass">
                                            <i class="bi bi-arrow-clockwise"></i> Reestablecer Contrase&ntildea
                                        </a>
                                    </small>
                                </div>
                                <div class="col-md-12 mt-1">
                                    <small><a href="{{ route('login') }}"
                                            class="text-danger text-decoration-none link-hover"><i
                                                class="bi bi-escape"></i> Cerrar Sesi&oacuten</a></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="div-alerts-bootstrap"></div>
    </header>
    <nav>
        <div class="offcanvas offcanvas-start bg-sistema-uno" tabindex="-1" id="offcanvasDashboard"
            aria-labelledby="offcanvasExampleLabel">
            <div class="offcanvas-header">
                <div class="d-flex justify-content-start d-sm-none">
                    <img alt="logo" src="{{ asset('storage/logos/logosysfondo.webp') }}" style="width:50px">
                    <div class="row d-block">
                        <h5 class="mb-0 text-light">Unik Technology
                        </h5>
                        <small class="text-secondary">v1.5</small>
                    </div>
                </div>
                <h5 class="d-none d-sm-flex offcanvas-title text-light">Men&uacute;</h5>
                <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body ">
                <ul class="list-group list-group-flush ">
                    <li class="list-group-item bg-sistema-uno menu-border"><a href="{{ route('dashboard') }}"
                            class="btn text-light">Dashboard <i class="bi bi-house-fill"></i></a></li>
                    <li class="list-group-item bg-sistema-uno menu-border"><a href="{{ route('calculadora') }}"
                            class="btn text-light">Calculadora <i class="bi bi-calculator"></i></a></li>
                    @php
                    $order = [2, 1, 4, 11, 12, 5, 3, 6, 7, 15, 16]; // Orden personalizado, 11 es Reclamos, 12 es Envios, 16 es Reviews
                    @endphp

                    @foreach ($order as $idVista)
                    @php
                    $access = $user->Accesos->firstWhere('idVista', $idVista);
                    @endphp
                    @if ($access)
                    @switch($access->idVista)
                    @case(1)
                    <li class="list-group-item bg-sistema-uno menu-border"><a
                            href="{{ route('publicaciones', [now()->format('Y-m')]) }}"
                            class="btn text-light">Publicaciones <i class="bi bi-megaphone-fill"></i></a></li>
                    @break

                    @case(2)
                    <li class="list-group-item bg-sistema-uno menu-border"><a
                            href="{{ route('productos', [encrypt(1), encrypt(1)]) }}" class="btn text-light">Productos
                            <i class="bi bi-box-fill"></i></a></li>
                    <li class="list-group-item bg-sistema-uno menu-border"><a
                            href="{{ route('documentos', [now()->format('Y-m')]) }}" class="btn text-light">Registros <i
                                class="bi bi-folder-fill"></i></a></li>
                    @break

                    @case(4)
                    <li class="list-group-item bg-sistema-uno menu-border">
                        <a href="{{ route('plataformas') }}" class="btn text-light">Plataformas <i class="bi bi-shop"></i></a>
                        <ul class="list-unstyled ps-3 mt-1">
                            <li class="mb-1">
                                <button class="btn btn-sm text-light fw-bold p-0 border-0 d-flex align-items-center w-100"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapseFalabella"
                                    aria-expanded="false">
                                    <i class="bi bi-cart-fill me-2"></i> FALABELLA <i class="bi bi-chevron-down small ms-auto me-2"></i>
                                </button>
                                <ul class="collapse list-unstyled ps-3 mt-1" id="collapseFalabella">
                                    <li>
                                        <a href="{{ route('plataformas.falabella.orders') }}" class="btn btn-sm text-info py-0">
                                            <i class="bi bi-chevron-right"></i> Sincronización
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('plataformas.falabella.productos') }}" class="btn btn-sm text-info py-0">
                                            <i class="bi bi-chevron-right"></i> Catálogo
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('plataformas.falabella.publicaciones') }}" class="btn btn-sm text-info py-0">
                                            <i class="bi bi-chevron-right"></i> Publicaciones
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('plataformas.falabella.picking') }}" class="btn btn-sm text-info py-0">
                                            <i class="bi bi-chevron-right"></i> Lista de Picking
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('plataformas.falabella.etiquetas') }}" class="btn btn-sm text-info py-0">
                                            <i class="bi bi-chevron-right"></i> Etiquetas
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('plataformas.falabella.devoluciones') }}" class="btn btn-sm text-info py-0">
                                            <i class="bi bi-chevron-right"></i> Devoluciones
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    @break

                    @case(5)
                    <li class="list-group-item bg-sistema-uno menu-border"><a href="{{ route('publicidad') }}"
                            class="btn text-light">Web <i class="bi bi-globe"></i></a></li>
                    @break

                    @case(3)
                    <li class="list-group-item bg-sistema-uno menu-border"><a href="{{ route('clientes') }}"
                            class="btn text-light">Clientes <i class="bi bi-person-standing"></i></a></li>
                    @break

                    @case(6)
                    <li class="list-group-item bg-sistema-uno menu-border"><a href="{{ route('usuarios') }}"
                            class="btn text-light">Usuarios <i class="bi bi-person-fill"></i></a></li>
                    @break

                    @case(7)
                    <li class="list-group-item bg-sistema-uno menu-border"><a href="{{ route('configweb') }}"
                            class="btn text-light">Configuraci&oacuten <i class="bi bi-gear-fill"></i></a></li>
                    @break
                    @case(11)
                    <li class="list-group-item bg-sistema-uno menu-border">
                        <a href="{{ route('reclamos.index') }}" class="btn text-light w-100 text-start">Reclamos <i class="bi bi-exclamation-triangle-fill text-warning"></i></a>
                        <ul class="list-unstyled ps-3">
                            <li>
                                <a href="{{ route('reclamos.historia') }}" class="btn btn-sm text-info py-0">
                                    <i class="bi bi-clock-history"></i> Historial Global
                                </a>
                            </li>
                        </ul>
                    </li>
                    @break
                    @case(12)
                    <li class="list-group-item bg-sistema-uno menu-border">
                        <a href="{{ route('envios.index') }}" class="btn text-light w-100 text-start">Envíos <i class="bi bi-truck "></i></a>
                    </li>
                    @break
                    @case(15)
                    <li class="list-group-item bg-sistema-uno menu-border">
                        <a href="{{ route('colaboradores') }}" class="btn text-light">Colaboradores <i class="bi bi-people-fill"></i></a>
                    </li>
                    @break

                    @case(16)
                    <li class="list-group-item bg-sistema-uno menu-border">
                        <a href="{{ route('reviews.index') }}" class="btn text-light">Reviews <i class="bi bi-star-half text-warning"></i></a>
                    </li>
                    @break

                    @endswitch
                    @endif
                    @endforeach

                </ul>
            </div>
            <small style="color: rgba(255, 255, 255, 0.137)">By Leonardo MH</small>
            <small style="color: rgba(255, 255, 255, 0.137)">By Luigui CO</small>
            <small style="color: rgba(255, 255, 255, 0.137)">By Alonso DLCR</small>
        </div>
    </nav>
    <main class="content" style="position: relative">

        @yield('content')

        <!-- Modal -->
        <form action="{{ route('updatepass') }}" method="POST">
            @csrf
            <div class="modal fade" id="modalNewPass" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title " id="modalNewPassLabel">Reestablecer contraseña</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <input type="hidden" name="id" value="" class="form-control" id="id-modal-password">
                                    <label class="form-label">Nueva contraseña</label>
                                    <input type="password" name="pass" class="form-control" id="pass-modal-password">
                                    <small id="passwordError" class="text-danger"></small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Confirmar contraseña</label>
                                    <input type="password" name="confirmpass" class="form-control"
                                        id="confirmpass-modal-password">
                                    <small id="confirmPasswordError" class="text-danger"></small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="cancelarModal()"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" id="btn-reestablecer-modal-password" class="btn btn-primary"><i
                                    class="bi bi-arrow-clockwise"></i> Reestablecer</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <form action="{{ route('updatebandeja') }}" method="post" id="form-update-bandeja">
            @csrf
            <div class="modal fade" id="modalBandeja" tabindex="-1" aria-labelledby="bandejaModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" style="position: relative">
                    <div class="h-100 w-100 bg-transparent align-items-center justify-content-center"
                        id="modalBandeja-total-body" style="position: absolute;z-index:1000;display:none">
                        <div class="spinner-border text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="text-secondary">&nbsp;Guardando...</h5>
                    </div>
                    <div class="modal-content ">
                        <div class="modal-header">
                            <h5 class="modal-title " id="bandejaModalLabel">Pendientes</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row" style="height: 300px">
                                <div class="col-md-12 h-100">
                                    <input type="hidden" name="id" value="" class="form-control" id="id-modal-bandeja">
                                    <input type="hidden" name="bandeja" id="bandeja-input" value="">
                                    <div id="text-bandeja" class="row h-75" style="width: 100%; overflow-y: auto;">
                                        {!! $user->bandeja !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill"></i>
                                Actualizar</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
    <footer>
    </footer>
    @stack('scripts')
    <script src="{{ asset('js/app.js') }}?v=1.03"></script>
</body>

</html>