<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UsuarioRepository
{
    public function __construct(private PDO $pdo) {}

    public function buscarPorEmail(string $email): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT id, email, password_hash, rol, proveedor_id FROM usuarios WHERE email = :email'
        );
        $st->execute(['email' => mb_strtolower(trim($email))]);

        return $st->fetch() ?: null;
    }

    /** Datos públicos del usuario (sin el hash). */
    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT u.id, u.email, u.rol, u.proveedor_id, p.razon_social
               FROM usuarios u
               LEFT JOIN proveedores p ON p.id = u.proveedor_id
              WHERE u.id = :id'
        );
        $st->execute(['id' => $id]);

        return $st->fetch() ?: null;
    }
}