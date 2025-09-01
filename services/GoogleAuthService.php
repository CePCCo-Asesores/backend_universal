<?php
declare(strict_types=1);

namespace Services;

class GoogleAuthService
{
    /**
     * Valida el ID token de Google de forma local (sin ir a la red):
     * - Decodifica JWT (sin verificar firma contra JWKs; demo/POC)
     * - Verifica exp > now
     * - Verifica aud == GOOGLE_CLIENT_ID si está definido
     * - Verifica iss si viene incluido
     * Devuelve perfil mínimo ['sub','email'] o null si inválido.
     */
    public static function validate(string $idToken): ?array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) return null;

        [$h64, $p64, $s64] = $parts;

        $payloadJson = JwtService::base64url_decode($p64);
        if ($payloadJson === false) return null;

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) return null;

        // exp
        $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
        if ($exp <= time()) return null;

        // aud
        $aud = (string)($payload['aud'] ?? '');
        $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
        if ($clientId !== '' && !hash_equals($clientId, $aud)) return null;

        // iss opcional
        $iss = (string)($payload['iss'] ?? '');
        if ($iss !== '' && !in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            return null;
        }

        $sub   = (string)($payload['sub'] ?? '');
        $email = (string)($payload['email'] ?? '');
        if ($sub === '' || $email === '') return null;

        return ['sub' => $sub, 'email' => $email];
    }
}
