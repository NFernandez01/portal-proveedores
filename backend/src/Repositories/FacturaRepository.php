<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class FacturaRepository
{
    private const SELECT = '
        SELECT f.id, f.orden_id, o.numero AS orden_numero, f.proveedor_id, p.razon_social,
               f.tipo, f.punto_venta, f.numero, f.fecha, f.importe, f.estado,
               f.motivo_rechazo, f.archivo, f.created_at
          FROM facturas f
          JOIN ordenes_compra o ON o.id = f.orden_id
          JOIN proveedores p ON p.id = f.proveedor_id';

    public function __construct(private PDO $pdo) {}

    public function listar(array $usuario, ?string $estado): array
    {
        $condiciones = [];
        $params = [];
        if ($usuario['rol'] === 'proveedor') {
            $condiciones[] = 'f.proveedor_id = :pid';
            $params['pid'] = $usuario['proveedor_id'];
        }
        if ($estado !== null) {
            $condiciones[] = 'f.estado = :estado';
            $params['estado'] = $estado;
        }
        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        $st = $this->pdo->prepare(self::SELECT . $where . ' ORDER BY f.created_at DESC');
        $st->execute($params);
        return $st->fetchAll();
    }

    public function listarPorOrden(int $ordenId): array
    {
        $st = $this->pdo->prepare(self::SELECT . ' WHERE f.orden_id = :id ORDER BY f.created_at DESC');
        $st->execute(['id' => $ordenId]);
        return $st->fetchAll();
    }

    public function buscar(int $id, array $usuario): ?array
    {
        $sql = self::SELECT . ' WHERE f.id = :id';
        $params = ['id' => $id];
        if ($usuario['rol'] === 'proveedor') {
            $sql .= ' AND f.proveedor_id = :pid';
            $params['pid'] = $usuario['proveedor_id'];
        }
        $st = $this->pdo->prepare($sql);
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
        $where = '';
        $params = [];
        if ($usuario['rol'] === 'proveedor') {
            $where = 'WHERE proveedor_id = :pid';
            $params['pid'] = $usuario['proveedor_id'];
        }
        $st = $this->pdo->prepare(
            "SELECT estado, COUNT(*) AS cantidad, COALESCE(SUM(importe), 0) AS importe
               FROM facturas {$where}
              GROUP BY estado"
        );
        $st->execute($params);
        return $st->fetchAll();
    }
}