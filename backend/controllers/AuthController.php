<?php
declare(strict_types=1);

namespace Controllers;

use Services\GoogleAuthService;
use Services\JwtService;

/**
 * Carga condicional de DatabaseManager:
 * - Si existe utils/database_manager.php se usa el real.
 * - Si NO existe, define un stub para evitar fatales en endpoints que no tocan DB.
 */
$__dm = __DIR__ . '/../utils/database_manager.php';
if (is_file($__dm)) {
    require_once $__dm; // \DatabaseManager real
} else {
    if (!class_exists('\\DatabaseManager', false)) {
        final class DatabaseManager {
            public static function fetchOne(string $sql, array $params = []): array { return ['count' => 0]; }
            public static function fetchAll(string $sql, array $params = []): array { return []; }
            public static function insert(string $table, array $data): int { return 1; }
            public static function update(string $table, array $data, string $where, array $params = []): int { return 1; }
            public static function delete(string $table, string $where, array $params = []): int { return 1; }
        }
    }
}

class AuthController
{
    // ==============================
    // DIAGNÓSTICO: GET /auth/google/ping
    // ==============================
    public function googlePing(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'controller' => __CLASS__,
            'namespace' => __NAMESPACE__,
            'db_file_exists' => is_file(__DIR__ . '/../utils/database_manager.php'),
            'has_DatabaseManager' => class_exists('\\DatabaseManager', false),
            'php_session_status' => session_status(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ==============================
    // DIAGNÓSTICO: GET /auth/google/diag
    // ==============================
    public function googleDiag(): void
    {
        $env = [
            // Solo presencia/booleanos. NO imprimimos secretos.
            'GOOGLE_CLIENT_ID'    => (getenv('GOOGLE_CLIENT_ID')    !== false && getenv('GOOGLE_CLIENT_ID')    !== ''),
            'GOOGLE_CLIENT_SECRET'=> (getenv('GOOGLE_CLIENT_SECRET')!== false && getenv('GOOGLE_CLIENT_SECRET')!== ''),
            'GOOGLE_REDIRECT_URI' => (getenv('GOOGLE_REDIRECT_URI') !== false && getenv('GOOGLE_REDIRECT_URI') !== ''),
            'FRONTEND_URL'        => (getenv('FRONTEND_URL')        !== false && getenv('FRONTEND_URL')        !== ''),
        ];
        $redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'https://cepcco-backend-production.up.railway.app/auth/google/callback';

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'diag',
            'env_present' => $env,
            'effective_redirect_uri' => $redirectUri,
            'db' => [
                'file_exists' => is_file(__DIR__ . '/../utils/database_manager.php'),
                'class_loaded'=> class_exists('\\DatabaseManager', false),
            ],
            'router_hint' => 'Usa controller: "AuthController" (alias global habilitado abajo).',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ==============================
    // GET /auth/google → redirige a Google
    // ==============================
    public function googleAuth(): void
    {
        $clientId    = getenv('GOOGLE_CLIENT_ID');
        $redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'https://cepcco-backend-production.up.railway.app/auth/google/callback';

        if (!$clientId || !$redirectUri) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => 'Faltan variables de entorno requeridas',
                'missing' => [
                    'GOOGLE_CLIENT_ID'    => (bool)$clientId,
                    'GOOGLE_REDIRECT_URI' => (bool)getenv('GOOGLE_REDIRECT_URI'),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (session_status() !== \PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // CSRF state (sesión + cookie fallback 10 min)
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth2_state'] = $state;
        setcookie('oauth2_state', $state, [
            'expires'  => time() + 600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'              => $clientId,
            'redirect_uri'           => $redirectUri,
            'response_type'          => 'code',
            'scope'                  => 'openid email profile',
            'access_type'            => 'offline',
            'include_granted_scopes' => 'true',
            'prompt'                 => 'consent',
            'state'                  => $state,
        ]);

        header('Location: ' . $authUrl, true, 302);
        exit;
    }

    // Tu método existente - SIN CAMBIOS
    public function loginConGoogle(array $input): array
    {
        $tokenGoogle
