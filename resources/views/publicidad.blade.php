@extends('layouts.app')

@section('title', 'Publicidad Web')

@section('content')
<div class="container">
    <br>
    <div class="row">
        <h2><i class="bi bi-globe"></i> Sitios web</h2>
    </div>
    <br>
    @foreach($empresas as $empresa)
        <div class="row border shadow rounded">
            <div class="col-md-12 border-bottom mb-2">
                <div class="row">
                    <div class="col-6 col-md-6 pt-1 text-truncate">
                        <h5>{{$empresa->nombreComercial}}</h5>
                    </div>
                    <div class="col-6 col-md-6 pt-1 text-end">
                        <h5>{{$empresa->rucEmpresa}} &nbsp;  <a href="{{route('empresa-publicidad',[encrypt($empresa->idEmpresa)])}}" class="fs-4 text-secondary" data-bs-toggle="tooltip" data-bs-placement="right" title="Editar sitio" style="cursor:pointer"><i class="bi bi-pencil-fill fs-5"></i></a></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="row">
                    <div class="col-4 col-md-12 mb-2">
                        <h6>Fondo:</h6>
                        <img src="{{ $empresa->fondo ? asset('storage/'.$empresa->fondo) . '?' . time() : 'https://via.placeholder.com/1920x1080?text=Sin+Fondo' }}" alt="fondo" class="border rounded-3 w-100" style="object-fit: cover; max-height: 100px;">
                    </div>
                    <div class="col-4 col-md-6 mb-2">
                        <h6>Icono:</h6>
                        <img src="{{ $empresa->icon ? asset('storage/'.$empresa->icon) . '?' . time() : 'https://via.placeholder.com/512' }}" alt="icono"  class="border rounded-3 w-100" >
                    </div>
                    <div class="col-4 col-md-6 mb-2">
                        <h6>Logo:</h6>
                        <img src="{{ $empresa->logo ? asset('storage/'.$empresa->logo) . '?' . time() : 'https://via.placeholder.com/500x200' }}" alt="logo" class="border rounded-3 w-100">
                    </div>
                    <div class="col-4 col-md-12 mb-2">
                        <h6>Colores:</h6>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <label>Principal</label>
                                <div class="d-flex justify-content-center align-items-center">
                                    <div class="border shadow rounded-circle" style="height:1.5rem;width:1.5rem;background-color:{{$empresa->colorUno}}">
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <label>Secundario</label>
                                <div class="d-flex justify-content-center align-items-center">
                                    <div class="border shadow rounded-circle" style="height:1.5rem;width:1.5rem;background-color:{{$empresa->colorDos}}">
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <label>Tercero</label>
                                <div class="d-flex justify-content-center align-items-center">
                                    <div class="border shadow rounded-circle" style="height:1.5rem;width:1.5rem;background-color:{{$empresa->colorTres}}">
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mb-2">
                        <h6>Ubicacion:</h6>
                        <a class="text-dark" href="{{$empresa->ubicacionLink}}">{{$empresa->ubicacion}}</a>
                    </div>
                    <div class="col-md-12 mb-2">
                        <h6>Correo:</h6>
                        <a >{{$empresa->correoEmpresa}}</a>
                    </div>
                    <div class="col-md-12 mb-2">
                        <h6>Redes sociales:</h6>
                        <div class="row">
                            @foreach($empresa->EmpresaRedSocial as $red)
                            <div class="col-2 col-md-3">
                                <a href="{{$red->enlace}}">
                                    <img  src="{{ asset('storage/'.$red->imagen) }}?{{ time() }}" alt="logo" class="rounded-3 w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="{{$red->titular}}">
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <h5>Banners principales:</h5>
                    @foreach($empresa->Publicidad->where('tipoPublicidad','BANNER') as $banner)
                    <div class="col-md-6 mb-2">
                        <img src="{{ asset('storage/'.$banner->imagenPublicidad) }}?{{ time() }}" alt="logo" class="border rounded-3 w-100">
                    </div>
                    @endforeach
                </div>
                <div class="row">
                    <h5>Banners verticales</h5>
                    @foreach($empresa->Publicidad->where('tipoPublicidad','VERTICAL') as $banner)
                    <div class="col-6 col-md-3 mb-2">
                        <img src="{{ asset('storage/'.$banner->imagenPublicidad) }}?{{ time() }}" alt="logo" class="border rounded-3 w-100">
                    </div>
                    @endforeach
                </div>
                <div class="row">
                    <h5>Banner por campa&ntildea</h5>
                    @foreach($empresa->Publicidad->where('tipoPublicidad','CAMPANIA') as $banner)
                    <div class="col-md-6 mb-2">
                        <img src="{{ asset('storage/'.$banner->imagenPublicidad) }}?{{ time() }}" alt="logo" class="border rounded-3 w-100">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <br>
    @endforeach
</div>
@endsection