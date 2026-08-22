<div class="row border shadow rounded-3 pt-2 mb-4">
    <div class="col-md-12 pb-2">
        <div class="accordion accordion-flush" id="accordionMainGrupos">
            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="flush-headingMainGrupos">
                    <button class="accordion-button collapsed fs-5 fw-bold flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseMainGrupos" aria-expanded="false" aria-controls="flush-collapseMainGrupos">
                        <i class="bi bi-collection-fill text-info me-2"></i> Grupos y Categorías
                    </button>
                    <button class="btn btn-success ms-2 me-3 my-2" style="z-index: 10;" data-bs-toggle="modal" data-bs-target="#categoriaModal">
                        <i class="bi bi-plus-lg"></i> Categoría
                    </button>
                </h2>
                <div id="flush-collapseMainGrupos" class="accordion-collapse collapse" aria-labelledby="flush-headingMainGrupos" data-bs-parent="#accordionMainGrupos">
                    <div class="accordion-body">
                        <p class="text-secondary mb-3">Conjunto donde se agrupan los productos.</p>
                        <div class="accordion accordion-flush mt-2" id="accordionCategorias">
                            @php
                                $count = 0;
                            @endphp
                            @foreach ($categorias as $categoria)
                            <div class="accordion-item">
                                <h2 class="accordion-header d-flex align-items-center" id="flush-headingCat-{{$count}}">
                                    <button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseCat-{{$count}}" aria-expanded="false" aria-controls="flush-collapseCat">
                                        <i class="{{$categoria->iconCategoria}} me-2"></i> {{$categoria->nombreCategoria}}  
                                    </button>
                                    <button class="btn btn-warning me-3" style="z-index: 10;" data-bs-toggle="modal" data-bs-target="#editCategoriaModal" onclick="populateEditCategoria({{$categoria->idCategoria}}, '{{$categoria->nombreCategoria}}', '{{$categoria->iconCategoria}}')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </h2>
                                <div id="flush-collapseCat-{{$count}}" class="accordion-collapse collapse" aria-labelledby="flush-headingCat-{{$count}}" data-bs-parent="#accordionCategorias">
                                    <div class="accordion-body">
                                        <div class="row">
                                            @foreach ($categoria->GrupoProducto as $grupo)
                                            <div class="col-md-3 pb-2">
                                                <div class="row bg-light text-center border rounded-3 ms-2 me-2 position-relative h-100">
                                                    <button class="btn btn-warning btn-sm position-absolute top-0 end-0" style="width: auto; z-index: 5;" data-bs-toggle="modal" data-bs-target="#editGrupoModal" onclick="populateEditGrupo({{$grupo->idGrupoProducto}}, '{{$grupo->nombreGrupo}}', '{{$grupo->idTipoProducto}}', '{{$grupo->imagenGrupo ? asset('storage/' . $grupo->imagenGrupo) : ''}}')">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <div class="col-md-12 pt-4 pb-2">
                                                        <h6>{{$grupo->nombreGrupo}}</h6>
                                                        @if($grupo->imagenGrupo)
                                                            <img src="{{asset('storage/'. $grupo->imagenGrupo)}}" alt="" class="border rounded img-fluid mt-2" style="max-height: 100px; object-fit: contain;">
                                                        @else
                                                            <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                            <div class="col-md-3 pb-2">
                                                <div class="row bg-light text-center border rounded-3 ms-2 me-2 h-100 d-flex align-items-center justify-content-center" style="min-height: 150px;">
                                                    <button class="btn btn-success rounded-circle" style="width: 50px; height: 50px;" data-bs-toggle="modal" data-bs-target="#grupoModal" onclick="sendGrupoData('{{$categoria->idCategoria}}','{{$categoria->nombreCategoria}}')">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @php
                                $count++;
                            @endphp
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
