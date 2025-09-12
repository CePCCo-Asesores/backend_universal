<?php
declare(strict_types=1);

namespace Services;

/**
 * Logger JSON a stdout.
 * Uso: Logger::info('Module activated', ['module'=>$m, 'action'=>$a]);
 */
final class Logger
{
    public static function info(string $msg, array $ctx = []): void { self::write('info', $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void { self::write('error', $msg, $ctx); }
    public static function debug(string $msg, array $ctx = []): void
    {
        if ((getenv('APP_ENV') ?: getenv('ENV') ?: 'production') !== 'production') {
            self::write('debug', $msg, $ctx);
        }
    }

    private static function write(string $level, string $msg, array $ctx): void
    {
        $line = [
            'ts'    => date('c'),
            'level' => $level,
            'msg'   => $msg,
            'ctx'   => $ctx,
        ];
        // phpcs:ignore
        fwrite(STDOUT, json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
