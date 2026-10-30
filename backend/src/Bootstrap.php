<?php
declare(strict_types=1);

namespace App;

use App\Controllers\AuthController;
use App\Controllers\OrdenController;
use App\Middleware\ApiErrorMiddleware;
use App\Middleware\AuthMiddleware;
use App\Repositories\LoginIntentoRepository;
use App\Repositories\OrdenRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AuthService;
use App\Support\Config;
use App\Support\Json;
use App\Support\JwtService;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

final class Bootstrap
{
    public static function crearApp(PDO $pdo): App
    {
        $app = AppFactory::create();

        // --- Dependencias (inyección manual, sin contenedor) ---
        $jwt = new JwtService(Config::get('JWT_SECRET'), (int) Config::get('JWT_TTL_MIN', '60'));
        $usuarios = new UsuarioRepository($pdo);
        $authController = new AuthController(
            new AuthService($usuarios, new LoginIntentoRepository($pdo)),
            $jwt,
            $usuarios
        );
        $ordenController = new OrdenController(new OrdenRepository($pdo));
        $authMiddleware = new AuthMiddleware($jwt);

        // --- Middlewares globales (el último agregado se ejecuta primero) ---
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(new ApiErrorMiddleware($app->getResponseFactory()));
        $errores = $app->addErrorMiddleware(Config::get('APP_ENV', 'prod') === 'local', true, true);
        $errores->getDefaultErrorHandler()->forceContentType('application/json');

        // --- Rutas públicas ---
        $app->get('/api/health', function (Request $req, Response $res) use ($pdo): Response {
            $pdo->query('SELECT 1');
            return Json::write($res, ['status' => 'ok']);
        });
        $app->post('/api/auth/login', [$authController, 'login']);
        $app->post('/api/auth/logout', [$authController, 'logout']);

        // --- Rutas protegidas: todo el grupo pasa por AuthMiddleware ---
        $app->group('/api', function (RouteCollectorProxy $api) use ($authController, $ordenController) {
            $api->get('/me', [$authController, 'me']);
            $api->get('/ordenes', [$ordenController, 'listar']);
            $api->get('/ordenes/{id:[0-9]+}', [$ordenController, 'ver']);
        })->add($authMiddleware);

        return $app;
    }
}