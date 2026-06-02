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
    @if(isset($reclamosUrgentes) && $reclamosUrgentes > 0)
    <div class="row mt-3">
        <div class="col-12">
            <div class="alert alert-danger d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
                <div>
                    <h5 class="alert-heading mb-1 fw-bold text-danger">¡Atención! Tienes reclamos por vencer</h5>
                    <p class="mb-0 text-dark">Hay <strong>{{ $reclamosUrgentes }}</strong> reclamo(s) de devolución/garantía con 3 días o menos para responder. <a href="{{ route('reclamos.index') }}" class="fw-bold text-danger text-decoration-underline">Ir a Reclamos</a></p>
                </div>
            </div>
        </div>
    </div>
    @endif
    <br>
    <div class="row">
        <div class="col-md-12 mt-3">
            <div class="row pt-3 pb-3 border shadow rounded-3 bg-white">
                <div class="col-md-8">
                    <h4 class="fw-bold"><i class="bi bi-box-seam me-2"></i>Inventario</h4>
                    <small class="text-secondary">Seguimiento de Productos registrados y métricas clave.</small>
                </div>
                @php
                    $tieneAccesoAnalitica = false;
                    foreach ($user->Accesos as $acceso) {
                        if ($acceso->idVista == 13) {
                            $tieneAccesoAnalitica = true;
                            break;
                        }
                    }
                @endphp
                @if($tieneAccesoAnalitica)
                <div class="col-md-4 text-end">
                    <a href="{{ route('dashboard.analitica') }}" class="btn btn-primary rounded-pill shadow-sm px-4">
                        <i class="bi bi-bar-chart-line-fill me-1"></i> Ver Detalles Analíticos
                    </a>
                </div>
                @endif

                <div class="col-12 mt-3">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border shadow-sm h-100">
                                <h6 class="text-secondary small mb-1 fw-bold uppercase">Ventas Semana</h6>
                                <h4 class="fw-bold text-primary mb-0">{{ collect($ventas7Dias)->sum('total') }} <small class="fs-6 fw-normal">uds.</small></h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border shadow-sm h-100">
                                <h6 class="text-secondary small mb-1 fw-bold uppercase">Promedio Diario</h6>
                                <h4 class="fw-bold text-success mb-0">{{ number_format(collect($ventas7Dias)->avg('total'), 1) }} <small class="fs-6 fw-normal">u/d</small></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-3"></div> <!-- Spacer -->
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
                        <div class="col-12 mt-3 pt-2 border-top">
                            <h4 class="mb-0">Top 5 Monto Vendido (Mes)</h4>
                            <small class="text-secondary">Mayores ingresos del mes actual.</small>
                        </div>
                        <div class="col-12 mt-2">
                            @foreach ($publicacionesTopMonto as $pub)
                            <div class="row border ms-1 me-1 rounded pt-1 pb-1 mb-1 bg-light shadow-sm">
                                <div class="col-2 text-center pe-0">
                                    <img src="{{ asset('storage/' . $pub->CuentasPlataforma->Plataforma->imagenPlataforma) }}"
                                        class="rounded-circle"
                                        style="width: 25px; height: 25px; object-fit: contain;"
                                        alt="{{ $pub->CuentasPlataforma->Plataforma->nombrePlataforma }}">
                                </div>
                                <div class="col-6">
                                    <h6 class="mb-0 text-truncate" style="font-size: 0.8rem;" title="{{ $pub->titulo }}">{{ $pub->titulo }}</h6>
                                </div>
                                <div class="col-4 text-end">
                                    <strong class="text-primary" style="font-size: 0.85rem;">S/ {{ number_format($pub->total_monto, 2) }}</strong>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="col-12 mt-3 pt-2 border-top">
                            <h4 class="mb-0">Top 5 Monto Vendido (Histórico)</h4>
                            <small class="text-secondary">Mayores ingresos acumulados.</small>
                        </div>
                        <div class="col-12 mt-2">
                            @foreach ($publicacionesTopMontoHist as $pub)
                            <div class="row border ms-1 me-1 rounded pt-1 pb-1 mb-1 bg-white shadow-sm">
                                <div class="col-2 text-center pe-0">
                                    <img src="{{ asset('storage/' . $pub->CuentasPlataforma->Plataforma->imagenPlataforma) }}"
                                        class="rounded-circle"
                                        style="width: 25px; height: 25px; object-fit: contain;"
                                        alt="{{ $pub->CuentasPlataforma->Plataforma->nombrePlataforma }}">
                                </div>
                                <div class="col-6">
                                    <h6 class="mb-0 text-truncate" style="font-size: 0.8rem;" title="{{ $pub->titulo }}">{{ $pub->titulo }}</h6>
                                </div>
                                <div class="col-4 text-end">
                                    <strong class="text-success" style="font-size: 0.85rem;">S/ {{ number_format($pub->total_monto, 2) }}</strong>
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
                            <h4 class="mb-0">Top 3 SKU</h4>
                            <small class="text-secondary">SKU con más ventas</small>
                        </div>
                        <div class="col-md-12 mt-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($publicacionesMostSold->take(3) as $index => $topPub)
                                <li class="list-group-item px-1">
                                    <div class="row align-items-center">
                                        <div class="col-2 text-center">
                                            <span class="badge rounded-pill {{ $index === 0 ? 'bg-warning text-dark' : ($index === 1 ? 'bg-secondary' : 'bg-dark') }}" style="font-size: 0.9rem;">
                                                #{{ $index + 1 }}
                                            </span>
                                        </div>
                                        <div class="col-6 px-0">
                                            <strong class="d-block" style="font-size: 0.85rem;">{{ $topPub->sku }}</strong>
                                            <small class="text-secondary text-truncate d-block" style="max-width: 100%;">{{ $topPub->titulo }}</small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <span class="text-success fw-bold">{{ $topPub->total_ventas }}</span>
                                            <small class="d-block text-secondary">egresos</small>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="row border shadow rounded-3 pt-2 pb-2">
                        <div class="col-md-12">
                            <h4 class="mb-0">Productos Top del Mes</h4>
                            <small class="text-secondary">Productos con más ventas este mes</small>
                        </div>
                        <div class="col-md-12 mt-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($productosMostSoldMonth->take(3) as $index => $topProd)
                                <li class="list-group-item px-1">
                                    <div class="row align-items-center">
                                        <div class="col-2 text-center">
                                            <span class="badge rounded-pill {{ $index === 0 ? 'bg-primary' : ($index === 1 ? 'bg-info text-dark' : 'bg-secondary') }}" style="font-size: 0.9rem;">
                                                #{{ $index + 1 }}
                                            </span>
                                        </div>
                                        <div class="col-6 px-0">
                                            <strong class="d-block text-truncate" style="font-size: 0.85rem;" title="{{ $topProd->nombreProducto }}">{{ $topProd->nombreProducto }}</strong>
                                            <small class="text-secondary text-truncate d-block" style="max-width: 100%;">{{ $topProd->codigoProducto }}</small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <span class="text-primary fw-bold">{{ $topProd->total_ventas }}</span>
                                            <small class="d-block text-secondary">uds.</small>
                                        </div>
                                    </div>

                                </li>
                                @endforeach
                            </ul>

                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="row border shadow rounded-3 pt-2 pb-2">
                        <div class="col-md-12">
                            <h4 class="mb-0">Top 3 SKU (Mes)</h4>
                            <small class="text-secondary">Mayor rotación este mes</small>
                        </div>
                        <div class="col-md-12 mt-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($skusMostSoldMonth as $index => $topSkuMonth)
                                <li class="list-group-item px-1">
                                    <div class="row align-items-center">
                                        <div class="col-2 text-center">
                                            <span class="badge rounded-pill {{ $index === 0 ? 'bg-warning text-dark' : ($index === 1 ? 'bg-secondary' : 'bg-dark') }}" style="font-size: 0.9rem;">
                                                #{{ $index + 1 }}
                                            </span>
                                        </div>
                                        <div class="col-6 px-0">
                                            <strong class="d-block" style="font-size: 0.85rem;" title="{{ $topSkuMonth->sku }}">{{ $topSkuMonth->sku }}</strong>
                                            <small class="text-secondary text-truncate d-block" style="max-width: 100%;" title="{{ $topSkuMonth->titulo }}">{{ $topSkuMonth->titulo }}</small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <span class="text-success fw-bold">{{ $topSkuMonth->total_ventas }}</span>
                                            <small class="d-block text-secondary">egresos</small>
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
                <div class="col-md-12 mt-3">
                    <div class="row border shadow rounded-3 pt-2 pb-2">
                        <div class="col-md-12">
                            <h4 class="mb-0">Top 5 Existencias</h4>
                            <small class="text-secondary">Productos con más stock total</small>
                        </div>
                        <div class="col-md-12 mt-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($productosMostStock as $index => $topStock)
                                <li class="list-group-item px-1">
                                    <div class="row align-items-center">
                                        <div class="col-2 text-center">
                                            <span class="badge rounded-pill {{ $index === 0 ? 'bg-success' : ($index === 1 ? 'bg-info text-dark' : 'bg-secondary') }}" style="font-size: 0.9rem;">
                                                #{{ $index + 1 }}
                                            </span>
                                        </div>
                                        <div class="col-6 px-0">
                                            <strong class="d-block text-truncate" style="font-size: 0.85rem;" title="{{ $topStock->nombreProducto }}">{{ $topStock->nombreProducto }}</strong>
                                            <small class="text-secondary text-truncate d-block" style="max-width: 100%;">{{ $topStock->codigoProducto }}</small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <span class="text-success fw-bold">{{ (int)$topStock->total_stock }}</span>
                                            <small class="d-block text-secondary">uds.</small>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="row border shadow rounded-3 pt-2 pb-2">
                        <div class="col-md-12">
                            <h4 class="mb-0">Top 5 Fallas</h4>
                            <small class="text-secondary">Productos con más devoluciones/fallos</small>
                        </div>
                        <div class="col-md-12 mt-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($productosConFallas as $index => $topFalla)
                                <li class="list-group-item px-1">
                                    <div class="row align-items-center">
                                        <div class="col-2 text-center">
                                            <span class="badge rounded-pill {{ $index === 0 ? 'bg-danger' : ($index === 1 ? 'bg-warning text-dark' : 'bg-secondary') }}" style="font-size: 0.9rem;">
                                                #{{ $index + 1 }}
                                            </span>
                                        </div>
                                        <div class="col-6 px-0">
                                            <strong class="d-block text-truncate" style="font-size: 0.85rem;" title="{{ $topFalla->nombreProducto }}">{{ $topFalla->nombreProducto }}</strong>
                                            <small class="text-secondary text-truncate d-block" style="max-width: 100%;">{{ $topFalla->modelo }}</small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <span class="text-danger fw-bold">{{ $topFalla->total_fallas }}</span>
                                            <small class="d-block text-secondary">fallas</small>
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