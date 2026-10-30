<?php
declare(strict_types=1);

namespace Tests;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\UploadedFile;

final class FacturasTest extends ApiTestCase
{
    private function pdfTemporal(string $contenido = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n"): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'fac');
        file_put_contents($ruta, $contenido);
        return new UploadedFile($ruta, 'factura.pdf', 'application/pdf', filesize($ruta), UPLOAD_ERR_OK);
    }

    private function cargar(string $token, array $campos, ?UploadedFile $archivo = null): ResponseInterface
    {
        $req = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/facturas', ['REMOTE_ADDR' => '10.1.1.1'])
            ->withCookieParams(['token' => $token])
            ->withParsedBody($campos)
            ->withUploadedFiles(['archivo' => $archivo ?? $this->pdfTemporal()]);

        return static::$app->handle($req);
    }

    private function camposValidos(int $orden = 1): array
    {
        return ['orden_id' => (string) $orden, 'tipo' => 'A', 'punto_venta' => '1',
                'numero' => (string) random_int(1000, 99999999), 'fecha' => '2026-10-01', 'importe' => '1.00'];
    }

    public function test_proveedor_carga_factura_valida(): void
    {
        $res = $this->cargar($this->login('proveedor1@portal.test'), $this->camposValidos());
        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame('pendiente', $this->json($res)['estado']);
        // El nombre interno del PDF es un detalle del servidor: no se expone al cliente
        $this->assertArrayNotHasKey('archivo', $this->json($res));
    }

    public function test_factura_duplicada_da_409(): void
    {
        $token = $this->login('proveedor1@portal.test');
        $campos = $this->camposValidos();

        $this->cargar($token, $campos);
        $this->assertSame(409, $this->cargar($token, $campos)->getStatusCode());
    }

    public function test_acepta_importe_con_coma_decimal(): void
    {
        $campos = ['importe' => '1,50'] + $this->camposValidos();
        $res = $this->cargar($this->login('proveedor1@portal.test'), $campos);

        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame('1.50', $this->json($res)['importe']);
    }

    public function test_importe_mayor_al_saldo_da_422(): void
    {
        $campos = ['importe' => '99999999.00'] + $this->camposValidos();
        $res = $this->cargar($this->login('proveedor1@portal.test'), $campos);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertArrayHasKey('importe', $this->json($res)['errores']);
    }

    public function test_archivo_que_no_es_pdf_da_422(): void
    {
        $falso = $this->pdfTemporal('esto es texto, no un PDF');
        $res = $this->cargar($this->login('proveedor1@portal.test'), $this->camposValidos(), $falso);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertArrayHasKey('archivo', $this->json($res)['errores']);
    }

    public function test_no_puede_cargar_en_orden_ajena(): void
    {
        $res = $this->cargar($this->login('proveedor1@portal.test'), $this->camposValidos(4));
        $this->assertSame(404, $res->getStatusCode());
    }

    public function test_admin_no_puede_cargar_facturas(): void
    {
        $res = $this->cargar($this->login('admin@portal.test'), $this->camposValidos());
        $this->assertSame(403, $res->getStatusCode());
    }

    public function test_rechazo_sin_motivo_da_422_y_con_motivo_200(): void
    {
        $id = $this->json($this->cargar($this->login('proveedor1@portal.test'), $this->camposValidos()))['id'];
        $admin = $this->login('admin@portal.test');

        $sinMotivo = $this->request('PATCH', "/api/facturas/{$id}/estado", ['estado' => 'rechazada'], $admin);
        $this->assertSame(422, $sinMotivo->getStatusCode());

        $conMotivo = $this->request('PATCH', "/api/facturas/{$id}/estado",
            ['estado' => 'rechazada', 'motivo' => 'Monto incorrecto'], $admin);
        $this->assertSame(200, $conMotivo->getStatusCode());

        // Procesarla de nuevo → 409
        $otraVez = $this->request('PATCH', "/api/facturas/{$id}/estado", ['estado' => 'aprobada'], $admin);
        $this->assertSame(409, $otraVez->getStatusCode());
    }
}