<?php
declare(strict_types=1);

namespace Services;

/**
 * EventBus simple (in-process). Suscriptores por nombre de evento.
 * publish('Evento', ['payload'=>...]); subscribe('Evento', fn($p)=>...).
 */
final class EventBus
{
    /** @var array<string, list<callable(array):void>> */
    private static array $subs = [];

    /** @param callable(array):void $handler */
    public static function subscribe(string $eventName, callable $handler): void
    {
        self::$subs[$eventName][] = $handler;
    }

    /** @param array<string,mixed> $payload */
    public static function publish(string $eventName, array $payload = []): void
    {
        foreach (self::$subs[$eventName] ?? [] as $fn) {
            try { $fn($payload); } catch (\Throwable $e) {
                Logger::error('EventBus handler error', ['event'=>$eventName, 'err'=>$e->getMessage()]);
            }
        }
    }

    public static function reset(): void
    {
        self::$subs = [];
    }
}
