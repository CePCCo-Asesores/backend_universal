<?php
declare(strict_types=1);

namespace Services;

class GoogleAuthService
{
    /**
     * Valida el ID token de Google verificando firma contra JWKs públicos.
     * Retorna perfil mínimo ['sub','email'] o null si inválido.
     */
    public static function validate(string $idToken): ?array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) return null;

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $headerJson = base64_decode(strtr($headerB64, '-_', '+/'));
        $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'));
        if ($headerJson === false || $payloadJson === false) return null;

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);
        if (!is_array($header) || !is_array($payload)) return null;

        $kid = $header['kid'] ?? null;
        $alg = $header['alg'] ?? null;
        if ($alg !== 'RS256' || !$kid) return null;

        $jwks = self::fetchGoogleJWKs(); // puede cachearse
        $key = self::findKey($jwks, $kid);
        if (!$key) return null;

        $publicKey = self::jwkToPem($key);
        $signingInput = $headerB64 . '.' . $payloadB64;
        $signature = base64_decode(strtr($sigB64, '-_', '+/'));
        if ($signature === false) return null;

        $ok = openssl_verify($signingInput, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) return null;

        // Validaciones estándar
        $now = time();
        if (($payload['exp'] ?? 0) <= $now) return null;
        $aud = (string)($payload['aud'] ?? '');
        $iss = (string)($payload['iss'] ?? '');
        $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';

        if ($clientId !== '' && !hash_equals($clientId, $aud)) return null;
        if (!in_array($iss, ['https://accounts.google.com', 'accounts.google.com'], true)) return null;

        $email = (string)($payload['email'] ?? '');
        $sub   = (string)($payload['sub'] ?? '');
        if ($email === '' || $sub === '') return null;

        return ['sub' => $sub, 'email' => $email];
    }

    private static function fetchGoogleJWKs(): array
    {
        $url = 'https://www.googleapis.com/oauth2/v3/certs';
        $json = file_get_contents($url);
        if (!$json) return [];
        $data = json_decode($json, true);
        return $data['keys'] ?? [];
    }

    private static function findKey(array $jwks, string $kid): ?array
    {
        foreach ($jwks as $k) {
            if (($k['kid'] ?? '') === $kid) return $k;
        }
        return null;
    }

    private static function jwkToPem(array $jwk): string
    {
        // Convierte JWK RSA (n,e) a PEM
        $n = self::b64url_to_bin($jwk['n'] ?? '');
        $e = self::b64url_to_bin($jwk['e'] ?? '');
        if (!$n || !$e) throw new \RuntimeException('JWK inválida');

        $modulus = self::encodeASN1Integer($n);
        $publicExponent = self::encodeASN1Integer($e);
        $sequence = self::encodeASN1Sequence($modulus . $publicExponent);
        $bitString = "\x03" . self::encodeLength(strlen($sequence) + 1) . "\x00" . $sequence;

        // Algoritmo RSA OID 1.2.840.113549.1.1.1
        $rsaOid = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01";
        $null = "\x05\x00";
        $algId = "\x30" . self::encodeLength(strlen($rsaOid . $null)) . $rsaOid . $null;

        $spki = "\x30" . self::encodeLength(strlen($algId . $bitString)) . $algId . $bitString;
        $pem = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($spki), 64, "\n") .
            "-----END PUBLIC KEY-----\n";
        return $pem;
    }

    private static function b64url_to_bin(string $data): string
    {
        $replaced = strtr($data, '-_', '+/');
        return base64_decode($replaced . str_repeat('=', (4 - strlen($replaced) % 4) % 4));
    }

    private static function encodeASN1Integer(string $x): string
    {
        if ($x === '' || (ord($x[0]) & 0x80)) $x = "\x00" . $x;
        return "\x02" . self::encodeLength(strlen($x)) . $x;
    }

    private static function encodeASN1Sequence(string $x): string
    {
        return "\x30" . self::encodeLength(strlen($x)) . $x;
    }

    private static function encodeLength(int $len): string
    {
        if ($len < 128) return chr($len);
        $out = '';
        while ($len > 0) { $out = chr($len & 0xff) . $out; $len >>= 8; }
        return chr(0x80 | strlen($out)) . $out;
    }
}
