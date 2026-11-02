<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

/** Chequeos previos a levantar la API, con mensajes que dicen por dónde arreglar. */
final class Arranque
{
    private const REQUERIDAS = ['POSTGRES_DB', 'POSTGRES_USER', 'POSTGRES_PASSWORD', 'DB_HOST', 'JWT_SECRET'];

    private const RECREAR_VOLUMEN = 'Postgres toma POSTGRES_DB, POSTGRES_USER y POSTGRES_PASSWORD solo la PRIMERA vez que crea el volumen: '
        . 'si los cambiaste en .env después, volvé a los valores originales o recreá la base (borra los datos) con '
        . '"docker compose down -v" y "docker compose up -d".';

    /** Valida el entorno y abre la conexión; si algo falla, lanza ErrorDeArranque. */
    public static function conectar(): PDO
    {
        self::verificarEntorno();

        try {
            return Database::connect();
        } catch (PDOException $e) {
            throw self::explicar($e);
        }
    }

    public static function verificarEntorno(): void
    {
        $faltan = array_values(array_filter(self::REQUERIDAS, fn (string $c) => Config::get($c, '') === ''));
        if ($faltan !== []) {
            throw new ErrorDeArranque(
                'Faltan variables de entorno: ' . implode(', ', $faltan),
                'Completalas en el .env de la raíz (guiate con .env.example) y recreá el backend con "docker compose up -d".'
            );
        }

        if (strlen(Config::get('JWT_SECRET')) < 32) {
            throw new ErrorDeArranque(
                'JWT_SECRET es demasiado corto',
                'Poné en .env una clave aleatoria de al menos 32 caracteres y recreá el backend con "docker compose up -d".'
            );
        }
    }

    /** Traduce el error de PDO a un mensaje entendible. */
    public static function explicar(PDOException $e): ErrorDeArranque
    {
        $detalle = strtolower($e->getMessage());
        $host = Config::get('DB_HOST', '?') . ':' . Config::get('DB_PORT', '5432');
        $usuario = Config::get('POSTGRES_USER', '?');
        $base = Config::get('POSTGRES_DB', '?');

        [$mensaje, $solucion] = match (true) {
            str_contains($detalle, 'could not find driver') => [
                'PHP no tiene el driver pdo_pgsql',
                'Reconstruí la imagen con "docker compose up -d --build backend".',
            ],
            str_contains($detalle, 'password authentication failed') => [
                "Postgres rechazó el usuario \"{$usuario}\" o su contraseña",
                'Revisá POSTGRES_USER y POSTGRES_PASSWORD en .env. ' . self::RECREAR_VOLUMEN,
            ],
            str_contains($detalle, 'role') && str_contains($detalle, 'does not exist') => [
                "El usuario \"{$usuario}\" no existe en Postgres",
                self::RECREAR_VOLUMEN,
            ],
            str_contains($detalle, 'database') && str_contains($detalle, 'does not exist') => [
                "La base \"{$base}\" no existe en Postgres",
                self::RECREAR_VOLUMEN,
            ],
            str_contains($detalle, 'could not translate host name') => [
                "No se encuentra el servidor de base \"{$host}\"",
                'Revisá DB_HOST en .env (dentro de Docker es "db") y que el contenedor esté levantado con "docker compose ps".',
            ],
            str_contains($detalle, 'connection refused'), str_contains($detalle, 'timeout expired') => [
                "Postgres no responde en {$host}",
                'Verificá que el contenedor db esté levantado y healthy con "docker compose ps" y "docker compose logs db", '
                    . 'y que DB_PORT sea 5432 (el puerto interno de Docker, no el 5434 de tu PC).',
            ],
            default => [
                'No se pudo conectar a la base de datos',
                'Mirá el detalle con "docker compose logs backend".',
            ],
        };

        return new ErrorDeArranque($mensaje, $solucion, $e);
    }
}
