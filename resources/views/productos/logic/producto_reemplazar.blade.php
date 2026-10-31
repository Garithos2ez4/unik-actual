@if($esProductoReemplazable)
<script>
    function abrirModalReemplazarProducto() {
        const opcionesGrupos = `<option value="">-- Detectar grupo automáticamente --</option>` +
            @json($grupos->map(fn($g) => '<option value="'.$g['idGrupoProducto'].'">'.e($g['nombreGrupo']).'</option>')->implode(''));

        Swal.fire({
            title: 'Reemplazar Producto (ID #{{ $producto->idProducto }})',
            html: `
                <div class="text-start small">
                    @if(isset($motivoReemplazo) && $motivoReemplazo['tipo'] === 'laptop_garantia_vencida')
                    <div class="alert alert-danger py-2 px-3 mb-3">
                        <i class="bi bi-shield-x me-1"></i>
                        Esta laptop (<strong>{{ $producto->codigoProducto }}</strong>) tiene su <strong>garantía comercial vencida</strong> ({{ $motivoReemplazo['descripcion'] }}).
                        Puedes reemplazarla por una laptop nueva conservando su <strong>ID #{{ $producto->idProducto }}</strong>.
                    </div>
                    @else
                    <div class="alert alert-warning py-2 px-3 mb-3">
                        <i class="bi bi-recycle me-1"></i>
                        Este producto (<strong>{{ $producto->codigoProducto }}</strong>) nunca tuvo ingresos en almacén.
                        Puedes reemplazarlo por un producto nuevo conservando su <strong>ID #{{ $producto->idProducto }}</strong>.
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Opción 1: Reemplazar automáticamente con SKU / Part Number de Deltron</label>
                        <input type="text" id="swal-deltron-sku" class="form-control text-uppercase" placeholder="Ej: DJ2Z8AT#ABM o NBHPDJ2Z8AT...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Grupo para el nuevo producto (opcional):</label>
                        <select id="swal-deltron-grupo" class="form-select form-select-sm">
                            ${opcionesGrupos}
                        </select>
                        <small class="text-muted">Si cambia de grupo se generará el nuevo código correlativo; si es el mismo grupo conservará {{ $producto->codigoProducto }}.</small>
                    </div>
                    <hr>
                    <div class="text-center">
                        <a href="{{ route('createproducto', ['replace_id' => $producto->idProducto]) }}" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-pencil-square me-1"></i> Opción 2: Ir al Formulario Completo para reemplazar manualmente
                        </a>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-cloud-arrow-down-fill me-1"></i> Reemplazar con Deltron',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-warning fw-semibold ms-2',
                cancelButton: 'btn btn-secondary'
            },
            reverseButtons: true,
            preConfirm: async () => {
                const sku = (document.getElementById('swal-deltron-sku')?.value || '').trim();
                const idGrupo = document.getElementById('swal-deltron-grupo')?.value || '';
                if (!sku) {
                    Swal.showValidationMessage('Ingresa un SKU o Part Number de Deltron, o usa la Opción 2 (Formulario Completo).');
                    return false;
                }
                const urlReplace = "{{ Route::has('producto.reemplazar_deltron') ? route('producto.reemplazar_deltron', $producto->idProducto) : url('/producto/'.$producto->idProducto.'/reemplazar-deltron') }}";
                try {
                    const res = await fetch(urlReplace, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ sku: sku, idGrupo: idGrupo })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        Swal.showValidationMessage(data.message || 'No se pudo reemplazar el producto.');
                        return false;
                    }
                    return data;
                } catch (err) {
                    Swal.showValidationMessage(err.message || 'Error de conexión con el servidor.');
                    return false;
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value && result.value.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Producto Reemplazado!',
                    text: result.value.message,
                    confirmButtonText: 'Recargar ficha'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }
</script>
@endif

