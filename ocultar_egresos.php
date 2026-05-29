<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obtener todas las Ventas que movimos a 2025
$ventasImportadas = \App\Models\Venta::where('fechaVenta', '2025-05-29')->pluck('idVenta');

// Obtener los idEgreso vinculados a estas ventas
$detalles = \App\Models\DetalleVenta::whereIn('idVenta', $ventasImportadas)
    ->whereNotNull('idEgreso')
    ->get(['idEgreso']);

$idEgresos = $detalles->pluck('idEgreso')->unique()->toArray();

if (count($idEgresos) > 0) {
    // Update EgresoProducto
    \App\Models\EgresoProducto::whereIn('idEgreso', $idEgresos)
        ->update([
            'fechaCompra' => '2025-05-29',
            'fechaDespacho' => '2025-05-29'
        ]);

    // Update RegistroProducto
    $idRegistros = \App\Models\EgresoProducto::whereIn('idEgreso', $idEgresos)->pluck('idRegistro')->toArray();

    if (count($idRegistros) > 0) {
        \App\Models\RegistroProducto::whereIn('idRegistro', $idRegistros)
            ->update(['fechaMovimiento' => '2025-05-29']);
    }

    echo "Se han retrocedido a 2025-05-29 exactamente " . count($idEgresos) . " egresos y sus movimientos de inventario.\n";
} else {
    echo "No se encontraron egresos vinculados a las ventas importadas.\n";
}
