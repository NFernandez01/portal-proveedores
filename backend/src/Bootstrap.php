<?php
declare(strict_types=1);

namespace App;

use App\Support\Config;
use App\Support\Json;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;

final class Bootstrap
{
    /** Arma la app completa. Recibe el PDO de afuera para poder usar otra conexión en los tests. */
    public static function crearApp(PDO $pdo): App
    {
        $app = AppFactory::create();

        $app->addBodyParsingMiddleware();   // convierte el JSON del body en array
        $app->addRoutingMiddleware();

        $mostrarDetalles = Config::get('APP_ENV', 'prod') === 'local';
        $errores = $app->addErrorMiddleware($mostrarDetalles, true, true);
        $errores->getDefaultErrorHandler()->forceContentType('application/json');

        $app->get('/api/health', function (Request $req, Response $res) use ($pdo): Response {
            $pdo->query('SELECT 1');
            return Json::write($res, ['status' => 'ok']);
        });

        return $app;
    }
}