@extends('layouts.app')

@section('title', 'Garantias')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <div class="col-8">
            <h2>Productos en Garant&iacute;a <i class="bi bi-award"></i></h2>
        </div>
        <div class="col-4 text-end">
            <div class="fw-bold text-primary mb-2 fs-8">
                Garant&iacute;as : {{$garantias->total()}}
            </div>
            <a class="btn btn-success" href="{{route('creategarantia')}}" target="_blank">Nueva garant&iacute;a</a>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-12">
            <ul class="list-group">
                <li class="list-group-item bg-sistema-uno text-light">
                    <div class="row pt-1 text-center">
                        <div class="col-4 text-start">
                            <h6>Producto</h6>
                        </div>
                        <div class="col-2">
                            <h6>Nuemro de serie</h6>
                        </div>
                        <div class="col-2">
                            <h6>Cliente</h6>
                        </div>
                        <div class="col-1">
                            <h6>Estado</h6>
                        </div>
                        <div class="col-2">
                            <h6>Fecha de garant&iacute;a</h6>
                        </div>
                        <div class="col-1">
                            <h6 class="pb-0 mb-0"><i class="bi bi-printer-fill"></i></h6>
                        </div>
                    </div>
                </li>
                @foreach ($garantias as $garantia)
                    <li class="list-group-item">
                        <div class="row text-center">
                            <div class="col-4 fw-bold text-start">
                                <small>{{$garantia->RegistroProducto->DetalleComprobante->Producto->nombreProducto}}</small>
                            </div>
                            <div class="col-2">
                                <small>{{$garantia->RegistroProducto->numeroSerie}}</small>
                            </div>
                            <div class="col-2">
                                <small>{{$garantia->Cliente->numeroDocumento}}</small>
                            </div>
                            <div class="col-1">
                                <small>{{$garantia->RegistroProducto->estado}}</small>
                            </div>
                            <div class="col-2">
                                <small>{{$garantia->fechaGarantia->format('d-m-Y');}}</small>
                            </div>
                            <div class="col-1">
                                <button onclick="pdfGarantia({{$garantia->idGarantia}})" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i></button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
<script>
    function pdfGarantia(idGarantia) {
        var url = "{{ route('garantiaPdf', ['idGarantia' => '__id__']) }}";
        url = url.replace('__id__', idGarantia);  
        window.open(url, '', 'width=800,height=600,scrollbars=yes,location=no,toolbar=no,status=no');
    }
</script>
@endsection