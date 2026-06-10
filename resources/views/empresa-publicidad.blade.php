@extends('layouts.app')

@section('title', 'Web | '. $empresa->nombreComercial)

@section('content')
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 1rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        .image-preview-wrapper {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
            transition: transform 0.3s ease;
        }
        .image-preview-wrapper:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .image-preview-wrapper img {
            width: 100%;
            object-fit: cover;
            border-radius: 0.75rem;
        }
        .edit-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
            color: white;
            text-align: center;
            padding: 5px;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .image-preview-wrapper:hover .edit-overlay {
            opacity: 1;
        }
        h4 {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            border-bottom: 2px solid #3498db;
            display: inline-block;
            padding-bottom: 0.2rem;
        }
    </style>
    <div class="container pb-5">
        <br>
        <div class="row">
            <div class="col-6">
                <h3><a href="{{route('publicidad')}}" class="text-secondary"><i class="bi bi-arrow-left-circle"></i></a> WEB: <span class="text-secondary">{{$empresa->nombreComercial}}</span></h3>
            </div> 
            <div class="col-6 text-end">
                <h3>{{$empresa->rucEmpresa}}</h3>
            </div>
        </div>
        <br>
        <form action="{{route('updatepublicacion')}}" method="POST" enctype="multipart/form-data">
        @csrf
        <input name="empresa" value="{{$empresa->idEmpresa}}" type="hidden">

        <div class="glass-card p-4 mb-4">
            <h4><i class="bi bi-gear-fill"></i> CONFIGURACIÓN PRINCIPAL</h4>
            <div class="row mt-3">
                <!-- FONDO -->
                <div class="col-md-12 mb-4">
                    <h5>Fondo (Background) <small class="text-muted" style="font-size: 0.8rem;">(Rec: 1920x1080, max 2MB, WEBP/JPG)</small></h5>
                    <div class="image-preview-wrapper" style="height: 200px; background: #f8f9fa; cursor: pointer;" onclick="document.getElementById('img-fondo').click()">
                        <input class="d-none input-edit" name="fondo" type="file" accept="image/webp, image/jpeg" id="img-fondo" onchange="changeImageFondo(event)">
                        <img src="{{ $empresa->fondo ? asset('storage/'.$empresa->fondo) . '?' . time() : 'https://via.placeholder.com/1920x1080?text=Subir+Fondo' }}" 
                             alt="Fondo" id="triggerImage-fondo" 
                             style="height: 100%; width: 100%; object-fit: cover;">
                        <div class="edit-overlay"><i class="bi bi-pencil-fill"></i> Cambiar Fondo</div>
                    </div>
                </div>

                <!-- LOGO -->
                <div class="col-md-6 mb-3">
                    <h5>Logo <small class="text-muted" style="font-size: 0.8rem;">(Rec: 500x200, max 1MB, WEBP/PNG)</small></h5>
                    <div class="image-preview-wrapper" style="height: 150px; background: #e9ecef; cursor: pointer;" onclick="document.getElementById('img-logo').click()">
                        <input class="d-none input-edit" name="logo" type="file" accept="image/webp, image/png" id="img-logo" onchange="changeImageLogo(event)">
                        <img src="{{ $empresa->logo ? asset('storage/'.$empresa->logo) . '?' . time() : 'https://via.placeholder.com/500x200?text=Subir+Logo' }}" 
                             alt="Logo" id="triggerImage-logo" 
                             style="height: 100%; width: 100%; object-fit: contain; padding: 10px;">
                        <div class="edit-overlay"><i class="bi bi-pencil-fill"></i> Cambiar Logo</div>
                    </div>
                </div>

                <!-- ICONO -->
                <div class="col-md-6 mb-3">
                    <h5>Ícono (Favicon) <small class="text-muted" style="font-size: 0.8rem;">(Rec: 512x512, max 500KB, PNG/ICO)</small></h5>
                    <div class="image-preview-wrapper" style="height: 150px; width: 150px; margin: 0 auto; background: #e9ecef; border-radius: 50%; cursor: pointer;" onclick="document.getElementById('img-icon').click()">
                        <input class="d-none input-edit" name="icon" type="file" accept="image/png, image/x-icon" id="img-icon" onchange="changeImageIcon(event)">
                        <img src="{{ $empresa->icon ? asset('storage/'.$empresa->icon) . '?' . time() : 'https://via.placeholder.com/512x512?text=Subir+Icono' }}" 
                             alt="Icono" id="triggerImage-icon" 
                             style="height: 100%; width: 100%; object-fit: cover; border-radius: 50%;">
                        <div class="edit-overlay" style="border-radius: 0 0 50% 50%;"><i class="bi bi-pencil-fill"></i> Editar</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card p-4 mb-4">
            <h4><i class="bi bi-images"></i> BANNERS</h4>
            <div class="col-md-12">
                <div class="row">
                    <h5>Principales: </h5>
                    @foreach($empresa->Publicidad->where('tipoPublicidad','BANNER') as $banner)
                    <div class="col-md-4 mb-2" id="previewImage-{{$banner->idPublicidad}}">
                        <input class="d-none input-edit" name="img[{{$banner->idPublicidad}}]" type="file" accept="image/webp" id="img-public-{{$banner->idPublicidad}}" onchange="changeImageBanner(event,{{$banner->idPublicidad}})">
                        <img src="{{ asset('storage/'.$banner->imagenPublicidad) }}?{{ time() }}" alt="Click to upload" id="triggerImage-{{$banner->idPublicidad}}" class="w-100 border border-secondary rounded-3" style="cursor: pointer; object-fit: cover;">
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <h5>Verticales: </h5>
                    @foreach($empresa->Publicidad->where('tipoPublicidad','VERTICAL') as $banner)
                    <div class="col-md-3 mb-2" id="previewImage-{{$banner->idPublicidad}}">
                        <input class="d-none input-edit" name="img[{{$banner->idPublicidad}}]" type="file" accept="image/webp" id="img-public-{{$banner->idPublicidad}}" onchange="changeImageVertical(event,{{$banner->idPublicidad}})">
                        <img src="{{ asset('storage/'.$banner->imagenPublicidad) }}?{{ time() }}" alt="Click to upload" id="triggerImage-{{$banner->idPublicidad}}" class="w-100 border border-secondary rounded-3" style="cursor: pointer; object-fit: cover;">
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <h5>Campa&ntildea: </h5>
                    @foreach($empresa->Publicidad->where('tipoPublicidad','CAMPANIA') as $banner)
                    <div class="col-md-12 mb-2" id="previewImage-{{$banner->idPublicidad}}">
                        <input class="d-none input-edit" name="imgPubli[{{$banner->idPublicidad}}]" type="file" accept="image/webp" id="img-public-{{$banner->idPublicidad}}" onchange="changeImage(event,{{$banner->idPublicidad}})">
                        <img src="{{ asset('storage/'.$banner->imagenPublicidad) }}?{{ time() }}" alt="Click to upload" id="triggerImage-{{$banner->idPublicidad}}" class="w-100 border border-secondary rounded-3" style="cursor: pointer; object-fit: cover;">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="row border shadow pt-2 pb-2 mb-2">
            <div class="col-12">
                <h4>REDES SOCIALES</h4>
                <input name="empresa" value="{{$empresa->idEmpresa}}" type="hidden" class="form-control">
            </div>
            @foreach($empresa->EmpresaRedSocial as $red)
            <div class="col-4 mb-2">
                <div class="row ms-1 me-1 pt-2 pb-2 border border-secondary rounded-3">
                    <div class="col-9">
                        <h6>{{$red->RedSocial->plataforma}}</h6>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text" id="inputGroup-sizing-sm">Enlace:</span>
                          <input type="text" name="enlaces[{{$red->idRedSocial}}]" class="form-control" value="{{$red->enlace}}" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm">
                        </div>
                    </div>
                    <div class="col-3" >
                        <input class="d-none input-edit" name="imgRed[{{$red->idRedSocial}}]" type="file" accept="image/webp" id="img-red-{{$red->idRedSocial}}" onchange="changeImageRedSocial(event,{{$red->idRedSocial}})">
                        <img src="{{ asset('storage/'.$red->imagen) }}?{{ time() }}" alt="Click to upload" id="triggerImageRed-{{$red->idRedSocial}}" class="w-100 border border-secondary rounded-3" style="cursor: pointer; object-fit: cover;">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-success" type="submit">Guardar <i class="bi bi-floppy"></i></button>
            </div>
        </div>
        </form>
        <br>
        <br>
    </div>
    <script>
        @foreach($empresa->Publicidad as $publi)
            document.getElementById('triggerImage-{{$publi->idPublicidad}}').addEventListener('click', function() {
                document.getElementById('img-public-{{$publi->idPublicidad}}').click();
            });
        @endforeach
        @foreach($empresa->EmpresaRedSocial as $red)
            document.getElementById('triggerImageRed-{{$red->idRedSocial}}').addEventListener('click', function() {
                document.getElementById('img-red-{{$red->idRedSocial}}').click();
            });
        @endforeach
        
        function changeImageBanner(event, id) {
            const file = event.target.files[0];
            const triggerImage = document.getElementById('triggerImage-' + id);
        
            if (file) {
                const reader = new FileReader();
        
                reader.onload = function(e) {
                    const img = new Image();
                    img.src = e.target.result;
        
                    img.onload = function() {
                        const maxWidth = 1920 ; // Ancho máximo permitido
                        const maxHeight = 740; // Alto máximo permitido
        
                        if (img.width > maxWidth || img.height > maxHeight) {
                            alert('La imagen no coincide con las dimensiones permitidas ' + maxWidth + ' x ' + maxHeight + ' píxeles.');
                            return;
                        }
        
                        // Si la imagen cumple con las dimensiones, actualiza la vista previa
                        triggerImage.src = e.target.result;
                    }
                }
        
                reader.readAsDataURL(file);
            }
        }
        
        function changeImageVertical(event, id) {
            const file = event.target.files[0];
            const triggerImage = document.getElementById('triggerImage-' + id);
        
            if (file) {
                const reader = new FileReader();
        
                reader.onload = function(e) {
                    const img = new Image();
                    img.src = e.target.result;
        
                    img.onload = function() {
                        const maxWidth = 800 ; // Ancho máximo permitido
                        const maxHeight = 1200; // Alto máximo permitido
        
                        if (img.width > maxWidth || img.height > maxHeight) {
                            alert('La imagen no coincide con las dimensiones permitidas ' + maxWidth + ' x ' + maxHeight + ' píxeles.');
                            return;
                        }
        
                        // Si la imagen cumple con las dimensiones, actualiza la vista previa
                        triggerImage.src = e.target.result;
                    }
                }
        
                reader.readAsDataURL(file);
            }
        }
        
        function changeImageRedSocial(event, id) {
            const file = event.target.files[0];
            const triggerImage = document.getElementById('triggerImageRed-' + id);
        
            if (file) {
                const reader = new FileReader();
        
                reader.onload = function(e) {
                    const img = new Image();
                    img.src = e.target.result;
        
                    img.onload = function() {
                        const maxWidth = 150 ; // Ancho máximo permitido
                        const maxHeight = 150; // Alto máximo permitido
        
                        if (img.width > maxWidth || img.height > maxHeight) {
                            alert('La imagen no coincide con las dimensiones permitidas ' + maxWidth + ' x ' + maxHeight + ' píxeles.');
                            return;
                        }
        
                        // Si la imagen cumple con las dimensiones, actualiza la vista previa
                        triggerImage.src = e.target.result;
                    }
                }
        
                reader.readAsDataURL(file);
            }
        }
        
        function changeImage(event, id) {
            const file = event.target.files[0];
            const triggerImage = document.getElementById('triggerImage-' + id);
        
            if (file) {
                const reader = new FileReader();
        
                reader.onload = function(e) {
                    triggerImage.src = e.target.result;
                }
        
                reader.readAsDataURL(file);
            }
        }
        function changeImageFondo(event) {
            const file = event.target.files[0];
            const triggerImage = document.getElementById('triggerImage-fondo');
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('El fondo no debe superar los 2MB.');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) { triggerImage.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        }

        function changeImageLogo(event) {
            const file = event.target.files[0];
            const triggerImage = document.getElementById('triggerImage-logo');
            if (file) {
                if (file.size > 1024 * 1024) {
                    alert('El logo no debe superar 1MB.');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) { triggerImage.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        }

        function changeImageIcon(event) {
            const file = event.target.files[0];
            const triggerImage = document.getElementById('triggerImage-icon');
            if (file) {
                if (file.size > 500 * 1024) {
                    alert('El ícono no debe superar los 500KB.');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) { triggerImage.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        }
    </script>
@endsection