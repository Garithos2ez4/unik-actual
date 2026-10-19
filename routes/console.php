<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

use Illuminate\Support\Facades\Schedule;

Schedule::command('exchange:update-daily')->dailyAt('11:00');
Schedule::command('bot:falabella-prices')->dailyAt('10:00');
// Sincronización oficial Deltron B2B:
// 1. Catálogo completo en horario de baja demanda (02:00 AM) - Base de datos con tránsito, specs, imágenes, precios y stock.
Schedule::command('deltron:sync --tipo=catalogo')->dailyAt('02:00');

// 2. Corte de inicio de día (06:00 AM - 4h después) - Refresco ligero de precios y stock matutino.
Schedule::command('deltron:sync --tipo=inicio-dia')->dailyAt('06:00');
