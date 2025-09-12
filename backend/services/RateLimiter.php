<?php
declare(strict_types=1);

namespace Services;

/**
 * Rate limit por (module, ip) con ventana deslizante simple usando Postgres.
 * Tabla se garantiza on-demand.
 */
final class RateLimiter
{
    private const SCHEMA = 'CORE';
    private const TABLE  = 'rate_limits';

    public static function allow(string $module, string $ip, int $limitPerMin = null): bool
    {
        $limit = $limitPerMin ?? (int)(getenv('RATE_LIMIT_DEFAULT') ?: 60);
        if ($limit <= 0) return true;

        $pdo = DB::conn();
        self::ensureTable($pdo);

        $now  = time();
        $win  = (int)floor($now / 60) * 60; // ventana por minuto
        $key  = $module . '|' . $ip;

        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO " . self::SCHEMA . "." . self::TABLE . " (rl_key, window_start, count)
                           VALUES (:k, to_timestamp(:w), 1)
                           ON CONFLICT (rl_key, window_start)
                           DO UPDATE SET count = " . self::SCHEMA . "." . self::TABLE . ".count + 1")
                ->execute([':k'=>$key, ':w'=>$win]);

            $st = $pdo->prepare("SELECT count FROM " . self::SCHEMA . "." . self::TABLE . " WHERE rl_key=:k AND window_start=to_timestamp(:w)");
            $st->execute([':k'=>$key, ':w'=>$win]);
            $count = (int)$st->fetchColumn();
            $pdo->commit();

            return $count <= $limit;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            // En error, por seguridad permitir (y loguear)
            Logger::error('RateLimiter error', ['err'=>$e->getMessage()]);
            return true;
        }
    }

    private static function ensureTable(\PDO $pdo): void
    {
        // Crea schema y tabla si no existen
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS " . self::SCHEMA);
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS " . self::SCHEMA . "." . self::TABLE . " (
          rl_key        TEXT NOT NULL,
          window_start  TIMESTAMP WITH TIME ZONE NOT NULL,
          count         INTEGER NOT NULL DEFAULT 0,
          PRIMARY KEY (rl_key, window_start)
        )");
        // Limpieza básica (entradas viejas > 24h)
        $pdo->exec("DELETE FROM " . self::SCHEMA . "." . self::TABLE . " WHERE window_start < now() - interval '24 hours'");
    }
}
