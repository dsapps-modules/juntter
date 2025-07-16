<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Detecta se está em ambiente de produção (Hostinger)
$isProduction = strpos(__DIR__, 'public_html') !== false;

// Define o caminho base do projeto conforme o ambiente
$basePath = $isProduction
    ? __DIR__ . '/../../juntter_checkout'
    : __DIR__ . '/../';

if (file_exists($maintenance = $basePath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
