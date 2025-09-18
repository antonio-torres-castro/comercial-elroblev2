<?php

namespace App\Config;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Cargar variables de entorno
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
            $dotenv->load();

            $host    = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $db      = $_ENV['DB_DATABASE'] ?? 'comerci3_bdsetap';
            $user    = $_ENV['DB_USERNAME'] ?? 'root';
            $pass    = $_ENV['DB_PASSWORD'] ?? '';
            $port    = $_ENV['DB_PORT'] ?? 3306;
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
