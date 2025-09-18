<?php

namespace App\Config;

use Dotenv\Dotenv;

class MailConfig
{
    private static array $mailConfig = [];

    public static function load(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        self::$mailConfig = [
            'mailer'   => $_ENV['MAIL_MAILER'] ?? 'smtp',
            'host'     => $_ENV['MAIL_HOST'] ?? 'localhost',
            'port'     => (int)($_ENV['MAIL_PORT'] ?? 25),
            'username' => $_ENV['MAIL_USERNAME'] ?? null,
            'password' => $_ENV['MAIL_PASSWORD'] ?? null,
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? null,
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@example.com',
            'from_name'    => $_ENV['MAIL_FROM_NAME'] ?? 'SETAP',
        ];
    }

    public static function get(string $key, $default = null)
    {
        return self::$mailConfig[$key] ?? $default;
    }
}

// Cargar configuración al incluir este archivo
MailConfig::load();
