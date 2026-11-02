<?php
declare(strict_types=1);

use App\Bootstrap;
use App\Support\Arranque;
use App\Support\Config;
use App\Support\ErrorDeArranque;

require __DIR__ . '/../vendor/autoload.php';

try {
    $pdo = Arranque::conectar();
} catch (ErrorDeArranque $e) {
    // al log siempre el detalle completo; en la respuesta, solo en local
    error_log("[arranque] {$e->getMessage()}. Solución: {$e->solucion}"
        . ($e->getPrevious() ? " Detalle: {$e->getPrevious()->getMessage()}" : ''));

    $body = Config::get('APP_ENV', 'prod') === 'local'
        ? ['error' => $e->getMessage(), 'solucion' => $e->solucion]
        : ['error' => 'Servicio no disponible'];

    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

Bootstrap::crearApp($pdo)->run();
