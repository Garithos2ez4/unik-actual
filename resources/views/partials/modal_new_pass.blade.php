<form action="{{ route('updatepass') }}" method="POST">
    @csrf
    <input type="text" name="username" value="{{ $user->name ?? '' }}" autocomplete="username" style="display:none;" aria-hidden="true">
    <div class="modal fade" id="modalNewPass" tabindex="-1" aria-labelledby="modalNewPassLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNewPassLabel">Reestablecer contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <input type="hidden" name="id" value="" class="form-control" id="id-modal-password">
                            <label class="form-label">Nueva contraseña</label>
                            <input type="password" name="pass" class="form-control" id="pass-modal-password" autocomplete="new-password">
                            <small id="passwordError" class="text-danger"></small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Confirmar contraseña</label>
                            <input type="password" name="confirmpass" class="form-control" id="confirmpass-modal-password" autocomplete="new-password">
                            <small id="confirmPasswordError" class="text-danger"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cancelarModal()" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btn-reestablecer-modal-password" class="btn btn-primary"><i class="bi bi-arrow-clockwise"></i> Reestablecer</button>
                </div>
            </div>
        </div>
    </div>
</form>
