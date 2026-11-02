<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use Throwable;

/** La API no puede arrancar: trae qué pasó y cómo solucionarlo. */
final class ErrorDeArranque extends RuntimeException
{
    public function __construct(string $mensaje, public readonly string $solucion, ?Throwable $previa = null)
    {
        parent::__construct($mensaje, 0, $previa);
    }
}
