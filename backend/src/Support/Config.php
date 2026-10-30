<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Config
{
    /** Lee una variable de entorno. Si falta y no hay default, falla en voz alta. */
    public static function get(string $clave, ?string $default = null): string
    {
        $valor = $_ENV[$clave] ?? getenv($clave);

        if ($valor === false || $valor === '') {
            if ($default !== null) {
                return $default;
            }
            throw new RuntimeException("Falta la variable de entorno {$clave}");
        }

        return (string) $valor;
    }
}