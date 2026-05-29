<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\DetalleVenta::where('precioVenta', 0)->count();
echo "Total: $count\n";

$hoy = \App\Models\DetalleVenta::where('precioVenta', 0)
    ->whereHas('Venta', function($q) {
        // Assuming Venta has fechaVenta or we can just check idVenta order
        $q->where('idVenta', '>', 0);
    })->count();

$c = \App\Models\DetalleVenta::where('precioVenta', 0)->with('Venta')->get()->groupBy('Venta.canal')->map->count();
print_r($c->toArray());
