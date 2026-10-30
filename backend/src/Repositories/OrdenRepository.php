<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OrdenRepository
{
    private const SELECT = "
        SELECT o.id, o.numero, o.fecha, o.total, o.estado, o.proveedor_id, p.razon_social,
               o.total - COALESCE(SUM(f.importe) FILTER (WHERE f.estado <> 'rechazada'), 0) AS saldo
          FROM ordenes_compra o
          JOIN proveedores p ON p.id = o.proveedor_id
          LEFT JOIN facturas f ON f.orden_id = o.id";

    public function __construct(private PDO $pdo) {}

    /** @return array{items: array, total: int, pagina: int, porPagina: int} */
    public function listar(array $usuario, ?string $estado, int $pagina, int $porPagina): array
    {
        [$where, $params] = $this->filtros($usuario, $estado);

        $st = $this->pdo->prepare("SELECT COUNT(*) FROM ordenes_compra o {$where}");
        $st->execute($params);
        $total = (int) $st->fetchColumn();

        $st = $this->pdo->prepare(self::SELECT . " {$where}
             GROUP BY o.id, p.id
             ORDER BY o.fecha DESC, o.id DESC
             LIMIT :limite OFFSET :offset");
        foreach ($params as $clave => $valor) {
            $st->bindValue($clave, $valor);
        }
        $st->bindValue('limite', $porPagina, PDO::PARAM_INT);
        $st->bindValue('offset', ($pagina - 1) * $porPagina, PDO::PARAM_INT);
        $st->execute();

        return ['items' => $st->fetchAll(), 'total' => $total, 'pagina' => $pagina, 'porPagina' => $porPagina];
    }

    public function buscar(int $id, array $usuario): ?array
    {
        [$where, $params] = $this->filtros($usuario, null);
        $where .= ($where === '' ? 'WHERE' : ' AND') . ' o.id = :id';
        $params['id'] = $id;

        $st = $this->pdo->prepare(self::SELECT . " {$where} GROUP BY o.id, p.id");
        $st->execute($params);

        return $st->fetch() ?: null;
    }

    /**
     * Arma el WHERE según quién pregunta. Si es proveedor, SIEMPRE filtra por su proveedor_id,
     * que viene del token; un rol desconocido se rechaza (ver Alcance).
     * Los fragmentos de SQL son fijos y los valores van como parámetros.
     */
    private function filtros(array $usuario, ?string $estado): array
    {
        $condiciones = [];
        $params = [];

        $proveedorId = Alcance::proveedorId($usuario);
        if ($proveedorId !== null) {
            $condiciones[] = 'o.proveedor_id = :pid';
            $params['pid'] = $proveedorId;
        }
        if ($estado !== null) {
            $condiciones[] = 'o.estado = :estado';
            $params['estado'] = $estado;
        }

        $where = $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones);
        return [$where, $params];
    }
}