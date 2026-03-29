<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Ruta al core de la app fuera de public_html
// En Neubox: /home/USER/agendaya_app/
$appPath = __DIR__ . '/../agendaya_app';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once $appPath . '/bootstrap/app.php')
    ->handleRequest(Request::capture());
