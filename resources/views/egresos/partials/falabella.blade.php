<div class="table-responsive">
    <table class="table table-custom table-hover m-0">
        <thead>
            <tr>
                <th class="bg-sistema-uno ps-4">Orden Falabella</th>
                <th class="bg-sistema-uno">Cliente</th>
                <th class="bg-sistema-uno" style="min-width: 200px;">Producto(s)</th>
                <th class="bg-sistema-uno">Monto</th>
                <th class="bg-sistema-uno">Fecha</th>
                <th class="bg-sistema-uno">Estado</th>
                <th class="bg-sistema-uno text-center pe-4">Acción</th>
            </tr>
        </thead>
        <tbody>
            @forelse($falabellaPendientes as $orden)
            <tr>
                <td class="ps-4 fw-bold text-dark">{{ $orden->order_number }}</td>
                <td>
                    <div class="fw-semibold text-dark">{{ $orden->customer_name }}</div>
                    <small class="text-muted">{{ $orden->customer_email }}</small>
                </td>
                <td>
                    @foreach($orden->items as $item)
                        <div class="mb-1" style="line-height: 1.2;">
                            <small class="fw-semibold text-dark">{{ $item->name }}</small>
                            <span class="badge bg-secondary ms-1">x{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                </td>
                <td class="text-success fw-bold">S/ {{ number_format($orden->price, 2) }}</td>
                <td class="text-muted">{{ $orden->created_at_falabella ? $orden->created_at_falabella->format('d/m/Y H:i') : 'N/A' }}</td>
                <td><span class="badge bg-success bg-opacity-10 text-white border border-success">Entregado</span></td>
                <td class="text-center pe-4">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarTexto('{{ $orden->order_number }}')" title="Copiar Orden" data-bs-toggle="tooltip">
                            <i class="bi bi-clipboard"></i>
                        </button>
                        <a href="{{ route('egresos', ['month' => now()->format('Y-m')]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Ir a Egresos" data-bs-toggle="tooltip">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-5">
                    <i class="bi bi-cart-x fs-2 d-block mb-2 text-light"></i>
                    No hay egresos pendientes de Falabella.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
