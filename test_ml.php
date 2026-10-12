<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$api = app(App\Services\MercadoLibreApiService::class);
$creds = App\Models\Ecommerce\MercadoLibreCredential::all();
foreach ($creds as $cred) {
    try {
        $result = $api->getOrder($cred->seller_id, '2000018792980742');
        echo "Exito con cuenta: " . $cred->seller_nickname . "\n";
        echo json_encode($result, JSON_PRETTY_PRINT);
        break;
    } catch (\Exception $e) {
        // Ignorar si no es dueño
    }
}
