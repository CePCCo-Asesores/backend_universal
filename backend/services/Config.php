<?php
declare(strict_types=1);

namespace Services;

/**
 * Carga config universal por ambiente + config por módulo (config.yaml/json).
 * Merge: universal -> módulo -> env vars.
 */
final class Config
{
    /** @var array<string,mixed>|null */
    private static ?array $cache = null;

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;

        $root = dirname(__DIR__); // .../backend
        $env  = getenv('APP_ENV') ?: (getenv('ENV') ?: 'production');

        $universal = self::loadFile($root . '/config/universal.yaml');
        $perEnv    = ($universal['environments'][$env] ?? []);
        $base      = is_array($perEnv) ? $perEnv : [];

        // variables de entorno pisando claves de primer nivel (opcional)
        foreach (['debug','database_connections'] as $k) {
            $ev = getenv(strtoupper($k));
            if ($ev !== false) $base[$k] = $ev;
        }

        self::$cache = $base;
        return self::$cache;
    }

    /** @return array<string,mixed> */
    public static function module(string $module): array
    {
        $root = dirname(__DIR__);
        $paths = [
            $root . "/modules/{$module}/config.yaml",
            $root . "/modules/{$module}/config.yml",
            $root . "/modules/{$module}/config.json",
        ];
        foreach ($paths as $p) {
            if (is_readable($p)) {
                return self::loadFile($p);
            }
        }
        return [];
    }

    /** @return array<string,mixed> */
    private static function loadFile(string $file): array
    {
        if (!is_readable($file)) return [];
        if (str_ends_with($file, '.json')) {
            $raw = file_get_contents($file);
            $j = $raw ? json_decode($raw, true) : null;
            return is_array($j) ? $j : [];
        }
        if (str_ends_with($file, '.yaml') || str_ends_with($file, '.yml')) {
            if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                $arr = \Symfony\Component\Yaml\Yaml::parseFile($file);
                return is_array($arr) ? $arr : [];
            }
            if (function_exists('yaml_parse_file')) {
                $arr = @yaml_parse_file($file);
                return is_array($arr) ? $arr : [];
            }
        }
        return [];
    }
}
