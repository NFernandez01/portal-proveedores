<?php
declare(strict_types=1);

namespace Tests;

use App\Repositories\FacturaRepository;
use App\Repositories\OrdenRepository;
use App\Support\ApiException;
use App\Support\Database;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Tests directos de los repositorios: alcance por usuario (fail-closed) y reglas de la base. */
final class RepositoriosTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connect();
    }

    public function test_un_rol_desconocido_no_ve_nada_y_da_403(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('No tenés permiso');

        (new OrdenRepository(self::$pdo))->listar(['rol' => 'auditor', 'proveedor_id' => null], null, 1, 10);
    }

    public function test_un_proveedor_sin_proveedor_id_da_403(): void
    {
        try {
            (new FacturaRepository(self::$pdo))->listar(['rol' => 'proveedor', 'proveedor_id' => null], null);
            $this->fail('Tendría que haber lanzado ApiException');
        } catch (ApiException $e) {
            $this->assertSame(403, $e->status);
        }
    }

    public function test_listar_por_orden_no_devuelve_facturas_de_otro_proveedor(): void
    {
        // La orden 1 es del proveedor 1: el proveedor 2 no tiene que ver sus facturas
        $facturas = (new FacturaRepository(self::$pdo))->listarPorOrden(1, ['rol' => 'proveedor', 'proveedor_id' => 2]);
        $this->assertSame([], $facturas);
    }

    public function test_las_facturas_no_exponen_el_nombre_interno_del_pdf(): void
    {
        $facturas = (new FacturaRepository(self::$pdo))->listar(['rol' => 'admin', 'proveedor_id' => null], null);
        foreach ($facturas as $f) {
            $this->assertArrayNotHasKey('archivo', $f);
        }
        $this->assertTrue(true);   // también pasa si no hay facturas cargadas
    }

    public function test_la_base_rechaza_emails_con_mayusculas(): void
    {
        self::$pdo->beginTransaction();   // se deshace al final: no deja datos de prueba
        try {
            self::$pdo->exec(
                "INSERT INTO usuarios (email, password_hash, rol) VALUES ('Mayus@Portal.test', 'x', 'admin')"
            );
            $this->fail('La base tendría que haber rechazado el email con mayúsculas');
        } catch (PDOException $e) {
            $this->assertSame('23514', $e->getCode());   // 23514 = violación de un CHECK
        } finally {
            self::$pdo->rollBack();
        }
    }
}
