<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\ApiException;
use App\Support\Json;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class ApiErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $factory) {}

    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (ApiException $e) {
            $body = ['error' => $e->getMessage()];
            if ($e->errores !== []) {
                $body['errores'] = $e->errores;
            }
            return Json::write($this->factory->createResponse(), $body, $e->status);
        }
    }
}