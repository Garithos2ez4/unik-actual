@extends('layouts.app')

@section('title', 'Calculadora')

@section('content')
    <div class="container">
        <br>
        <div class="row">
            <div class="col-8">
                 <h2><i class="bi bi-calculator"></i> Calculadora</h2>
            </div>
            <div class="col-4 text-end text-secondary">
                <h4>
                    T.C: <span id="span-tasa-cambio">{{$valores->tasaCambio}}</span>
                    <button class="btn btn-sm btn-outline-secondary ms-2" id="btn-consultar-tc" onclick="consultarTipoCambio()" title="Consultar SUNAT en tiempo real"><i class="bi bi-arrow-clockwise"></i></button>
                </h4>
                <h4>IGV: {{$valores->igv}}% </h4>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="row border shadow rounded-3 me-1 ms-1 pt-2 pb-2">
                    <div class="col-12 col-md-12">
                        <h4>Precio:</h4>
                    </div>
                    <div class="col-4 col-lg-4">
                        <label>Moneda:</label>
                        <select class="form-select" onchange="calculateTc(this)" id="select-type">
                          <option value="DOLAR">Dolar</option>
                          <option value="SOL">Sol</option>
                        </select>
                    </div>
                    <div class="col-4 col-lg-2">
                        <label>Sin IGV:</label>
                        <input type="number" value="0.00" class="form-control" step="0.01" id="precio-entrada">
                    </div>
                    <div class="col-4 col-lg-2">
                        <label>Con IGV:</label>
                        <input type="number" id="costo-igv" value="0.00" class="form-control" step="0.01">
                    </div>
                </div>
            </div>
            <div class="col-lg-1 mb-4"></div>
            <div class="col-6 d-block d-lg-none"></div>
            <div class="col-6 col-lg-3">
                <div class="row border shadow rounded-3 me-1 ms-1 pt-2 pb-2">
                    <div class="col-md-12">
                        <h4 >Categorias:</h4>
                        <input type="hidden" id="hidden-group" value="{{$categorias[0]['GrupoProducto'][0]->idGrupoProducto}}">
                    </div>
                    <div class="col-md-12">
                        <div class="btn-group w-100">
                          <button class="btn btn-secondary dropdown-toggle" type="button" id="title-group" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            {{$categorias[0]['GrupoProducto'][0]->nombreGrupo}}
                          </button>
                          <ul class="dropdown-menu pt-0 pb-0 w-100" aria-labelledby="title-group" id="category-select">
                              @foreach($categorias as $categoria)
                                <li style="position:relative" onmouseover="mostrarGrupos({{$categoria['idCategoria']}})" onmouseout="" class="hover-sistema-uno">
                                    <p class="mb-0 mt-0 pt-1 pb-1 ps-4" style="cursor:pointer">{{$categoria['nombreCategoria']}}
                                    <div style="position:absolute;right:100%;top:0;z-index:900;display:none" data-cat="{{$categoria['idCategoria']}}" class="calc-category">
                                        <ul class="list-group">
                                            @foreach($categoria['GrupoProducto'] as $grupo)
                                            <li class="list-group-item pt-0 pb-0 pe-0 ps-0"><a class="dropdown-item" href="#" onclick="changeGroupValues({{$grupo->idGrupoProducto}},'{{$grupo->nombreGrupo}}')" data-bs-auto-close="true">{{$grupo->nombreGrupo}}</a></li> 
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                              @endforeach
                          </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12" >
                <div class="row border mt-4 shadow rounded-3 me-1 ms-1 pt-2" style="position:relative">
                    <div style="position:absolute;top:0;height:100%;width:100%; display:flex; justify-content:center; align-items:center;opacity:0.5;display:none" class="bg-light rounded-3 loading">
                        <div class="spinner-border text-sistema-uno" style="opacity:1" role="status">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <h4>Costos:</h4>
                    </div>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-3 col-md-2">
                                <label>T.S FACT</label>
                                <h5 id="costo-tsfact">0.00</h5>
                            </div>
                            <div class="col-3 col-md-2">
                                <label>T FACT</label>
                                <h5 id="costo-tfact">0.00</h5>
                            </div>
                            <div class="col-3 col-md-2">
                                <label>Ganancia</label>
                                <h5 id="costo-ganancia">0.00</h5>
                            </div>
                            <div class="col-3 col-md-2">
                                <label>Promedio</label>
                                <h5 id="costo-promedio">0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @foreach($plataformas as $plata)
            <div class="col-md-12">
                <div class="row border mt-4 shadow rounded-3 me-1 ms-1 pt-2 pb-3" style="position:relative">
                    <div style="position:absolute;top:0;height:100%;width:100%; display:flex; justify-content:center; align-items:center;opacity:0.5;display:none;z-index:1000" class="bg-light rounded-3 loading">
                        <div class="spinner-border text-sistema-uno" style="opacity:1" role="status">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="row mb-2">
                            <div class="col-7 col-md-8 col-lg-10">
                                <h4>{{$plata->nombrePlataforma}}:</h4>
                            </div>
                            <div class="col-5 col-md-4 col-lg-2 text-end">
                                <img src="{{asset('storage/'.$plata->imagenPlataforma)}}" class="w-50">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <ul class="list-group list-group-flush rounded-3 border">
                            <li class="list-group-item">
                                <div class="row text-secondary">
                                    <div class="col-3 col-lg-2 text-truncate">
                                        <em><strong>Porcentaje:</strong></em>
                                    </div>
                                    <div class="col-2 col-lg-1">
                                        <em><strong>Monto:</strong></em>
                                    </div>
                                    <div class="col-2 col-lg-2">
                                        <em><strong>Costo:</strong></em>
                                    </div>
                                    <div class="col-lg-1 d-none d-sm-none d-lg-block">
                                        <em><strong>T.S.Fact:</strong></em>
                                    </div>
                                    <div class="col-lg-2 d-none d-sm-none d-lg-block">
                                        <em><strong>T.Fact:</strong></em>
                                    </div>
                                    <div class="col-2 col-lg-2">
                                        <em><strong>Flete:</strong></em>
                                    </div>
                                    <div class="col-2 col-lg-2 text-end">
                                        <em><strong>Promedio:</strong></em>
                                    </div>
                                </div>
                            </li>
                            @foreach($plata->ComisionPlataforma as $comi)
                                <li class="list-group-item li-comision-plataforma hover-sistema-uno">
                                    <div class="row">
                                        <div data-porcent="{{number_format($comi->comision,2,'.')}}" class="col-3 col-lg-2 porcentaje-plataforma">
                                            <small>%{{number_format($comi->comision,2,'.')}}</small>
                                        </div>
                                        <div class="col-2 col-lg-1">
                                            <small class="monto-plataforma">0.00</small>
                                        </div>
                                        <div class="col-2 col-lg-2">
                                            <small class="costo-plataforma">0.00</small>
                                        </div>
                                        <div class="col-lg-1 d-none d-lg-block">
                                            <small class="tsfact-plataforma">0.00</small>
                                        </div>
                                        <div class="col-lg-2 d-none d-lg-block">
                                            <small class="tfact-plataforma">0.00</small>
                                        </div>
                                        <div class="col-2 col-lg-2">
                                            <small class="flete-plataforma" data-flete="{{number_format($comi->flete,2,'.')}}" >{{number_format($comi->flete,2,'.')}}</small>
                                        </div>
                                        <div class="col-2 col-lg-2 text-end">
                                            <small class="promedio-plataforma fw-bold">0.00</small>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endforeach
            
        </div>
        <h2 id="proba"></h2>
    </div>
    <script>
        window.APP_DATA = {
            facturacionFactor: {{ ($valores->facturacion / 100) + 1 }},
            igvFactor: {{ ($valores->igv / 100) + 1 }},
            tasaCambio: {{ $valores->tasaCambio }}
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
                        document.getElementById('span-tasa-cambio').innerText = tc;
                        window.APP_DATA.tasaCambio = tc;
                        
                        Swal.fire('Actualizado', 'Tipo de cambio SUNAT obtenido: ' + tc, 'success');
                        
                        // Simular cambio para recalcular automáticamente
                        let selectType = document.getElementById('select-type');
                        if(selectType && typeof calculateTc === 'function') {
                            calculateTc(selectType);
                        } else {
                            let input = document.getElementById('precio-entrada');
                            if(input) input.dispatchEvent(new Event('keyup'));
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
    <script src="{{ asset('js/calculator-scripts.js') }}?v=1.00"></script>
@endsection