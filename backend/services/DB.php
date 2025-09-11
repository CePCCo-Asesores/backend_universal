<?php
declare(strict_types=1);

namespace Services;

use PDO;

class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo) return self::$pdo;

        // Railway expone normalmente DATABASE_URL (formato postgres://user:pass@host:port/db)
        $url = getenv('DATABASE_URL') ?: '';
        if ($url === '') {
            throw new \RuntimeException('DATABASE_URL no configurado');
        }

        // Normaliza a DSN de PDO
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'postgres') {
            throw new \RuntimeException('DATABASE_URL inválido (se espera postgres://)');
        }

        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? '5432';
        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        $db   = ltrim($parts['path'] ?? '', '/');

        $dsn = "pgsql:host={$host};port={$port};dbname={$db};options='--client_encoding=UTF8'";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Extensiones útiles (uuid/gen_random_uuid) – si no existen, crea
        $pdo->exec("CREATE EXTENSION IF NOT EXISTS pgcrypto");
        $pdo->exec("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");

        self::$pdo = $pdo;
        return $pdo;
    }
}
