<?php
declare(strict_types=1);

use App\Bootstrap;
use App\Support\Database;

require __DIR__ . '/../vendor/autoload.php';

Bootstrap::crearApp(Database::connect())->run();