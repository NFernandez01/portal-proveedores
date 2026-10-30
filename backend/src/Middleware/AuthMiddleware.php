<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\ApiException;
use App\Support\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private JwtService $jwt) {}

    public function process(Request $request, Handler $handler): Response
    {
        $token = $request->getCookieParams()['token'] ?? '';
        if ($token === '') {
            throw new ApiException(401, 'Tenés que iniciar sesión.');
        }

        try {
            $datos = $this->jwt->validar($token);
        } catch (Throwable) {
            throw new ApiException(401, 'Tu sesión venció. Iniciá sesión de nuevo.');
        }

        // Guardamos el usuario en el request: los controllers lo leen de acá, NUNCA del body.
        $usuario = [
            'id'           => (int) $datos['sub'],
            'rol'          => (string) $datos['rol'],
            'proveedor_id' => $datos['pid'] !== null ? (int) $datos['pid'] : null,
        ];

        return $handler->handle($request->withAttribute('usuario', $usuario));
    }
}