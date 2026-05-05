@php
$gradientParts = [];
$startRange = 0;
$totalInventario = $inventario > 0 ? $inventario : 1;
foreach ($stock as $key => $value) {
$percentage = ($value['cantidad'] * 100) / $totalInventario;
$endRange = $startRange + $percentage;
$gradientParts[] = ($colors[$key] ?? '#ccc') . " " . round($startRange, 2) . "% " . round($endRange, 2) . "%";
$startRange = $endRange;
}
$gradientString = implode(', ', $gradientParts);
@endphp

<div class="container">
    <br>
    <div class="row">
        <div class="col-md-12 mt-3">
            <div class="row pt-2 pb-2 border shadow rounded-3">
                <h4>Inventario</h4>
                <small class="mb-2 text-secondary">Seguimiento de Productos registrados.</small>
                @foreach ($registros as $registro)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{route('dashboardinventario',[encrypt($registro['estado'])])}}" class="text-decoration-none">
                        <div class="card {{$registro['bg']}} text-light mb-3" style="max-width: 18rem;">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-12 truncate">
                                        <h5>{{$registro['titulo']}}</h5>
                                    </div>
                                    <div class="col-md-12" style="position: relative">
                                        <i class="bi bi-{{$registro['icon']}} text-transparent" style="position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%);font-size:3rem"></i>
                                        <h1 style="position: relative; z-index: 1;">{{$registro['cantidad']}}</h1>
                                    </div>
                                    <div class="col-md-12 truncate">
                                        <small>{{$registro['fecha']}}.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 mt-3">
            <div class="row me-md-1">
                <div class="col-md-12 mt-3">
                    <div class="row border shadow rounded-3 pt-2 pb-2">
                        <div class="col-12">
                            <h4 class="mb-0">Productos más vendidos</h4>
                            <small class="text-secondary">Productos con mayor cantidad de ventas.</small>
                        </div>
                        <div class="col-12">
                            @foreach ($productosMostSold as $prod)
                            <div class="row border ms-1 me-1 rounded pt-2 pb-2 mb-2">
                                <div class="col-3 col-md-2 pe-0 text-center">
                                    <img
                                        src="{{asset('storage/'.$prod->imagenProducto1)}}"
                                        class="w-100 rounded-3"
                                        alt="{{$prod->nombreProducto}}"
                                        title="{{$prod->nombreProducto}}"
                                        onerror="this.src=this.getAttribute('data-fallback');"
                                        data-fallback="{{asset('storage/noimagen.webp')}}">
                                </div>
                                <div class="col-9 col-md-10">
                                    <h6 class="mb-0">{{$prod->nombreProducto}}</h6>
                                </div>
                                <div class="col-3 col-md-2 pe-0 text-end">
                                    <small class="text-secondary mt-0 pt-0 mb-0">{{$prod->codigoProducto}}</small>
                                </div>
                                <div class="col-6 col-md-6">
                                    <p class="mt-0 mb-0">{{$prod->modelo}} <em class="text-secondary">({{$prod->MarcaProducto->nombreMarca}})</em></p>
                                </div>
                                <div class="col-3 col-md-4 text-end">
                                    <h5 class="mt-0 mb-0 text-success">Ventas: {{$prod->total_ventas}}</h5>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 mt-3">
            <div class="row me-1">
                <div class="col-md-12 mt-3">
                    <a href="{{route('stockmindashboard')}}" class="text-decoration-none text-dark">
                        <div class="row border shadow rounded-3 pt-2 pb-2">
                            <div class="col-12">
                                <h4 class="mb-0">Stock</h4>
                                <small class="text-secondary">Productos por agotarse</small>
                            </div>
                            <div class="col-2 col-md-4"></div>

                            <div class="col-8 col-md-4 text-center">
                                @php
                                $porcent = round((100 * $stockMin)/($productos > 0 ? $productos : 1), 2);
                                @endphp
                                <div style="width: 100%;aspect-ratio: 1 / 1" class="border rounded-circle d-flex justify-content-center align-items-center mt-2 mb-2 {{$porcent < 10 ? 'border-success' : ($porcent < 40 ? 'border-info' : ($porcent < 80 ? 'border-warning' : 'border-danger'))}}">
                                    <h1>{{$stockMin}}</h1>
                                </div>
                            </div>

                            <div class="col-2 col-md-4"></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="row border shadow rounded-3 pt-2 pb-2">
                        <div class="col-md-12">
                            <h4 class="mb-0">Reclamos</h4>
                            <small class="text-secondary">Libro de reclamaciones</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 mt-3">
            <div class="row">
                <div class="col-md-12 mt-3">
                    <div class="row border shadow rounded-3 pt-2 pb-2">
                        <div class="col-9">
                            <h4 class="mb-0">Almacenes</h4>
                            <small class="text-secondary">Stock de los almacenes</small>
                        </div>
                        <div class="col-3 d-flex justify-content-end align-items-center">
                            @foreach ($stock as $key => $value)
                            <button data-id="{{ $value['almacen']->idAlmacen }}"
                                onclick="reportAlmacen(this.getAttribute('data-id'));"
                                class="btn btn-outline-danger btn-sm ms-2">
                                <i class="bi bi-file-pdf"></i>
                            </button>
                            @endforeach
                        </div>
                        <div class="col-md-12 text-center">
                            <div class="card text-bg-light mb-3 h-100" style="max-width: auto;">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="grafico-pastel" {!! 'style="background: conic-gradient(' . $gradientString . ');"' !!}>
                                                <div class="total-items">
                                                    <div class="d-inline">
                                                        <h1>{{$inventario}}</h1>
                                                        <small class="text-secondary">Productos en existencias</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12 text-start mt-2">
                                            <ul class="list-group list-group-flush">
                                                @foreach ($stock as $key => $value)
                                                <li class="list-group-item">
                                                    <div class="row">
                                                        <div class="col-md-8 text-start">
                                                            {!! '<i class="bi bi-circle-fill" style="color: '.$colors[$key].'"></i>' !!} {{$value['almacen']->descripcion}}
                                                        </div>
                                                        <div class="col-md-4 text-center text-md-end">
                                                            <span>{{$value['cantidad']}}</span>
                                                        </div>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br><br>
</div>

<style>
    .grafico-pastel {
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 280px;
        aspect-ratio: 1 / 1;
        margin: 0 auto;
    }

    .total-items {
        background-color: white;
        border-radius: 50%;
        width: 70%;
        height: 70%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>

<script>
    function reportAlmacen(idAlmacen) {
        const url = "{{ route('reportealmacen', ['idAlmacen' => 'ALMACEN_ID']) }}";
        window.open(url.replace('ALMACEN_ID', idAlmacen), '_blank');
    }
</script>