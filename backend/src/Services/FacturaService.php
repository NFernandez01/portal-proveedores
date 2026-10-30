<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\FacturaRepository;
use App\Support\ApiException;
use DateTimeImmutable;
use finfo;
use PDO;
use PDOException;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

final class FacturaService
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private PDO $pdo,
        private FacturaRepository $facturas,
        private string $dirUploads,
    ) {}

    public function crear(array $usuario, array $datos, ?UploadedFileInterface $archivo): array
    {
        $errores = $this->validar($datos, $archivo);
        if ($errores !== []) {
            throw new ApiException(422, 'Revisá los datos de la factura.', $errores);
        }

        $importe = self::aCentavos($datos['importe']);
        $rutaArchivo = null;

        $this->pdo->beginTransaction();
        try {
            $orden = $this->facturas->bloquearOrden((int) $datos['orden_id'], $usuario['proveedor_id']);
            if ($orden === null) {
                throw new ApiException(404, 'La orden no existe.');
            }

            $saldo = self::aCentavos($orden['total']) - self::aCentavos($this->facturas->totalFacturado($orden['id']));
            if ($importe > $saldo) {
                throw new ApiException(422, 'El importe supera el saldo de la orden.', [
                    'importe' => 'Saldo disponible: $ ' . number_format($saldo / 100, 2, ',', '.'),
                ]);
            }

            // Nombre aleatorio: nunca usamos el nombre que manda el usuario
            $nombre = bin2hex(random_bytes(16)) . '.pdf';
            $rutaArchivo = $this->dirUploads . '/' . $nombre;
            $archivo->moveTo($rutaArchivo);

            $id = $this->facturas->crear([
                'orden_id'     => $orden['id'],
                'proveedor_id' => $usuario['proveedor_id'],
                'tipo'         => $datos['tipo'],
                'punto_venta'  => (int) $datos['punto_venta'],
                'numero'       => (int) $datos['numero'],
                'fecha'        => $datos['fecha'],
                'importe'      => $datos['importe'],
                'archivo'      => $nombre,
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            if ($rutaArchivo !== null && is_file($rutaArchivo)) {
                unlink($rutaArchivo);   // no dejamos archivos huérfanos
            }
            // 23505 = violación de UNIQUE en PostgreSQL → factura duplicada
            if ($e instanceof PDOException && $e->getCode() === '23505') {
                throw new ApiException(409, 'Esa factura ya fue cargada.', ['numero' => 'Número de factura repetido.']);
            }
            throw $e;
        }

        return $this->facturas->buscar($id, $usuario);
    }

    public function resolver(int $id, array $datos): void
    {
        $estado = $datos['estado'] ?? '';
        $motivo = trim((string) ($datos['motivo'] ?? ''));

        if (!in_array($estado, ['aprobada', 'rechazada'], true)) {
            throw new ApiException(422, 'Estado inválido.', ['estado' => 'Elegí aprobar o rechazar.']);
        }
        if ($estado === 'rechazada' && ($motivo === '' || mb_strlen($motivo) > 500)) {
            throw new ApiException(422, 'Indicá el motivo del rechazo.', ['motivo' => 'Obligatorio, hasta 500 caracteres.']);
        }

        if (!$this->facturas->resolver($id, $estado, $estado === 'rechazada' ? $motivo : null)) {
            throw new ApiException(409, 'La factura no existe o ya fue procesada.');
        }
    }

    /** @return array<string,string> errores por campo */
    private function validar(array $d, ?UploadedFileInterface $archivo): array
    {
        $e = [];

        if (!self::enteroEntre($d['orden_id'] ?? '', 1, PHP_INT_MAX)) {
            $e['orden_id'] = 'Elegí una orden.';
        }
        if (!in_array($d['tipo'] ?? '', ['A', 'B', 'C'], true)) {
            $e['tipo'] = 'El tipo tiene que ser A, B o C.';
        }
        if (!self::enteroEntre($d['punto_venta'] ?? '', 1, 99999)) {
            $e['punto_venta'] = 'Punto de venta entre 1 y 99999.';
        }
        if (!self::enteroEntre($d['numero'] ?? '', 1, 99999999)) {
            $e['numero'] = 'Número entre 1 y 99999999.';
        }

        $fechaTxt = (string) ($d['fecha'] ?? '');
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaTxt);
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaTxt) {
            $e['fecha'] = 'Fecha inválida.';
        } elseif ($fecha > new DateTimeImmutable('today')) {
            $e['fecha'] = 'La fecha no puede ser futura.';
        }

        $importe = (string) ($d['importe'] ?? '');
        if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $importe) || (float) $importe <= 0) {
            $e['importe'] = 'Importe inválido. Usá punto para los decimales (ej: 1500.50).';
        }

        if ($archivo === null || $archivo->getError() !== UPLOAD_ERR_OK) {
            $e['archivo'] = 'Adjuntá el PDF de la factura.';
        } elseif ($archivo->getSize() > self::MAX_BYTES) {
            $e['archivo'] = 'El PDF no puede superar los 5 MB.';
        } else {
            // Miramos el CONTENIDO real del archivo, no la extensión ni lo que declara el navegador
            $tipoReal = (new finfo(FILEINFO_MIME_TYPE))->file($archivo->getStream()->getMetadata('uri'));
            if ($tipoReal !== 'application/pdf') {
                $e['archivo'] = 'El archivo tiene que ser un PDF.';
            }
        }

        return $e;
    }

    private static function enteroEntre(mixed $valor, int $min, int $max): bool
    {
        return ctype_digit((string) $valor) && (int) $valor >= $min && (int) $valor <= $max;
    }

    /** Pasamos la plata a centavos (enteros) para comparar sin errores de redondeo. */
    private static function aCentavos(string|int|float $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }
}