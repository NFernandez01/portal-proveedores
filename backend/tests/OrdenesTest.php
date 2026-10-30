<?php
declare(strict_types=1);

namespace Tests;

final class OrdenesTest extends ApiTestCase
{
    public function test_proveedor_solo_ve_sus_ordenes(): void
    {
        $token = $this->login('proveedor1@portal.test');
        $datos = $this->json($this->request('GET', '/api/ordenes', [], $token));

        $this->assertNotEmpty($datos['items']);
        foreach ($datos['items'] as $orden) {
            $this->assertSame(1, $orden['proveedor_id']);
        }
    }

    public function test_proveedor_no_puede_ver_orden_ajena(): void
    {
        $token = $this->login('proveedor1@portal.test');
        // La OC-0004 (id 4) es del proveedor 2
        $this->assertSame(404, $this->request('GET', '/api/ordenes/4', [], $token)->getStatusCode());
    }

    public function test_admin_ve_todas_las_ordenes(): void
    {
        $token = $this->login('admin@portal.test');
        $datos = $this->json($this->request('GET', '/api/ordenes', [], $token));

        $this->assertSame(5, $datos['total']);
    }

    public function test_estado_invalido_da_422(): void
    {
        $token = $this->login('admin@portal.test');
        $this->assertSame(422, $this->request('GET', '/api/ordenes?estado=cualquiera', [], $token)->getStatusCode());
    }
}