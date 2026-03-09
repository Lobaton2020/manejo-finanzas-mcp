<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

class Connection
{
    private static ?Capsule $capsule = null;

    public static function getCapsule(): Capsule
    {
        if (self::$capsule === null) {
            self::$capsule = new Capsule();
            self::configureConnection(self::$capsule);
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
        }

        return self::$capsule;
    }

    private static function configureConnection(Capsule $capsule): void
    {
        $dbPath = __DIR__ . '/../../finanzas.db';

        if (file_exists($dbPath)) {
            $capsule->addConnection([
                'driver' => 'sqlite',
                'database' => $dbPath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
        } else {
            $envPath = __DIR__ . '/../../.env';
            $dotenv = file_exists($envPath) ? parse_ini_file($envPath) : false;

            $capsule->addConnection([
                'driver' => 'mysql',
                'host' => $dotenv['DB_HOST'] ?? 'localhost',
                'database' => $dotenv['DB_NAME'] ?? 'finanzas',
                'username' => $dotenv['DB_USER'] ?? 'root',
                'password' => $dotenv['DB_PASWORD'] ?? '',
                'port' => $dotenv['DB_PORT'] ?? 3306,
                'charset' => $dotenv['DB_CHARSET'] ?? 'utf8mb4',
                'prefix' => '',
            ]);
        }
    }

    public static function table(string $table)
    {
        return self::getCapsule()->table($table);
    }
}
