<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\FacturaRepository;
use App\Support\Json;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DashboardController
{
    public function __construct(private FacturaRepository $facturas) {}

    public function resumen(Request $req, Response $res): Response
    {
        $porEstado = ['pendiente' => ['cantidad' => 0, 'importe' => '0'],
                      'aprobada'  => ['cantidad' => 0, 'importe' => '0'],
                      'rechazada' => ['cantidad' => 0, 'importe' => '0']];

        foreach ($this->facturas->resumen($req->getAttribute('usuario')) as $fila) {
            $porEstado[$fila['estado']] = ['cantidad' => (int) $fila['cantidad'], 'importe' => $fila['importe']];
        }

        return Json::write($res, ['facturas' => $porEstado]);
    }
}