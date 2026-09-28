                @php
                $hasFalabella = $producto->hasMapper('falabella');
                $hasMercadoLibre = $producto->hasMapper('Mercado');
                $hasCompleto = $producto->hasTemplateCompleto('falabella');
                @endphp

                @if($hasFalabella || $hasMercadoLibre)
                <div class="btn-group me-2">
                    <button type="button" class="btn btn-success dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false" title="Descargar plantillas Excel">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                        <span class="d-none d-lg-inline"> Plantillas</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if($hasFalabella)
                        <li>
                            <h6 class="dropdown-header text-success fw-bold"><i class="bi bi-bag"></i> Falabella</h6>
                        </li>
                        @if($hasCompleto)
                        <li>
                            <a class="dropdown-item" href="{{ route('producto.falabella.template', $producto->idProducto) }}">
                                <i class="bi bi-file-earmark-excel-fill text-success me-1"></i> Template Completo
                            </a>
                        </li>
                        @endif
                        <li>
                            <a class="dropdown-item" href="#" onclick="abrirModalTitulosFbk({{ $producto->idProducto }}, '{{ addslashes($producto->nombreProducto) }}'); return false;">
                                <i class="bi bi-lightning-fill text-warning me-1"></i> Template Express
                            </a>
                        </li>
                        @endif

                        @if($hasFalabella && $hasMercadoLibre)
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        @endif

                        @if($hasMercadoLibre)
                        <li>
                            <h6 class="dropdown-header text-primary fw-bold"><i class="bi bi-shop"></i> Mercado Libre</h6>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('producto.mercadolibre.template', $producto->idProducto) }}">
                                <i class="bi bi-file-earmark-excel-fill text-primary me-1"></i> Template MercadoLibre
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif
