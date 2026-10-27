<div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
    <div class="form-check form-switch mb-0 me-1" title="Activa para limitar el PDF solo al grupo actual en vez de toda la categoría">
        <input class="form-check-input" type="checkbox" role="switch" id="check-solo-grupo" onchange="sincronizarEnlacesPdf()">
        <label class="form-check-label small text-secondary" for="check-solo-grupo" style="cursor: pointer; font-size: 0.8rem;">
            Solo {{ $grupo['nombreGrupo'] }}
        </label>
    </div>

    <div class="dropdown d-inline-block">
        <button class="btn btn-danger btn-sm dropdown-toggle shadow-sm d-flex align-items-center gap-1" type="button" id="dropdownCatalogoPdf" data-bs-toggle="dropdown" aria-expanded="false" title="Opciones del Catálogo PDF">
            <i class="bi bi-file-earmark-pdf-fill"></i>
            <span class="fw-semibold">Catálogo PDF</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-1" aria-labelledby="dropdownCatalogoPdf" style="font-size: 0.85rem; min-width: 215px;">
            <li>
                <a class="dropdown-item py-2 d-flex align-items-center" href="#" onclick="abrirCatalogoPdf(event, 'ver')" id="btn-ver-pdf" title="Ver catálogo PDF de precios web en línea">
                    <i class="bi bi-eye-fill text-danger me-2 fs-6"></i>
                    <div>
                        <div class="fw-semibold">Ver PDF (Precios Web)</div>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">Abrir documento online</small>
                    </div>
                </a>
            </li>
            <li>
                <a class="dropdown-item py-2 d-flex align-items-center" href="#" onclick="abrirCatalogoPdf(event, 'descargar')" id="btn-descargar-pdf" title="Descargar catálogo PDF protegido">
                    <i class="bi bi-download text-primary me-2 fs-6"></i>
                    <div>
                        <div class="fw-semibold">Descargar PDF</div>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">Guardar archivo local</small>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
                <a class="dropdown-item py-2 d-flex align-items-center" href="#" onclick="copiarLinkPublicoPdf(event)" id="btn-copiar-link" title="Copiar enlace público del catálogo">
                    <i class="bi bi-link-45deg text-dark me-2 fs-5"></i>
                    <div>
                        <div class="fw-semibold">Copiar Link Público</div>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">Para compartir con clientes</small>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>
