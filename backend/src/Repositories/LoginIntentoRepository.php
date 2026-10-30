<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Intentos fallidos de login, para el rate limit. Se cuentan por IP + email
 * (y también por IP en general), así un login correcto de OTRA cuenta no resetea el contador.
 */
final class LoginIntentoRepository
{
    public function __construct(private PDO $pdo) {}

    /** Fallos recientes desde esta IP contra este email. */
    public function contarPorEmail(string $ip, string $email, int $minutos = 15): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM login_intentos
              WHERE ip = :ip AND email = :email AND creado > now() - make_interval(mins => :minutos)"
        );
        $st->execute(['ip' => $ip, 'email' => $email, 'minutos' => $minutos]);

        return (int) $st->fetchColumn();
    }

    /** Fallos recientes desde esta IP contra cualquier cuenta (frena probar muchas cuentas). */
    public function contarPorIp(string $ip, int $minutos = 15): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM login_intentos
              WHERE ip = :ip AND creado > now() - make_interval(mins => :minutos)"
        );
        $st->execute(['ip' => $ip, 'minutos' => $minutos]);

        return (int) $st->fetchColumn();
    }

    public function registrar(string $ip, string $email): void
    {
        $this->pdo->prepare('INSERT INTO login_intentos (ip, email) VALUES (:ip, :email)')
            ->execute(['ip' => $ip, 'email' => $email]);
    }

    /** Tras un login correcto se borran SOLO los fallos de esa cuenta desde esa IP. */
    public function limpiar(string $ip, string $email): void
    {
        $this->pdo->prepare('DELETE FROM login_intentos WHERE ip = :ip AND email = :email')
            ->execute(['ip' => $ip, 'email' => $email]);
    }
}
