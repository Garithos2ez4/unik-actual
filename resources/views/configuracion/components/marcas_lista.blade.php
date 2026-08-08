<div class="row border shadow rounded-3 pt-2 mb-4">
    <div class="col-md-12 pb-2">
        <div class="accordion accordion-flush" id="accordionMarcas">
            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="flush-headingMarcas">
                    <button class="accordion-button collapsed fs-5 fw-bold flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseMarcas" aria-expanded="false" aria-controls="flush-collapseMarcas">
                        <i class="bi bi-tags-fill text-primary me-2"></i> Marcas
                    </button>
                    <button class="btn btn-success ms-2 me-3 my-2" style="z-index: 10;" data-bs-toggle="modal" data-bs-target="#marcaModal">
                        <i class="bi bi-bookmark-plus-fill"></i>
                    </button>
                </h2>
                <div id="flush-collapseMarcas" class="accordion-collapse collapse" aria-labelledby="flush-headingMarcas" data-bs-parent="#accordionMarcas">
                    <div class="accordion-body">
                        <div class="row bg-list pt-2">
                            @foreach ($marcas->sortBy('nombreMarca') as $marca)
                            <div class="col-md-2 pb-2">
                                <div class="row bg-light text-center border rounded-3 ms-2 me-2 pt-2 h-100 align-items-center">
                                    <h5 class="mb-2">{{$marca->nombreMarca}}</h5>
                                    @if($marca->imagenMarca)
                                        <img src="{{asset('storage/'. $marca->imagenMarca)}}" alt="{{$marca->nombreMarca}}" class="border ps-0 pe-0 mb-2" style="max-height: 80px; object-fit: contain;">
                                    @else
                                        <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                    @endif
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
