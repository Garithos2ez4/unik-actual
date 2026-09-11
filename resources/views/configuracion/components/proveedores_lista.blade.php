<div class="row border shadow rounded-3 pt-2 mb-4 mt-4">
    <div class="col-md-12 pb-2">
        <div class="accordion accordion-flush" id="accordionProveedores">
            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="flush-headingProveedores">
                    <button class="accordion-button collapsed fs-4 fw-bold flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseProveedores" aria-expanded="false" aria-controls="flush-collapseProveedores">
                        Proveedores
                    </button>
                    @if(isset($hasEditAccess) && $hasEditAccess)
                    <button class="btn btn-success ms-2 me-3 my-2" style="z-index: 10;" data-bs-toggle="modal" data-bs-target="#proveedorModal">
                        <i class="bi bi-plus-lg"></i> <i class="bi bi-truck"></i>
                    </button>
                    @endif
                </h2>
                <div id="flush-collapseProveedores" class="accordion-collapse collapse" aria-labelledby="flush-headingProveedores" data-bs-parent="#accordionProveedores">
                    <div class="accordion-body bg-list">
                        <p class="text-secondary mb-3">Configuracion de proveedores para los ingresos y seguimiento de stock.</p>
                        <div class="row">
                            @foreach ($proveedores as $proveedor)
                            <div class="col-md-3 pb-2">
                                <div class="row bg-light border ms-2 me-2 pt-2 h-100">
                                    <h5>{{$proveedor->nombreProveedor}}</h5>
                                    <small class="text-secondary">{{$proveedor->razSocialProveedor}}</small>
                                    <small>{{$proveedor->rucProveedor}}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
