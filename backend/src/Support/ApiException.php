<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/** Error esperado (validación, permisos, regla de negocio) que se le muestra al usuario. */
final class ApiException extends RuntimeException
{
    /** @param array<string,string> $errores errores por campo, para mostrar en el formulario */
    public function __construct(
        public readonly int $status,
        string $mensaje,
        public readonly array $errores = [],
    ) {
        parent::__construct($mensaje);
    }
}