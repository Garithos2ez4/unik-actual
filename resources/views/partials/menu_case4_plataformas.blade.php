<li class="list-group-item bg-sistema-uno menu-border">
    <a href="{{ route('plataformas') }}" class="btn text-light">Plataformas <i class="bi bi-shop"></i></a>
    <ul class="list-unstyled ps-3 mt-1">
        <li class="mb-2 mt-1">
            <a href="{{ route('egresos.pendientes') }}" class="btn btn-sm text-warning fw-bold p-0 border-0 d-flex align-items-center w-100">
                <i class="bi bi-clock-history me-2"></i> Egresos Pendientes
            </a>
        </li>
        <li class="mb-1">
            <button class="btn btn-sm text-light fw-bold p-0 border-0 d-flex align-items-center w-100"
                type="button" data-bs-toggle="collapse" data-bs-target="#collapseFalabella"
                aria-expanded="false">
                <i class="bi bi-cart-fill me-2"></i> FALABELLA <i class="bi bi-chevron-down small ms-auto me-2"></i>
            </button>
            <ul class="collapse list-unstyled ps-3 mt-1" id="collapseFalabella">
                <li>
                    <a href="{{ route('dashboard.herramientas.scraper_falabella') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-search me-1"></i> Verificador de Precios
                    </a>
                </li>
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
        <li class="mb-1 mt-2">
            <button class="btn btn-sm text-light fw-bold p-0 border-0 d-flex align-items-center w-100"
                type="button" data-bs-toggle="collapse" data-bs-target="#collapseRipley"
                aria-expanded="false">
                <i class="bi bi-shop-window me-2"></i> RIPLEY <i class="bi bi-chevron-down small ms-auto me-2"></i>
            </button>
            <ul class="collapse list-unstyled ps-3 mt-1" id="collapseRipley">
                <li>
                    <a href="{{ route('dashboard.herramientas.scraper_ripley') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-search me-1"></i> Verificador de Precios
                    </a>
                </li>
                <li>
                    <a href="{{ route('plataformas.ripley.orders') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Órdenes
                    </a>
                </li>
                <li>
                    <a href="{{ route('plataformas.ripley.catalogo') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Publicaciones
                    </a>
                </li>
                <li>
                    <a href="{{ route('plataformas.ripley.etiquetas') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Etiquetas
                    </a>
                </li>
                <li>
                    <a href="{{ route('dashboard.analitica.ripley') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Analítica
                    </a>
                </li>
            </ul>
        </li>
        <li class="mb-1 mt-2">
            <button class="btn btn-sm text-light fw-bold p-0 border-0 d-flex align-items-center w-100"
                type="button" data-bs-toggle="collapse" data-bs-target="#collapseML"
                aria-expanded="false">
                <i class="bi bi-bag-check me-2" style="color: #FFE600;"></i> MERCADO LIBRE <i class="bi bi-chevron-down small ms-auto me-2"></i>
            </button>
            <ul class="collapse list-unstyled ps-3 mt-1" id="collapseML">
                <li>
                    <a href="{{ route('dashboard.herramientas.scraper_mercadolibre') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-search me-1"></i> Verificador de Precios
                    </a>
                </li>
                <li>
                    <a href="{{ route('plataformas.ml.publicaciones') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Catálogo y Flex
                    </a>
                </li>
                <li>
                    <a href="{{ route('plataformas.ml.orders') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Órdenes y Envíos
                    </a>
                </li>
                <li>
                    <a href="{{ route('plataformas.ml.etiquetas') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Etiquetas
                    </a>
                </li>
                <li>
                    <a href="{{ route('plataformas.ml.flex') }}" class="btn btn-sm text-info py-0">
                        <i class="bi bi-chevron-right"></i> Envíos Flex
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</li>
