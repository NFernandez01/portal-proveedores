<?php
declare(strict_types=1);

namespace App;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;     
use App\Controllers\FacturaController;       
use App\Controllers\OrdenController;
use App\Middleware\ApiErrorMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;           
use App\Middleware\SecurityHeadersMiddleware;
use App\Repositories\FacturaRepository;      
use App\Repositories\LoginIntentoRepository;
use App\Repositories\OrdenRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AuthService;
use App\Services\FacturaService;             
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

        // facturas, dashboard y carpeta de uploads
        $dirUploads = dirname(__DIR__) . '/storage/uploads';
        $facturaRepo = new FacturaRepository($pdo);
        $facturaController = new FacturaController(
            new FacturaService($pdo, $facturaRepo, $dirUploads),
            $facturaRepo,
            $dirUploads
        );
        $dashboardController = new DashboardController($facturaRepo);

        // también recibe $facturaRepo
        $ordenController = new OrdenController(new OrdenRepository($pdo), $facturaRepo);
        $authMiddleware = new AuthMiddleware($jwt);

        // --- Middlewares globales (el último agregado se ejecuta primero) ---
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(new ApiErrorMiddleware($app->getResponseFactory()));
        $errores = $app->addErrorMiddleware(Config::get('APP_ENV', 'prod') === 'local', true, true);
        $errores->getDefaultErrorHandler()->forceContentType('application/json');
        $app->add(new SecurityHeadersMiddleware());

        // --- Rutas públicas ---
        $app->get('/api/health', function (Request $req, Response $res) use ($pdo): Response {
            $pdo->query('SELECT 1');
            return Json::write($res, ['status' => 'ok']);
        });
        $app->post('/api/auth/login', [$authController, 'login']);
        $app->post('/api/auth/logout', [$authController, 'logout']);

        // --- Rutas protegidas: todo el grupo pasa por AuthMiddleware ---
        $app->group('/api', function (RouteCollectorProxy $api) use ($authController, $ordenController, $facturaController, $dashboardController) {
            $api->get('/me', [$authController, 'me']);
            $api->get('/ordenes', [$ordenController, 'listar']);
            $api->get('/ordenes/{id:[0-9]+}', [$ordenController, 'ver']);

            $api->get('/facturas', [$facturaController, 'listar']);
            $api->get('/facturas/{id:[0-9]+}/archivo', [$facturaController, 'descargar']);
            $api->post('/facturas', [$facturaController, 'crear'])->add(new RoleMiddleware('proveedor'));
            $api->patch('/facturas/{id:[0-9]+}/estado', [$facturaController, 'resolver'])->add(new RoleMiddleware('admin'));
            $api->get('/dashboard', [$dashboardController, 'resumen']);
        })->add($authMiddleware);

        return $app;
    }
}