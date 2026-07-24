{{-- Modal: Gestionar Tipos de Licencia --}}
<div class="modal fade" id="modalGestionarTipos" tabindex="-1" aria-labelledby="modalGestionarTiposLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <div class="modal-header" style="background:linear-gradient(135deg,#475569,#1e293b); border-radius:16px 16px 0 0; border:none;">
                <h5 class="modal-title text-white" id="modalGestionarTiposLabel">
                    <i class="bi bi-gear-fill me-2"></i>Gestionar Tipos de Licencia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="listaTiposContainer" class="list-group list-group-flush">
                    <!-- Loading state -->
                    <div class="text-center p-4 text-muted" id="loadingTipos">
                        <div class="spinner-border spinner-border-sm me-2"></div>Cargando tipos...
                    </div>
                    <!-- Data will be populated here -->
                </div>
            </div>
            <div class="modal-footer border-0 p-3" style="background: #f8fafc;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.tipo-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #e2e8f0;
    transition: background-color 0.2s;
}
.tipo-item:hover {
    background-color: #f8fafc;
}
.tipo-item:last-child {
    border-bottom: none;
}
.tipo-name {
    font-weight: 500;
    color: #334155;
}
</style>
