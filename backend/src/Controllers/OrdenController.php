<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\FacturaRepository;
use App\Repositories\OrdenRepository;
use App\Support\ApiException;
use App\Support\Json;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class OrdenController
{
    private const ESTADOS = ['abierta', 'facturada', 'cerrada'];

    public function __construct(private OrdenRepository $ordenes, private FacturaRepository $facturas) {}

    public function listar(Request $req, Response $res): Response
    {
        $q = $req->getQueryParams();
        $estado = $q['estado'] ?? null;

        if ($estado !== null && !in_array($estado, self::ESTADOS, true)) {
            throw new ApiException(422, 'Estado inválido.');
        }
        $pagina = max(1, (int) ($q['pagina'] ?? 1));

        return Json::write($res, $this->ordenes->listar($req->getAttribute('usuario'), $estado, $pagina, 10));
    }

    public function ver(Request $req, Response $res, array $args): Response
    {
        $orden = $this->ordenes->buscar((int) $args['id'], $req->getAttribute('usuario'));

        if ($orden === null) {
            // 404 (no 403) para no confirmar que la orden de otro proveedor existe
            throw new ApiException(404, 'La orden no existe.');
        }

        $orden['facturas'] = $this->facturas->listarPorOrden((int) $orden['id'], $req->getAttribute('usuario'));

        return Json::write($res, $orden);
    }
}