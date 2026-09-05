@extends('layouts.app')
@section('title', 'Mercado Libre - Imprimir Etiquetas')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                {{-- Título --}}
                <div class="col-xl-6 col-lg-12">
                    <h1 class="h4 mb-0 text-dark fw-bold">
                        <i class="bi bi-tags-fill me-2" style="color:#FFE600"></i>Impresión de Etiquetas ML
                    </h1>
                    <p class="text-muted small mb-0 mt-1">Selecciona hasta 50 órdenes listas para enviar y genera un solo PDF con todas las etiquetas.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card shadow-sm border-0">
        <form action="{{ route('plataformas.ml.labels.bulk') }}" method="POST" target="_blank" id="form-etiquetas-bulk">
            @csrf
            <div class="card-body p-0">
                <div class="d-flex justify-content-between p-3 align-items-center bg-white border-bottom">
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                            <label class="form-check-label fw-bold" for="checkAll">Seleccionar todas las páginas</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm" id="btn-print-selected" disabled>
                        <i class="bi bi-printer-fill"></i> Imprimir Seleccionadas (<span id="selected-count">0</span>)
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4" style="width: 40px;"></th>
                                <th>Orden</th>
                                <th>Comprador</th>
                                <th>Productos</th>
                                <th>Estado Envío</th>
                                <th>Fecha Creación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td class="ps-4">
                                    <div class="form-check">
                                        <input class="form-check-input order-checkbox" type="checkbox" name="shipping_ids[]" value="{{ $order->shipping_id }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $order->ml_order_id }}</div>
                                    <div class="small text-muted">Envío: {{ $order->shipping_id }}</div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $order->buyer_name ?? $order->buyer_nickname ?? '—' }}</div>
                                </td>
                                <td>
                                    <div class="small">
                                        @forelse($order->items->take(2) as $item)
                                        <div class="mb-1">
                                            <span class="d-block text-dark fw-bold text-truncate" style="max-width: 250px;" title="{{ $item->title }}">{{ $item->title }}</span>
                                            <span class="d-block text-muted" style="font-size: 10px;">x{{ $item->quantity }}</span>
                                        </div>
                                        @empty
                                        <span class="text-muted">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 bg-info">Listo para Enviar</span>
                                </td>
                                <td>{{ $order->created_at_ml?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-25"></i>
                                    No hay envíos listos para imprimir etiqueta.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.order-checkbox');
    const btnPrint = document.getElementById('btn-print-selected');
    const selectedCount = document.getElementById('selected-count');

    function updateCount() {
        const count = document.querySelectorAll('.order-checkbox:checked').length;
        selectedCount.textContent = count;
        
        if (count > 0 && count <= 50) {
            btnPrint.disabled = false;
        } else {
            btnPrint.disabled = true;
        }

        if (count > 50) {
            alert('Solo puedes seleccionar un máximo de 50 etiquetas para imprimir a la vez según las reglas de Mercado Libre.');
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateCount();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });
});
</script>
@endsection
