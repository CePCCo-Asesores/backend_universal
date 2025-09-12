<?php
declare(strict_types=1);

namespace Services;

/**
 * Validador de seguridad universal: JWT (opcional), rate limit, sanitización básica.
 */
final class SecurityValidator
{
    /**
     * @return array{sanitized:array<string,mixed>, ip:string, jwt?:array<string,mixed>}
     */
    public static function validate(string $module, string $action, array $payload): array
    {
        $ip = self::clientIp();

        // Rate limit por módulo + IP
        $limit = (int)(getenv('RATE_LIMIT_PER_MIN') ?: 60);
        if (!RateLimiter::allow($module . ':' . $action, $ip, $limit)) {
            http_response_code(429);
            throw new \RuntimeException('rate_limited');
        }

        // JWT opcional: si viene, verificar
        $claims = null;
        try {
            $claims = JwtService::verifyFromRequest();
        } catch (\Throwable $e) {
            // si tu endpoint requiere auth, valida aquí y lanza 401
            // por ahora, JWT opcional
        }

        // Sanitizar inputs
        $san = self::sanitize($payload);

        return ['sanitized'=>$san, 'ip'=>$ip, 'jwt'=>$claims ?: null];
    }

    /** @param array<string,mixed> $arr */
    private static function sanitize(array $arr): array
    {
        $out = [];
        foreach ($arr as $k=>$v) {
            if (is_array($v)) {
                $out[$k] = self::sanitize($v);
            } elseif (is_string($v)) {
                // Básico: recorta, elimina tags peligrosas
                $s = trim($v);
                $s = strip_tags($s);
                $out[$k] = $s;
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    private static function clientIp(): string
    {
        $hdrs = [
            'HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP', 'REMOTE_ADDR'
        ];
        foreach ($hdrs as $h) {
            $v = $_SERVER[$h] ?? '';
            if ($v) {
                $ip = explode(',', $v)[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }
}
