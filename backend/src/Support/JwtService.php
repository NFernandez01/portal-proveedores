<?php
declare(strict_types=1);

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtService
{
    private const ALGORITMO = 'HS256';

    public function __construct(
        private string $secreto,
        private int $ttlMinutos,
    ) {}

    /** @param array{id:int|string, rol:string, proveedor_id:int|string|null} $usuario */
    public function emitir(array $usuario): string
    {
        $ahora = time();

        return JWT::encode([
            'sub' => (int) $usuario['id'],
            'rol' => $usuario['rol'],
            'pid' => $usuario['proveedor_id'] !== null ? (int) $usuario['proveedor_id'] : null,
            'iat' => $ahora,
            'exp' => $ahora + $this->ttlSegundos(),
        ], $this->secreto, self::ALGORITMO);
    }

    /** Devuelve los datos del token. Lanza una excepción si la firma no coincide o está vencido. */
    public function validar(string $token): array
    {
        return (array) JWT::decode($token, new Key($this->secreto, self::ALGORITMO));
    }

    public function ttlSegundos(): int
    {
        return $this->ttlMinutos * 60;
    }
}