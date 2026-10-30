<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\ApiException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(private string $rolRequerido) {}

    public function process(Request $request, Handler $handler): Response
    {
        $rol = $request->getAttribute('usuario')['rol'] ?? null;

        if ($rol !== $this->rolRequerido) {
            throw new ApiException(403, 'No tenés permiso para hacer esto.');
        }

        return $handler->handle($request);
    }
}