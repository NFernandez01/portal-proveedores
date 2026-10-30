<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LoginIntentoRepository;
use App\Repositories\UsuarioRepository;
use App\Support\ApiException;

final class AuthService
{
    private const MAX_POR_EMAIL = 5;    // fallos desde una IP contra una misma cuenta (15 min)
    private const MAX_POR_IP = 20;      // fallos desde una IP contra cualquier cuenta (15 min)
    private static ?string $hashDeRelleno = null;

    public function __construct(
        private UsuarioRepository $usuarios,
        private LoginIntentoRepository $intentos,
    ) {}

    public function login(string $email, string $password, string $ip): array
    {
        $email = mb_strtolower(trim($email));

        if ($this->intentos->contarPorEmail($ip, $email) >= self::MAX_POR_EMAIL
            || $this->intentos->contarPorIp($ip) >= self::MAX_POR_IP) {
            throw new ApiException(429, 'Demasiados intentos. Probá de nuevo en unos minutos.');
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        // Verificamos SIEMPRE un hash, aunque el usuario no exista, para que la respuesta
        // tarde lo mismo y no se pueda adivinar qué emails están registrados.
        $passwordOk = password_verify($password, $usuario['password_hash'] ?? self::hashDeRelleno());

        if ($usuario === null || !$passwordOk) {
            $this->intentos->registrar($ip, $email);
            throw new ApiException(401, 'Email o contraseña incorrectos.');
        }

        // Solo se limpian los fallos de ESTA cuenta: el login correcto de otra cuenta no resetea nada
        $this->intentos->limpiar($ip, $email);

        return $this->usuarios->buscarPorId((int) $usuario['id']);
    }

    private static function hashDeRelleno(): string
    {
        return self::$hashDeRelleno ??= password_hash('relleno', PASSWORD_BCRYPT, ['cost' => 12]);
    }
}