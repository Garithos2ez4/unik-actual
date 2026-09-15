<div class="table-responsive">
    <table class="table table-custom table-hover m-0">
        <thead>
            <tr>
                <th class="bg-sistema-uno ps-4">Orden ML</th>
                <th class="bg-sistema-uno">Comprador</th>
                <th class="bg-sistema-uno" style="min-width: 200px;">Producto(s)</th>
                <th class="bg-sistema-uno">Monto</th>
                <th class="bg-sistema-uno">Fecha</th>
                <th class="bg-sistema-uno">Estado</th>
                <th class="bg-sistema-uno text-center pe-4">Acción</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mlPendientes as $orden)
            <tr>
                <td class="ps-4 fw-bold text-dark">{{ $orden->ml_order_id }}</td>
                <td>
                    <div class="fw-semibold text-dark">{{ $orden->buyer_name ?? $orden->buyer_nickname }}</div>
                    <small class="text-muted">{{ $orden->buyer_nickname }}</small>
                </td>
                <td>
                    @foreach($orden->items as $item)
                    <div class="mb-1" style="line-height: 1.2;">
                        <small class="fw-semibold text-dark">{{ $item->title }}</small>
                        <span class="badge bg-secondary ms-1">x{{ $item->quantity }}</span>
                    </div>
                    @endforeach
                </td>
                <td class="text-success fw-bold">S/ {{ number_format($orden->total_amount, 2) }}</td>
                <td class="text-muted">{{ $orden->created_at_ml ? $orden->created_at_ml->format('d/m/Y H:i') : 'N/A' }}</td>
                <td><span class="badge  bg-opacity-10 text-primary border border-primary">{{ ucfirst($orden->status) }}</span></td>
                <td class="text-center pe-4">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarTexto('{{ $orden->ml_order_id }}')" title="Copiar Orden" data-bs-toggle="tooltip">
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
                    <i class="bi bi-bag-x fs-2 d-block mb-2 text-light"></i>
                    No hay egresos pendientes de Mercado Libre.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>