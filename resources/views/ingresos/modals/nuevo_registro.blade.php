<!-- Modal Ingreso -->
@foreach ($user->Accesos as $vista)
@if($vista->idVista == 8)
<form action="{{route('insertcomprobante')}}" method="POST">
    @csrf
    <div class="modal fade" id="ingresoModal" tabindex="-1" aria-labelledby="ingresoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ingresoModalLabel">Nuevo Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 col-md-6">
                            <label>Proveedor</label>
                            <select class="form-select" id="proveedor-select" name="proveedor">
                                <option value="" {{old('proveedor')=='' ? 'selected' : '' }}>-Elige un proveedor-</option>
                                @foreach($proveedores as $proveedor)
                                <option value="{{$proveedor['idProveedor']}}"
                                    {{old('proveedor')==$proveedor['idProveedor'] ? 'selected' : '' }}>
                                    {{$proveedor['nombreProveedor']}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-6">
                            <label>Documento</label>
                            <select class="form-select" id="documento-select" name="tipocomprobante">
                                <option value="" {{old('tipocomprobante')=='' ? 'selected' : '' }}>-Elige un
                                    documento-</option>
                                @foreach($documentos as $doc)
                                <option value="{{$doc->idTipoComprobante}}" {{old('tipocomprobante')==$doc->
                                        idTipoComprobante ? 'selected' : ''}}>{{$doc->descripcion}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Nro Documento</label>
                            <input type="text" class="form-control" id="documento-number" name="numerocomprobante"
                                value="{{old('numerocomprobante')}}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success" id="btn-save"><i class="bi bi-floppy"></i>
                        Registrar</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endif
@endforeach
