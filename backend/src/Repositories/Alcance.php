<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Support\ApiException;

/**
 * Decide qué datos puede ver cada usuario. Es "fail-closed": solo se conocen dos roles,
 * y cualquier otro (o un proveedor sin proveedor_id) se rechaza en lugar de ver todo.
 */
final class Alcance
{
    /** null = ve todo (admin) · int = solo ve lo de ese proveedor */
    public static function proveedorId(array $usuario): ?int
    {
        return match ($usuario['rol'] ?? null) {
            'admin'     => null,
            'proveedor' => isset($usuario['proveedor_id'])
                ? (int) $usuario['proveedor_id']
                : throw new ApiException(403, 'No tenés permiso para ver estos datos.'),
            default     => throw new ApiException(403, 'No tenés permiso para ver estos datos.'),
        };
    }
}
