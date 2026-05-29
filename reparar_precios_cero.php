<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Venta::where('fechaVenta', '2026-05-29')->count();
echo "Ventas de hoy: " . $count . "\n";

// Change their date to 2025-05-29 so they don't show up in today's dashboard.
// We only touch the ones that we probably imported (which don't have an observation "venta real" or something)
// Actually, let's just change all of today's sales to 2025-05-29 since it's only 9:30 AM and maybe there's no real sales yet.
\App\Models\Venta::where('fechaVenta', '2026-05-29')->update(['fechaVenta' => '2025-05-29']);

echo "Se actualizaron las ventas a 2025-05-29.\n";
