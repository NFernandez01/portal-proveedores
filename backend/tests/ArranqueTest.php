<?php
declare(strict_types=1);

namespace Tests;

use App\Support\Arranque;
use App\Support\ErrorDeArranque;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArranqueTest extends TestCase
{
    #[DataProvider('erroresDePdo')]
    public function test_explica_error_de_conexion(string $detalle, string $esperado): void
    {
        $error = Arranque::explicar(new PDOException($detalle));

        $this->assertStringContainsString($esperado, $error->getMessage());
        $this->assertNotSame('', $error->solucion);
    }

    public static function erroresDePdo(): array
    {
        return [
            'contraseña'     => ['SQLSTATE[08006] [7] connection to server at "db" failed: FATAL:  password authentication failed for user "x"', 'rechazó el usuario'],
            'usuario'        => ['FATAL:  role "x" does not exist', 'no existe en Postgres'],
            'base'           => ['FATAL:  database "x" does not exist', 'La base'],
            'host'           => ['could not translate host name "dbb" to address', 'No se encuentra'],
            'puerto'         => ['connection to server at "db", port 5434 failed: Connection refused', 'no responde'],
            'driver'         => ['could not find driver', 'pdo_pgsql'],
            'desconocido'    => ['algo raro', 'No se pudo conectar'],
        ];
    }

    public function test_rechaza_jwt_secret_corto(): void
    {
        $original = getenv('JWT_SECRET');
        putenv('JWT_SECRET=corto');
        unset($_ENV['JWT_SECRET']);

        try {
            Arranque::verificarEntorno();
            $this->fail('Debería haber lanzado ErrorDeArranque');
        } catch (ErrorDeArranque $e) {
            $this->assertStringContainsString('JWT_SECRET', $e->getMessage());
        } finally {
            putenv($original === false ? 'JWT_SECRET' : "JWT_SECRET={$original}");
        }
    }

    public function test_avisa_variables_faltantes(): void
    {
        $original = getenv('DB_HOST');
        putenv('DB_HOST');
        unset($_ENV['DB_HOST']);

        try {
            Arranque::verificarEntorno();
            $this->fail('Debería haber lanzado ErrorDeArranque');
        } catch (ErrorDeArranque $e) {
            $this->assertStringContainsString('DB_HOST', $e->getMessage());
        } finally {
            putenv($original === false ? 'DB_HOST' : "DB_HOST={$original}");
        }
    }
}
