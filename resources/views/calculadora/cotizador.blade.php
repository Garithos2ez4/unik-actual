@extends('layouts.app')

@section('title', 'Cotizador de Publicaciones')

@section('content')
<style>
    /* Estilos para el cotizador */
    .table-cotizador input {
        text-align: right;
    }
    .table-cotizador .remove-row {
        cursor: pointer;
        color: #dc3545;
    }
    .table-cotizador .remove-row:hover {
        color: #a71d2a;
    }
    .product-result-item {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .product-result-item:hover {
        background-color: #f8f9fa;
    }
    .totales-box {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #dee2e6;
    }
    .platform-card {
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    .platform-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="row mb-3 align-items-center">
        <div class="col-md-5">
            <h2><i class="bi bi-calculator"></i> Cotizador de Publicaciones</h2>
        </div>
        <div class="col-md-2 text-center">
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input" type="checkbox" id="switch-modo-combo" style="transform: scale(1.3);">
                <label class="form-check-label ms-2 fw-bold" for="switch-modo-combo">Armar Combo</label>
            </div>
        </div>
        <div class="col-md-5 text-end">
            <div class="d-flex justify-content-end align-items-center">
                <h5 class="text-secondary mb-0 me-2">T.C:</h5>
                <input type="number" id="input-tasa-cambio" class="form-control form-control-sm text-center fw-bold text-primary border-primary" style="width: 70px;" step="0.01" min="1" value="{{$valores->tasaCambio}}">
                <button class="btn btn-sm btn-outline-secondary ms-2" id="btn-consultar-tc" onclick="consultarTipoCambio()" title="Consultar SUNAT en tiempo real"><i class="bi bi-arrow-clockwise"></i></button>
                
                <h5 class="text-secondary mb-0 ms-4">IGV: <span class="fw-bold text-dark">{{$valores->igv}}</span>%</h5>
                <h5 class="text-secondary mb-0 ms-3">Facturación: <span class="fw-bold text-dark">{{$valores->facturacion}}</span>%</h5>
            </div>
        </div>
    </div>

    <!-- Buscador de productos general -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body pb-2">
                    <h5 class="card-title mb-3"><i class="bi bi-search"></i> Buscar Producto (Opcional)</h5>
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                            <input type="text" class="form-control form-control-lg" id="search-producto" placeholder="Escribe el modelo, nombre o SKU del producto..." autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="btn-clear-search"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <!-- Dropdown de resultados -->
                        <div id="search-results" class="position-absolute w-100 bg-white border rounded shadow-sm" style="display: none; z-index: 1000; max-height: 400px; overflow-y: auto; top: 100%; left: 0; margin-top: 5px;">
                            <!-- Aquí se inyectan los resultados con JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- COLUMNA IZQUIERDA: MODO INDIVIDUAL O MODO COMBO -->
        <div class="col-xl-8 mb-4">
            
            <!-- MODO INDIVIDUAL -->
            <div id="modo-individual" class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title mb-4"><i class="bi bi-laptop"></i> Producto Individual</h5>
                    
                    <div class="mb-4 text-center">
                        <div class="d-flex justify-content-center align-items-center">
                            <h4 id="indiv-nombre-producto" class="text-primary mb-1">Cotización Manual</h4>
                            <button class="btn btn-sm btn-outline-danger ms-2 d-none" id="btn-remover-prod-indiv" title="Quitar selección y cotizar manual"><i class="bi bi-x-circle"></i></button>
                        </div>
                        <div id="indiv-detalles-producto" class="text-muted small">Ingresa el costo manualmente o busca un producto arriba.</div>
                    </div>

                    <div class="row g-3 justify-content-center">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Costo del Producto</label>
                            <div class="input-group">
                                <select class="form-select" id="indiv-moneda" style="max-width: 80px;">
                                    <option value="USD">$</option>
                                    <option value="PEN">S/</option>
                                </select>
                                <input type="number" class="form-control form-control-lg input-indiv" id="indiv-costo" value="0.00" step="0.01" min="0">
                            </div>
                            <small class="text-muted">Con IGV</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Flete S/</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" class="form-control form-control-lg input-indiv text-secondary" id="indiv-flete" value="0.00" step="1" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ganancia S/</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" class="form-control form-control-lg input-indiv text-success" id="indiv-ganancia" value="0.00" step="1" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resumen Individual -->
                    <div class="totales-box mt-auto mt-4">
                        <div class="row align-items-center text-center">
                            <div class="col-md-4 border-end">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Costo (Con IGV)</div>
                                <h4 class="mb-0 text-dark">S/ <span id="indiv-total-soles">0.00</span></h4>
                            </div>
                            <div class="col-md-8">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Costo Base Total (S/ + Flete + Ganancia)</div>
                                <h3 class="mb-0 fw-bold text-primary" id="indiv-total-base">0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODO COMBO -->
            <div id="modo-combo" class="card shadow-sm h-100 d-none">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><i class="bi bi-box-seam"></i> Componentes del Combo</h5>
                        <button class="btn btn-sm btn-danger d-none" id="btn-clear-combo"><i class="bi bi-trash"></i> Limpiar Todo</button>
                    </div>
                    
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-hover table-bordered table-cotizador align-middle mb-0" id="table-combo">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th style="width: 40px;"></th>
                                    <th>Producto</th>
                                    <th style="width: 80px;">Cant.</th>
                                    <th style="width: 110px;">Costo $ <br><small class="text-muted fw-normal">(Sin IGV)</small></th>
                                    <th style="width: 100px;">Flete S/</th>
                                    <th style="width: 100px;">Gan. S/</th>
                                    <th style="width: 110px;">Costo S/ <br><small class="text-muted fw-normal">(Con IGV)</small></th>
                                </tr>
                            </thead>
                            <tbody id="combo-body">
                                <tr id="empty-combo-row">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-light"></i>
                                        Aún no has agregado productos al combo. Busca un producto arriba.
                                    </td>
                                </tr>
                                <!-- Filas agregadas dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Resumen Totales (Base) -->
                    <div class="totales-box mt-3 d-none" id="totales-box">
                        <div class="row align-items-center">
                            <div class="col-md-3 border-end">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Costo Productos (Sin IGV)</div>
                                <h4 class="mb-0 text-primary">$ <span id="total-usd">0.00</span></h4>
                            </div>
                            <div class="col-md-3 border-end">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Costo Base S/ (Con IGV)</div>
                                <h4 class="mb-0 text-dark">S/ <span id="total-soles">0.00</span></h4>
                            </div>
                            <div class="col-md-2 border-end text-center">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Flete Total S/</div>
                                <h5 class="mb-0 text-secondary" id="total-flete">0.00</h5>
                            </div>
                            <div class="col-md-2 border-end text-center">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Ganancia S/</div>
                                <h5 class="mb-0 text-success" id="total-ganancia">0.00</h5>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Total (Sin Comis.)</div>
                                <h5 class="mb-0 fw-bold" id="total-base-sin-comision">0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Simulación Plataformas -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow-sm h-100 bg-light border-0">
                <div class="card-body">
                    <h5 class="card-title mb-4"><i class="bi bi-graph-up"></i> Precio Venta Sugerido</h5>
                    
                    <div id="simulador-empty" class="text-center text-muted py-5">
                        <i class="bi bi-calculator fs-1 d-block mb-2 text-secondary opacity-25"></i>
                        Agrega productos para ver la simulación de precios por plataforma.
                    </div>

                    <div id="simulador-plataformas" class="d-none">
                        <!-- Precios de venta generados por JS -->
                        <div class="accordion" id="accordionPlataformas">
                            @foreach($plataformas as $index => $plata)
                            @php
                                $plataformaKey = strtoupper(trim($plata->nombrePlataforma));
                                $reglasPlata = isset($reglasComisiones) ? $reglasComisiones->where('plataforma', $plataformaKey) : collect();
                            @endphp
                            <div class="accordion-item mb-2 border rounded shadow-sm">
                                <h2 class="accordion-header" id="heading-{{$plata->idPlataforma}}">
                                    <button class="accordion-button {{$index !== 0 ? 'collapsed' : ''}} py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{$plata->idPlataforma}}" aria-expanded="{{$index === 0 ? 'true' : 'false'}}" aria-controls="collapse-{{$plata->idPlataforma}}">
                                        <div class="d-flex align-items-center w-100 pe-3">
                                            @if($plata->imagenPlataforma)
                                                <img src="{{asset('storage/'.$plata->imagenPlataforma)}}" alt="{{$plata->nombrePlataforma}}" style="height: 24px; max-width: 80px; object-fit: contain;" class="me-2">
                                            @else
                                                <span class="fw-bold me-2">{{$plata->nombrePlataforma}}</span>
                                            @endif
                                            
                                            <div class="ms-auto text-end">
                                                <span class="badge bg-primary rounded-pill js-rango-recomendado" data-pid="{{$plata->idPlataforma}}">S/ 0.00</span>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-{{$plata->idPlataforma}}" class="accordion-collapse collapse {{$index === 0 ? 'show' : ''}}" aria-labelledby="heading-{{$plata->idPlataforma}}" data-bs-parent="#accordionPlataformas">
                                    <div class="accordion-body p-0">
                                        <div class="list-group list-group-flush rounded-bottom">
                                            <!-- Guardamos la info de comisiones en data attributes para leerlas desde JS -->
                                            <div class="d-none plataforma-data" data-pid="{{$plata->idPlataforma}}" data-name="{{$plata->nombrePlataforma}}" data-has-rules="{{$reglasPlata->isNotEmpty() ? '1' : '0'}}">
                                                @if($reglasPlata->isNotEmpty())
                                                    @foreach($reglasPlata as $regla)
                                                        <span class="comision-data" 
                                                              data-id="{{$regla->id}}"
                                                              data-nombre="{{$regla->nombre_regla}}"
                                                              data-comision="{{$regla->porcentaje_comision !== null ? ($regla->porcentaje_comision * 100) : 0}}" 
                                                              data-flete="{{$regla->monto_fijo ?? 0}}"
                                                              data-tipo="{{$regla->tipo_condicion}}"
                                                              data-valor="{{$regla->valor_condicion ?? ''}}"
                                                              data-prioridad="{{$regla->prioridad}}">
                                                        </span>
                                                    @endforeach
                                                @else
                                                    @foreach($plata->ComisionPlataforma as $comi)
                                                        <span class="comision-data" 
                                                              data-id="{{$comi->idComisionPlataforma}}"
                                                              data-nombre=""
                                                              data-comision="{{$comi->comision}}" 
                                                              data-flete="{{$comi->flete}}"
                                                              data-tipo="DEFAULT"
                                                              data-valor=""
                                                              data-prioridad="0">
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </div>
                                            
                                            <!-- Aquí se inyectan las opciones de comisiones con JS -->
                                            <div class="plataforma-comisiones-container" id="container-plataforma-{{$plata->idPlataforma}}">
                                                <!-- Plantilla JS -->
                                            </div>
                                        </div>
                                    </div>
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

<!-- Configuración Global -->
<script>
    window.APP_DATA = {
        facturacionFactor: {{ ($valores->facturacion / 100) + 1 }},
        igvFactor: {{ ($valores->igv / 100) + 1 }},
        tasaCambio: {{ $valores->tasaCambio }},
        igvPorcentaje: {{ $valores->igv }},
        facturacionPorcentaje: {{ $valores->facturacion }},
        csrf: '{{ csrf_token() }}'
    };

    function consultarTipoCambio() {
        let btn = document.getElementById('btn-consultar-tc');
        let icon = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        btn.disabled = true;

        fetch('/tipo-cambio')
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = icon;
                btn.disabled = false;
                if (data.success && data.data && data.data.venta) {
                    let tc = data.data.venta;
                    let inputTc = document.getElementById('input-tasa-cambio');
                    if (inputTc) inputTc.value = tc;
                    window.APP_DATA.tasaCambio = parseFloat(tc);
                    
                    Swal.fire({
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                        icon: 'success', title: 'TC SUNAT obtenido: ' + tc
                    });
                    
                    // Recalcular todo
                    if(typeof window.recalcularCotizacion === 'function') {
                        window.recalcularCotizacion();
                    }
                } else {
                    Swal.fire('Error', data.message || 'No se pudo obtener el TC de hoy', 'error');
                }
            })
            .catch(err => {
                btn.innerHTML = icon;
                btn.disabled = false;
                Swal.fire('Error', 'Problema de red al consultar', 'error');
            });
    }
</script>
<script src="{{ asset('js/cotizador.js') }}?v=1.02"></script>
@endsection
