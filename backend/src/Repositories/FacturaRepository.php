<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class FacturaRepository
{
    // Columnas que se devuelven al frontend. NO incluye "archivo": el nombre interno
    // del PDF es un detalle del servidor y el cliente no lo necesita.
    private const SELECT = '
        SELECT f.id, f.orden_id, o.numero AS orden_numero, f.proveedor_id, p.razon_social,
               f.tipo, f.punto_venta, f.numero, f.fecha, f.importe, f.estado,
               f.motivo_rechazo, f.created_at
          FROM facturas f
          JOIN ordenes_compra o ON o.id = f.orden_id
          JOIN proveedores p ON p.id = f.proveedor_id';

    public function __construct(private PDO $pdo) {}

    public function listar(array $usuario, ?string $estado): array
    {
        [$condiciones, $params] = $this->alcance($usuario);
        if ($estado !== null) {
            $condiciones[] = 'f.estado = :estado';
            $params['estado'] = $estado;
        }

        $st = $this->pdo->prepare(self::SELECT . self::where($condiciones) . ' ORDER BY f.created_at DESC');
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Facturas de una orden. Filtra por el usuario: es seguro aunque se llame desde cualquier lado. */
    public function listarPorOrden(int $ordenId, array $usuario): array
    {
        [$condiciones, $params] = $this->alcance($usuario);
        $condiciones[] = 'f.orden_id = :orden_id';
        $params['orden_id'] = $ordenId;

        $st = $this->pdo->prepare(self::SELECT . self::where($condiciones) . ' ORDER BY f.created_at DESC');
        $st->execute($params);
        return $st->fetchAll();
    }

    public function buscar(int $id, array $usuario): ?array
    {
        [$condiciones, $params] = $this->alcance($usuario);
        $condiciones[] = 'f.id = :id';
        $params['id'] = $id;

        $st = $this->pdo->prepare(self::SELECT . self::where($condiciones));
        $st->execute($params);
        return $st->fetch() ?: null;
    }

    /** Solo para la descarga: devuelve el nombre interno del PDF si el usuario puede verlo. */
    public function archivoDe(int $id, array $usuario): ?array
    {
        [$condiciones, $params] = $this->alcance($usuario);
        $condiciones[] = 'f.id = :id';
        $params['id'] = $id;

        $st = $this->pdo->prepare('SELECT f.id, f.archivo FROM facturas f' . self::where($condiciones));
        $st->execute($params);
        return $st->fetch() ?: null;
    }

    /**
     * Bloquea la fila de la orden hasta el COMMIT. Si llegan dos cargas al mismo tiempo,
     * la segunda espera a que termine la primera, y así ve el saldo actualizado.
     */
    public function bloquearOrden(int $ordenId, int $proveedorId): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT id, total FROM ordenes_compra WHERE id = :id AND proveedor_id = :pid FOR UPDATE'
        );
        $st->execute(['id' => $ordenId, 'pid' => $proveedorId]);
        return $st->fetch() ?: null;
    }

    /** Suma de lo facturado en la orden (sin contar las rechazadas). */
    public function totalFacturado(int $ordenId): string
    {
        $st = $this->pdo->prepare(
            "SELECT COALESCE(SUM(importe), 0) FROM facturas WHERE orden_id = :id AND estado <> 'rechazada'"
        );
        $st->execute(['id' => $ordenId]);
        return (string) $st->fetchColumn();
    }

    public function crear(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO facturas (orden_id, proveedor_id, tipo, punto_venta, numero, fecha, importe, archivo)
             VALUES (:orden_id, :proveedor_id, :tipo, :punto_venta, :numero, :fecha, :importe, :archivo)
             RETURNING id'
        );
        $st->execute($d);
        return (int) $st->fetchColumn();
    }

    /** Cambia el estado SOLO si sigue pendiente. Devuelve false si ya la había procesado otro. */
    public function resolver(int $id, string $estado, ?string $motivo): bool
    {
        $st = $this->pdo->prepare(
            "UPDATE facturas SET estado = :estado, motivo_rechazo = :motivo
              WHERE id = :id AND estado = 'pendiente'
             RETURNING id"
        );
        $st->execute(['estado' => $estado, 'motivo' => $motivo, 'id' => $id]);
        return $st->fetchColumn() !== false;
    }

    public function resumen(array $usuario): array
    {
        [$condiciones, $params] = $this->alcance($usuario);

        $st = $this->pdo->prepare(
            'SELECT f.estado, COUNT(*) AS cantidad, COALESCE(SUM(f.importe), 0) AS importe
               FROM facturas f' . self::where($condiciones) . '
              GROUP BY f.estado'
        );
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * Condición de alcance por usuario, en un solo lugar (antes estaba repetida en 3 métodos).
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function alcance(array $usuario): array
    {
        $proveedorId = Alcance::proveedorId($usuario);   // fail-closed: rol desconocido → 403
        return $proveedorId === null
            ? [[], []]
            : [['f.proveedor_id = :pid'], ['pid' => $proveedorId]];
    }

    /** @param string[] $condiciones fragmentos de SQL fijos (nunca datos del usuario) */
    private static function where(array $condiciones): string
    {
        return $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);
    }
}
