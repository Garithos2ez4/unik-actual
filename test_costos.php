<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tc = app('App\Services\CalculadoraServiceInterface')->getTasaCambio();
$c = app('App\Http\Controllers\GananciaController');
$res = $c->getAllGanancias()->getData();
foreach($res->data as $v) {
    if ($v->idVenta == 2418) {
        echo "GananciaController: " . json_encode($v) . "\n";
    }
}

$request = new \Illuminate\Http\Request();
$request->merge(['anio' => 2026, 'mes' => 6, 'dia_inicio' => '2026-06-24', 'dia_fin' => '2026-06-24']);
$c2 = app('App\Http\Controllers\AnalyticsController');
$data = $c2->tiendaData($request)->getData();
$ventas = $data['ventasTienda'];
foreach($ventas as $v) {
    if ($v->idVenta == 2418) {
        echo "AnalyticsController: " . json_encode($v) . "\n";
    }
}
