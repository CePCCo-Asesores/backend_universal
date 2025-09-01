<?php
declare(strict_types=1);

namespace Services;

class JwtService
{
    private const ALG = 'HS256';

    private static function getSecret(): string
    {
        $secret = getenv('JWT_SECRET') ?: '';
        if ($secret === '') {
            http_response_code(500);
            exit('JWT_SECRET no configurado');
        }
        return $secret;
    }

    public static function generate(array $payload, int $ttlSeconds = 3600): string
    {
        $header = ['typ' => 'JWT', 'alg' => self::ALG];
        $now    = time();
        $payload['iat'] = $payload['iat'] ?? $now;
        $payload['exp'] = $payload['exp'] ?? ($now + $ttlSeconds);

        $base64UrlHeader  = self::base64url_encode(json_encode($header, JSON_UNESCAPED_UNICODE));
        $base64UrlPayload = self::base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));

        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", self::getSecret(), true);
        $base64UrlSignature = self::base64url_encode($signature);

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    public static function validate(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        [$h64, $p64, $s64] = $parts;

        $headerJson  = self::base64url_decode($h64);
        $payloadJson = self::base64url_decode($p64);
        if ($headerJson === false || $payloadJson === false) return null;

        $header  = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);
        if (!is_array($header) || !is_array($payload)) return null;

        if (($header['alg'] ?? '') !== 'HS256') return null;

        $calc = self::base64url_encode(hash_hmac('sha256', "$h64.$p64", self::getSecret(), true));
        if (!hash_equals($calc, $s64)) return null;

        if (isset($payload['exp']) && (int)$payload['exp'] < time()) return null;

        return $payload;
    }

    public static function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64url_decode(string $data): string|false
    {
        $replaced = strtr($data, '-_', '+/');
        $pad = strlen($replaced) % 4;
        if ($pad) $replaced .= str_repeat('=', 4 - $pad);
        return base64_decode($replaced);
    }
}
