<?php
declare(strict_types=1);

namespace Events;

final class ModuleActivated
{
    public const NAME = 'module.activated';

    /** @return array<string,mixed> */
    public static function payload(string $module, string $action, array $auth = null, int $ms, bool $ok): array
    {
        return [
            'module' => $module,
            'action' => $action,
            'ok'     => $ok,
            'ms'     => $ms,
            'user'   => $auth ? ($auth['email'] ?? $auth['sub'] ?? null) : null,
            'ts'     => date('c'),
        ];
    }
}
