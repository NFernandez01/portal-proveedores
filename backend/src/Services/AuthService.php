<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LoginIntentoRepository;
use App\Repositories\UsuarioRepository;
use App\Support\ApiException;

final class AuthService
{
    private const MAX_INTENTOS = 5;
    private static ?string $hashDeRelleno = null;

    public function __construct(
        private UsuarioRepository $usuarios,
        private LoginIntentoRepository $intentos,
    ) {}

    public function login(string $email, string $password, string $ip): array
    {
        if ($this->intentos->contarRecientes($ip) >= self::MAX_INTENTOS) {
            throw new ApiException(429, 'Demasiados intentos. Probá de nuevo en unos minutos.');
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        // Verificamos SIEMPRE un hash, aunque el usuario no exista, para que la respuesta
        // tarde lo mismo y no se pueda adivinar qué emails están registrados.
        $passwordOk = password_verify($password, $usuario['password_hash'] ?? self::hashDeRelleno());

        if ($usuario === null || !$passwordOk) {
            $this->intentos->registrar($ip);
            throw new ApiException(401, 'Email o contraseña incorrectos.');
        }

        $this->intentos->limpiar($ip);

        return $this->usuarios->buscarPorId((int) $usuario['id']);
    }

    private static function hashDeRelleno(): string
    {
        return self::$hashDeRelleno ??= password_hash('relleno', PASSWORD_BCRYPT, ['cost' => 12]);
    }
}