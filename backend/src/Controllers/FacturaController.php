<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\FacturaRepository;
use App\Services\FacturaService;
use App\Support\ApiException;
use App\Support\Json;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class FacturaController
{
    private const ESTADOS = ['pendiente', 'aprobada', 'rechazada'];

    public function __construct(
        private FacturaService $servicio,
        private FacturaRepository $facturas,
        private string $dirUploads,
    ) {}

    public function listar(Request $req, Response $res): Response
    {
        $estado = $req->getQueryParams()['estado'] ?? null;
        if ($estado !== null && !in_array($estado, self::ESTADOS, true)) {
            throw new ApiException(422, 'Estado inválido.');
        }
        return Json::write($res, $this->facturas->listar($req->getAttribute('usuario'), $estado));
    }

    public function crear(Request $req, Response $res): Response
    {
        $factura = $this->servicio->crear(
            $req->getAttribute('usuario'),
            (array) $req->getParsedBody(),
            $req->getUploadedFiles()['archivo'] ?? null,
        );
        return Json::write($res, $factura, 201);
    }

    public function resolver(Request $req, Response $res, array $args): Response
    {
        $this->servicio->resolver((int) $args['id'], (array) $req->getParsedBody());
        return Json::write($res, ['ok' => true]);
    }

    public function descargar(Request $req, Response $res, array $args): Response
    {
        // archivoDe() aplica el mismo alcance que el resto: un proveedor no baja PDFs ajenos
        $factura = $this->facturas->archivoDe((int) $args['id'], $req->getAttribute('usuario'));
        if ($factura === null) {
            throw new ApiException(404, 'La factura no existe.');
        }

        $ruta = $this->dirUploads . '/' . basename($factura['archivo']);
        if (!is_file($ruta)) {
            throw new ApiException(404, 'El archivo no está disponible.');
        }

        // Si existe pero no se puede leer (permisos), error controlado en vez de un warning de PHP
        $contenido = is_readable($ruta) ? file_get_contents($ruta) : false;
        if ($contenido === false) {
            error_log("No se pudo leer el comprobante de la factura {$factura['id']}: {$ruta}");
            throw new ApiException(500, 'No pudimos abrir el comprobante. Avisá al administrador.');
        }

        $res->getBody()->write($contenido);
        return $res
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="factura-' . $factura['id'] . '.pdf"');
    }
}