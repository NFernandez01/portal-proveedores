<?php
declare(strict_types=1);

namespace App\Support;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            Config::get('DB_HOST'),
            Config::get('DB_PORT', '5432'),
            Config::get('POSTGRES_DB')
        );

        return new PDO($dsn, Config::get('POSTGRES_USER'), Config::get('POSTGRES_PASSWORD'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // filas como arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements reales
        ]);
    }
}