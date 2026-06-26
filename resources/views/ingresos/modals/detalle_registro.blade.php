<form action="{{route('updateregistro')}}" method="POST">
    @csrf
    <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="idregistro" id="idregistro-modal-detail" value="">
                        <div class="col-12">
                            <h5 id="titleproduct-modal-detail">[titulo del producto]</h5>
                        </div>
                        <div class="col-6 text-secondary">
                            <h6 id="proveedor-modal-detail">[proveedor]</h6>
                        </div>
                        <div class="col-6 text-end text-secondary">
                            <h6 id="serialnumber-modal-detail">[numero de serie]</h6>
                        </div>
                        <div class="col-6">
                            <span id="user-modal-detail">[usuario]</span>
                        </div>
                        <div class="col-6 text-end">
                            <span id="date-modal-detail">[fechademovimiento]</span>
                        </div>
                        <div class="col-6 pt-2">
                            <label class="form-label fw-bold">Ubicacion:</label>
                            <select id="almacen-modal-detail" class="form-select" disabled>
                                @foreach ($almacenes as $almacen)
                                <option value="{{$almacen->idAlmacen}}">{{$almacen->descripcion}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pt-2">
                            <label class="form-label fw-bold">Ubicación Específica (Opcional):</label>
                            <select id="ubicacion-especifica-modal-detail" name="ubicacion_especifica" class="form-select">
                                <option value="">Seleccione Rack/Estante</option>
                                @foreach ($almacenes as $almacen)
                                @foreach($almacen->Ubicaciones as $ubicacion)
                                <option value="{{$ubicacion->idUbicacion}}" data-almacen="{{$almacen->idAlmacen}}" class="d-none ubicacion-option">
                                    {{$ubicacion->nombre}}
                                </option>
                                @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pt-2">
                            <label class="form-label fw-bold">Estado:</label>
                            <select id="state-modal-detail" name="estado" class="form-select">
                                @foreach ($estados as $estado)
                                <option value="{{$estado['value']}}" {{$estado['value']=='ENTREGADO' ? 'disabled' :''}}>
                                    {{$estado['name']}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mt-3 d-none" id="devolucion-info-block">
                            <div class="alert alert-warning mb-0 p-2">
                                <h6 class="alert-heading mb-1"><i class="bi bi-info-circle"></i> Info. Devolucin previa</h6>
                                <small id="devolucion-motivo" class="d-block mb-1"></small>
                                <small class="d-block"><strong>Apto Venta:</strong> <span id="devolucion-apto"></span></small>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check-fallo-entrega">
                                <label class="form-check-label text-primary fw-bold" style="cursor: pointer" for="check-fallo-entrega">
                                    Fallo de entrega (Autocompletar)
                                </label>
                            </div>
                            <strong>Observaciones</strong>
                            <textarea name="observacion" maxlength="500" placeholder="Sin observaciones"
                                id="obs-modal-detail" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </div>
        </div>
    </div>
</form>
