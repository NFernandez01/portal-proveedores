<?php
declare(strict_types=1);

namespace Tests;

use App\Support\Cuit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CuitTest extends TestCase
{
    #[DataProvider('casos')]
    public function test_valida_cuit(string $cuit, bool $esperado): void
    {
        $this->assertSame($esperado, Cuit::esValido($cuit));
    }

    public static function casos(): array
    {
        return [
            'válido sin guiones'   => ['30712345671', true],
            'válido con guiones'   => ['30-71234567-1', true],
            'verificador 0'        => ['30654321090', true],
            'verificador erróneo'  => ['30712345672', false],
            'largo incorrecto'     => ['3071234567', false],
            'vacío'                => ['', false],
        ];
    }
}