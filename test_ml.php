<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$api = app(App\Services\MercadoLibreApiService::class);
$cred = App\Models\Ecommerce\MercadoLibreCredential::where('seller_nickname', 'UNIK TECHNOLOGY')->first();
if ($cred) {
    try {
        echo json_encode($api->getOrder($cred->seller_id, '2000018560542590'), JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
