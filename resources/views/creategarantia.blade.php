@extends('layouts.app')

@section('title', 'Nueva Garantia')

@section('content')
<script>
    @if (session('success_pdf'))
        @php
            $garantia = session('success_pdf');
        @endphp
        Swal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            text: " ",
            showCancelButton: true, // Mostrar el botón "Cancelar"
            confirmButtonText: 'Nuevo',
            cancelButtonText: 'Cancelar',
            customClass: {
                    confirmButton: 'btn-success'
                },
            reverseButtons: true, // Invierte el orden de los botones
            html: `
                <!-- Aquí agregamos el botón PDF dentro del cuerpo de la alerta -->
                <div class="text-center mt-3">
                    <h6>Documento de garantía {{1000000 + $garantia->idGarantia}} registrado.</h6>
                    <button class="btn btn-danger btn-sm" onclick="pdfGarantia('{{ route('garantiaPdf', [$garantia->idGarantia]) }}')">
                        PDF <i class="bi bi-file-earmark-pdf"></i>
                    </button>
                </div>
            `,
        }).then((result) => {
            if (result.isDismissed) {
                window.location.href = "{{ route('garantias', [now()->format('Y-m')]) }}"; 
            }
        });
    @endif
</script>


<div class="container">
    <div class="bg-secondary" id="hidden-body" style="position:fixed;left:0;width:100vw;height:100vh;z-index:998;opacity:0.5;display:none">
    </div>
    <br>
    <form action="{{route('insertgarantia')}}" method="post" id="form-create-garantia">
        @csrf
        <div class="row">
            <div class="col-8">
                <h2>Nueva Garantia</h2>
            </div>
            <div class="col-4 text-end">
                <input type="text" class="form-control body-form-garantia" placeholder="Nro del comprobante.." name="numerocomprobante" required>
            </div>
        </div>
        <br>
        <div class="row border shadow rounded-3 pt-3 pb-2">
            <div class="col-8 mb-3">
                <h4>Datos del Cliente</h4>
                <input type="hidden" value="" class="body-form-garantia" id="input-cliente-form-id" name="idcliente">
            </div>
            <div class="col-4 mb-3">
                <div class="input-group" id="div-input-group-cliente" style="position: relative">
                    <input type="text" class="form-control" placeholder="Nro Documento" id="input-garantia-cliente">
                    <x-btn_modal_cliente :clases="'btn-outline-success'" :spanClass="'d-none'"/>
                    <ul class="list-group w-100" style="position: absolute;top:100%" id="suggestion-cliente">
                    </ul>
                </div>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label">Nombres:</label>
                <input type="text" class="form-control" id="input-cliente-form-nombre" placeholder="Nombre o Razón social" readonly>
            </div>
            <div class="col-3 mb-3">
                <label class="form-label">Apellido Paterno:</label>
                <input type="text" class="form-control" id="input-cliente-form-apePaterno" placeholder="Apellido opcional" readonly>
            </div>
            <div class="col-3 mb-3">
                <label class="form-label">Apellido Materno:</label>
                <input type="text" class="form-control" id="input-cliente-form-apeMaterno" placeholder="Apellido opcional" readonly>
            </div>
            <div class="col-4">
                <label class="form-label" >Documento:</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="input-cliente-form-tipoDoc">DNI</span>
                    <input type="text" class="form-control" id="input-cliente-form-numDoc" placeholder="10000001" readonly>
                </div>
            </div>
            <div class="col-4">
                <label class="form-label">N&uacute;mero teléfono:</label>
                <input type="text" class="form-control" id="input-cliente-form-telefono" placeholder="+51 999 999 999" readonly>
            </div>
            <div class="col-4">
                <label class="form-label">Correo electrónico:</label>
                <input type="text" class="form-control" id="input-cliente-form-correo" placeholder="example@tumail.com" readonly>
            </div>
        </div>
        <br>
        <div class="row border shadow rounded-3 pt-3 pb-2">
            <div class="col-8 mb-1">
                <h4>Datos del Producto</h4>
                <input type="hidden" value="" class="body-form-garantia" id="input-producto-form-id" name="idregistro">
            </div>
            <div class="col-4 mb-1">
                <div class="row">
                    <div class="col-12">
                        <div class="input-group" id="div-input-group-registro" style="position: relative">
                            <input type="text" class="form-control" placeholder="Serial Number" id="input-producto-serial">
                            <div class="input-group-text">
                                <x-scan_check :clases="'form-check-input scan-check mt-0'" :idInput="'input-producto-serial'"/>
                            </div>
                            <ul class="list-group w-100" style="position: absolute;top:100%" id="suggestion-registro">
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 text-end">
                        <small class="text-secondary">scanner</small>
                    </div>
                </div>
                
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Descripci&oacute;n:</label>
                <input type="text" class="form-control" id="input-producto-form-nombre" placeholder="Descripción del producto..." disabled>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label">N&uacute;mero de Serie:</label>
                <input type="text" class="form-control" id="input-producto-form-serie" placeholder="Numero de serie..." disabled>
            </div>
            <div class="col-3 mb-3">
                <label class="form-label">Marca:</label>
                <input type="text" class="form-control" id="input-producto-form-marca" placeholder="Marca del producto..." disabled>
            </div>
            <div class="col-3 mb-3">
                <label class="form-label">Modelo:</label>
                <input type="text" class="form-control" id="input-producto-form-modelo" placeholder="Modelo del producto..." disabled>
            </div>
        </div>
        <br>
        <div class="row border shadow rounded-3 pt-3 pb-2">
            <div class="col-12 mb-3">
                <h4>Detalles de la garant&iacute;a</h4>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label">Componentes Recepcionados:</label>
                <textarea class="form-control body-form-garantia" maxlength="255" name="recepcion" required></textarea>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label">Estado F&iacute;sico:</label>
                <textarea class="form-control body-form-garantia" maxlength="255" name="estado" required></textarea>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Falla Presentada:</label>
                <textarea class="form-control body-form-garantia" maxlength="255" name="falla" required></textarea>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-12 text-center">
                <button type="submit" id="btn-registrar-garantia" class="btn btn-success" onclick="return confirm('¿Estas seguro de registrar?')"><i class="bi bi-floppy-fill"></i> Registrar</button>
            </div>
        </div>
    </form>
</div>
<br>
<x-modal_new_cliente :documentos="$tipoDocumentos"/>
<script>
    function pdfGarantia(url) {
        window.open(url, '', 'width=800,height=600,scrollbars=yes,location=no,toolbar=no,status=no');
    }
</script>
<script src="{{asset('js/createGarantia.js')}}"></script>
@endsection