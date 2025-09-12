<?php
declare(strict_types=1);

namespace Services;

/**
 * Service Locator minimalista (registro + resolución de dependencias).
 *
 * Soporta:
 *  - set(id, instancia)                → registra instancia concreta
 *  - register(id, factory)             → registra fábrica (nuevo objeto en cada get)
 *  - singleton(id, factory)            → registra fábrica en modo compartido (una sola instancia)
 *  - get(id)                           → resuelve instancia
 *  - has(id) / remove(id) / reset()    → utilidades
 *
 * Ejemplos:
 *   ServiceLocator::singleton('db', fn() => DB::conn());
 *   $pdo = ServiceLocator::get('db');
 */
final class ServiceLocator
{
    /** @var array<string, callable(self):mixed> */
    private static array $factories = [];

    /** @var array<string, mixed> */
    private static array $instances = [];

    /** @var array<string, bool> marcas de "singleton" */
    private static array $shared = [];

    private function __construct() {}

    /**
     * Registra una instancia concreta.
     * @param string $id
     * @param mixed  $instance
     */
    public static function set(string $id, mixed $instance): void
    {
        self::$instances[$id] = $instance;
        unset(self::$factories[$id], self::$shared[$id]);
    }

    /**
     * Registra una fábrica (no compartida): crea una nueva instancia en cada get().
     * @param string $id
     * @param callable(self):mixed $factory
     */
    public static function register(string $id, callable $factory): void
    {
        self::$factories[$id] = $factory;
        unset(self::$instances[$id], self::$shared[$id]);
    }

    /**
     * Registra una fábrica como singleton (compartida).
     * @param string $id
     * @param callable(self):mixed $factory
     */
    public static function singleton(string $id, callable $factory): void
    {
        self::$factories[$id] = $factory;
        self::$shared[$id] = true;
        unset(self::$instances[$id]);
    }

    /**
     * Resuelve un servicio.
     * @throws \RuntimeException si no existe el id.
     */
    public static function get(string $id): mixed
    {
        // Instancia ya creada
        if (array_key_exists($id, self::$instances)) {
            return self::$instances[$id];
        }

        // Fábrica registrada
        if (isset(self::$factories[$id])) {
            $instance = (self::$factories[$id])(new self());
            if (!empty(self::$shared[$id])) {
                self::$instances[$id] = $instance; // cachea singleton
            }
            return $instance;
        }

        throw new \RuntimeException("Service '{$id}' not found");
    }

    public static function has(string $id): bool
    {
        return array_key_exists($id, self::$instances) || array_key_exists($id, self::$factories);
    }

    public static function remove(string $id): void
    {
        unset(self::$instances[$id], self::$factories[$id], self::$shared[$id]);
    }

    /** Limpia todo (útil en tests). */
    public static function reset(): void
    {
        self::$instances = [];
        self::$factories = [];
        self::$shared = [];
    }
}
