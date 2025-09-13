<?php
/**
 * Módulo LAB para probar Google OAuth sin DB.
 * Usa variables LAB_* y guarda el state en sesión y cookie (fallback).
 *
 * Rutas:
 *   GET /_lab/google/auth
 *   GET /_lab/google/callback
 */
class GoogleLabController
{
    private function json(int $status, array $payload): void {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function env(string $k, ?string $def=null): ?string {
        $v = getenv($k);
        return ($v === false || $v === '') ? $def : $v;
    }

    private function startSession(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            @session_start();
        }
    }

    private function buildUrl(string $base, array $params): string {
        $q = http_build_query($params);
        return $base . (str_contains($base, '?') ? '&' : '?') . $q;
    }

    private function redirect(string $url, int $code=302): void {
        if (!headers_sent()) header('Location: '.$url, true, $code);
        exit;
    }

    private function httpPost(string $url, array $fields): array {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($fields),
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT => 20,
            ]);
            $body = curl_exec($ch);
            $err  = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$code, $body === false ? null : $body, $err ?: null];
        }
        $opts = ['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content'=> http_build_query($fields),
            'timeout'=> 20,
        ]];
        $ctx = stream_context_create($opts);
        $body = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#HTTP/\d\.\d\s+(\d{3})#',$h,$m)) {$code=(int)$m[1]; break;}
            }
        }
        return [$code, $body === false ? null : $body, $body === false ? 'fetch failed' : null];
    }

    private function httpGet(string $url): array {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20]);
            $body = curl_exec($ch);
            $err  = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$code, $body === false ? null : $body, $err ?: null];
        }
        $body = @file_get_contents($url);
        $code = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#HTTP/\d\.\d\s+(\d{3})#',$h,$m)) {$code=(int)$m[1]; break;}
            }
        }
        return [$code, $body === false ? null : $body, $body === false ? 'fetch failed' : null];
    }

    private function ensureEnv(array $keys): void {
        $missing = [];
        foreach ($keys as $k) if (!$this->env($k)) $missing[] = $k;
        if ($missing) $this->json(500, ['error'=>'Faltan variables de entorno', 'missing'=>$missing]);
    }

    // GET /_lab/google/auth
    public function auth(): void {
        $this->ensureEnv(['LAB_GOOGLE_CLIENT_ID','LAB_GOOGLE_REDIRECT_URI']);
        $this->startSession();

        $state = bin2hex(random_bytes(16));
        $_SESSION['lab_oauth2_state'] = $state;
        setcookie('lab_oauth2_state', $state, [
            'expires'  => time() + 600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $authUrl = $this->buildUrl('https://accounts.google.com/o/oauth2/v2/auth', [
            'client_id'              => $this->env('LAB_GOOGLE_CLIENT_ID'),
            'redirect_uri'           => $this->env('LAB_GOOGLE_REDIRECT_URI'),
            'response_type'          => 'code',
            'scope'                  => 'openid email profile',
            'access_type'            => 'offline',
            'include_granted_scopes' => 'true',
            'prompt'                 => 'consent',
            'state'                  => $state,
        ]);
        $this->redirect($authUrl, 302);
    }

    // GET /_lab/google/callback
    public function callback(): void {
        $this->ensureEnv(['LAB_GOOGLE_CLIENT_ID','LAB_GOOGLE_CLIENT_SECRET','LAB_GOOGLE_REDIRECT_URI']);
        $this->startSession();

        $state = $_GET['state'] ?? null;
        $expected = $_SESSION['lab_oauth2_state'] ?? ($_COOKIE['lab_oauth2_state'] ?? null);
        if (!$state || !$expected || !hash_equals($expected, $state)) {
            $this->json(400, ['error'=>'state inválido o ausente']);
        }
        unset($_SESSION['lab_oauth2_state']);
        if (isset($_COOKIE['lab_oauth2_state'])) {
            setcookie('lab_oauth2_state', '', time() - 3600, '/', '', true, true);
        }

        $code = $_GET['code'] ?? null;
        if (!$code) $this->json(400, ['error'=>'code ausente']);

        // Intercambio de code por tokens
        [$tCode,$tBody,$tErr] = $this->httpPost('https://oauth2.googleapis.com/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->env('LAB_GOOGLE_CLIENT_ID'),
            'client_secret' => $this->env('LAB_GOOGLE_CLIENT_SECRET'),
            'redirect_uri'  => $this->env('LAB_GOOGLE_REDIRECT_URI'),
        ]);
        if ($tBody === null) $this->json(502, ['error'=>'Fallo token endpoint', 'http_code'=>$tCode, 'detail'=>$tErr]);

        $tok = json_decode($tBody, true);
        if (!is_array($tok) || !isset($tok['id_token'])) {
            $this->json(400, ['error'=>'Respuesta de token inválida', 'http_code'=>$tCode, 'google_response'=>$tok]);
        }

        // Validar id_token
        [$iCode,$iBody,$iErr] = $this->httpGet($this->buildUrl('https://oauth2.googleapis.com/tokeninfo', ['id_token'=>$tok['id_token']]));
        if ($iBody === null) $this->json(502, ['error'=>'Fallo tokeninfo', 'http_code'=>$iCode, 'detail'=>$iErr]);
        $claims = json_decode($iBody, true);
        if (!is_array($claims) || ($claims['aud'] ?? null) !== $this->env('LAB_GOOGLE_CLIENT_ID')) {
            $this->json(401, ['error'=>'id_token inválido (aud no coincide)', 'claims'=>$claims]);
        }

        $this->json(200, [
            'status'  => 'ok',
            'profile' => [
                'sub'            => $claims['sub'] ?? null,
                'email'          => $claims['email'] ?? null,
                'email_verified' => filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'name'           => $claims['name'] ?? ($claims['given_name'] ?? null),
                'picture'        => $claims['picture'] ?? null,
            ],
            'tokens'  => [
                'id_token'      => $tok['id_token'],
                'access_token'  => $tok['access_token'] ?? null,
                'refresh_token' => $tok['refresh_token'] ?? null,
                'expires_in'    => $tok['expires_in'] ?? null,
                'scope'         => $tok['scope'] ?? null,
                'token_type'    => $tok['token_type'] ?? null,
            ],
        ]);
    }
}
