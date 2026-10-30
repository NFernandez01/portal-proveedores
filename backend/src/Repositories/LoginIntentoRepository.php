<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class LoginIntentoRepository
{
    public function __construct(private PDO $pdo) {}

    public function contarRecientes(string $ip, int $minutos = 15): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM login_intentos
              WHERE ip = :ip AND creado > now() - make_interval(mins => :minutos)"
        );
        $st->execute(['ip' => $ip, 'minutos' => $minutos]);

        return (int) $st->fetchColumn();
    }

    public function registrar(string $ip): void
    {
        $this->pdo->prepare('INSERT INTO login_intentos (ip) VALUES (:ip)')->execute(['ip' => $ip]);
    }

    public function limpiar(string $ip): void
    {
        $this->pdo->prepare('DELETE FROM login_intentos WHERE ip = :ip')->execute(['ip' => $ip]);
    }
}