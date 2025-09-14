<?php
declare(strict_types=1);

namespace Controllers;

use Services\GoogleAuthService;
use Services\JwtService;

/**
 * Cargar servicios sin Composer/autoloader (rutas case-sensitive en Linux).
 */
$__svc = __DIR__ . '/../services/GoogleAuthService.php';
if (is_file($__svc)) { require_once $__svc; } else { error_log("GoogleAuthService no encontrado: $__svc"); }

$__jwt = __DIR__ . '/../services/JwtService.php';
if (is_file($__jwt)) { require_once $__jwt; } else { error_log("JwtService no encontrado: $__jwt"); }

/**
 * Carga condicional de DatabaseManager:
 * - Si existe utils/database_manager.php se usa el real (\DatabaseManager).
 * - Si NO existe, define un stub en este namespace y lo alias a \DatabaseManager
 *   para evitar fatales en endpoints que no tocan DB.
 */
$__dm = __DIR__ . '/../utils/database_manager.php';
if (is_file($__dm)) {
    require_once $__dm; // carga la clase \DatabaseManager real
} elseif (!\class_exists('\\DatabaseManager', false)) {
    class DatabaseManager {
        public static function fetchOne(string $sql, array $params = []): array { return ['count' => 0]; }
        public static function fetchAll(string $sql, array $params = []): array { return []; }
        public static function insert(string $table, array $data): int { return 1; }
        public static function update(string $table, array $data, string $where, array $params = []): int { return 1; }
        public static function delete(string $table, string $where, array $params = []): int { return 1; }
    }
    \class_alias(__NAMESPACE__ . '\DatabaseManager', 'DatabaseManager');
}

class AuthController
{
    // ==============================
    // DIAGNÓSTICO
    // ==============================
    public function googlePing(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'controller' => __CLASS__,
            'namespace' => __NAMESPACE__,
            'db_file_exists' => is_file(__DIR__ . '/../utils/database_manager.php'),
            'has_DatabaseManager' => \class_exists('\\DatabaseManager', false),
            'php_session_status' => \session_status(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function googleDiag(): void
    {
        $redirectUri = $this->effectiveRedirectUri();
        $env = [
            'GOOGLE_CLIENT_ID'     => (\getenv('GOOGLE_CLIENT_ID')     !== false && \getenv('GOOGLE_CLIENT_ID')     !== ''),
            'GOOGLE_CLIENT_SECRET' => (\getenv('GOOGLE_CLIENT_SECRET') !== false && \getenv('GOOGLE_CLIENT_SECRET') !== ''),
            'GOOGLE_REDIRECT_URI'  => (\getenv('GOOGLE_REDIRECT_URI')  !== false && \getenv('GOOGLE_REDIRECT_URI')  !== ''),
            'FRONTEND_URL'         => (\getenv('FRONTEND_URL')         !== false && \getenv('FRONTEND_URL')         !== ''),
        ];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'diag',
            'env_present' => $env,
            'effective_redirect_uri' => $redirectUri,
            'db' => [
                'file_exists' => is_file(__DIR__ . '/../utils/database_manager.php'),
                'class_loaded'=> \class_exists('\\DatabaseManager', false),
            ],
            'router_hint' => 'El enrutador puede usar "AuthController" gracias al alias global definido al final.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ==============================
    // GET /auth/google → redirige a Google
    // ==============================
    public function googleAuth(): void
    {
        $clientId    = \getenv('GOOGLE_CLIENT_ID');
        $redirectUri = $this->effectiveRedirectUri(); // <— SIEMPRE coherente con el dominio actual

        if (!$clientId || !$redirectUri) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => 'Faltan variables de entorno requeridas',
                'missing' => [
                    'GOOGLE_CLIENT_ID'    => (bool)$clientId,
                    'GOOGLE_REDIRECT_URI' => (bool)\getenv('GOOGLE_REDIRECT_URI'),
                ],
                'effective_redirect_uri' => $redirectUri,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (\session_status() !== \PHP_SESSION_ACTIVE) { @\session_start(); }

        // CSRF state (sesión + cookie fallback 10 min)
        $state = \bin2hex(\random_bytes(16));
        $_SESSION['oauth2_state'] = $state;
        \setcookie('oauth2_state', $state, [
            'expires'  => time() + 600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . \http_build_query([
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

    // ==============================
    // POST /auth/login-con-google (token id del lado cliente)
    // ==============================
    public function loginConGoogle(array $input): array
    {
        $tokenGoogle = $input['token_google'] ?? null;

        if (!$tokenGoogle || !\is_string($tokenGoogle)) {
            http_response_code(400);
            return ['error' => 'token_google es requerido'];
        }

        $perfil = GoogleAuthService::validate($tokenGoogle);
        if ($perfil === null) {
            http_response_code(401);
            return ['error' => 'Token de Google inválido'];
        }

        $jwt = JwtService::generate([
            'sub'   => $perfil['sub'],
            'email' => $perfil['email']
        ], 3600);

        return [
            'autenticado' => true,
            'usuario'     => $perfil['email'],
            'jwt'         => $jwt
        ];
    }

    /**
     * GET /auth/google/callback
     */
    public function googleCallback(): void
    {
        try {
            if (\session_status() !== \PHP_SESSION_ACTIVE) { @\session_start(); }

            // Validación de CSRF state (sesión + cookie)
            $state    = $_GET['state'] ?? null;
            $expected = $_SESSION['oauth2_state'] ?? ($_COOKIE['oauth2_state'] ?? null);
            if (!$state || !$expected || !\hash_equals((string)$expected, (string)$state)) {
                throw new \Exception('Parámetro state inválido o ausente');
            }
            unset($_SESSION['oauth2_state']);
            if (isset($_COOKIE['oauth2_state'])) {
                \setcookie('oauth2_state', '', time() - 3600, '/', '', true, true);
            }

            $authCode = $_GET['code'] ?? null;
            if (!$authCode) throw new \Exception('Código de autorización no recibido');

            // Intercambiar código por token
            $tokenData = $this->exchangeCodeForToken($authCode);
            if (!$tokenData || !isset($tokenData['id_token'])) {
                throw new \Exception('No se pudo obtener ID token de Google');
            }

            // Validar ID token
            $perfil = GoogleAuthService::validate($tokenData['id_token']);
            if (!$perfil) throw new \Exception('ID token inválido');

            // Info adicional (no crítica)
            $userInfo = $this->getGoogleUserInfo($tokenData['access_token'] ?? '');

            // Crear/actualizar usuario
            $user = $this->createOrUpdateUser($perfil, $userInfo);

            // JWT propio
            $jwtToken = JwtService::generate([
                'user_id'   => $user['id'],
                'sub'       => $perfil['sub'],
                'email'     => $perfil['email'],
                'tenant_id' => $user['tenant_id'],
                'plan'      => $user['plan']
            ], 24 * 60 * 60);

            // Crear sesión en DB
            $this->createSession($user['id'], $jwtToken);

            // Cookie
            \setcookie('auth_token', $jwtToken, [
                'expires'  => time() + (24 * 60 * 60),
                'path'     => '/',
                'domain'   => '',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Redirigir al frontend (universal)
            $frontendUrl = \getenv('FRONTEND_URL') ?: 'https://tu-frontend.com';
            $afterOk  = \getenv('FRONTEND_AFTER_LOGIN_PATH')       ?: '/#/dashboard';
            header("Location: {$frontendUrl}{$afterOk}?auth=success");
            exit;

        } catch (\Exception $e) {
            error_log('Google callback error: ' . $e->getMessage());
            $frontendUrl = \getenv('FRONTEND_URL') ?: 'https://tu-frontend.com';
            $afterErr = \getenv('FRONTEND_AFTER_LOGIN_ERROR_PATH') ?: '/#/login';
            header("Location: {$frontendUrl}{$afterErr}?error=" . \urlencode($e->getMessage()));
            exit;
        }
    }

    /** POST /auth/logout */
    public function logout(): void
    {
        try {
            $token = $this->getTokenFromRequest();
            if ($token) {
                \DatabaseManager::delete('user_sessions', 'token = ?', [$token]);
            }
            \setcookie('auth_token', '', time() - 3600, '/', '', true, true);
            echo json_encode(['success' => true, 'message' => 'Sesión cerrada exitosamente']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error cerrando sesión', 'message' => $e->getMessage()]);
        }
    }

    /** GET /auth/me */
    public function me(): void
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'No autenticado']);
                return;
            }
            unset($user['google_id']);
            echo json_encode(['user' => $user, 'authenticated' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error obteniendo usuario', 'message' => $e->getMessage()]);
        }
    }

    /** GET /auth/status */
    public function status(): void
    {
        try {
            $user = $this->getCurrentUser();
            echo json_encode([
                'authenticated' => $user !== null,
                'user_id'       => $user['id'] ?? null,
                'email'         => $user['email'] ?? null,
                'plan'          => $user['plan'] ?? null,
                'tenant_id'     => $user['tenant_id'] ?? null
            ]);
        } catch (\Exception $e) {
            echo json_encode(['authenticated' => false, 'error' => $e->getMessage()]);
        }
    }

    /** GET /auth/usage */
    public function usage(): void
    {
        try {
            $user = $this->requireAuth();
            $dailyUsage = \DatabaseManager::fetchOne(
                "SELECT COUNT(*) as count FROM assistant_usage WHERE user_id = ? AND created_at >= CURRENT_DATE",
                [$user['id']]
            )['count'];
            $monthlyUsage = \DatabaseManager::fetchOne(
                "SELECT COUNT(*) as count FROM assistant_usage 
                 WHERE user_id = ? AND created_at >= DATE_TRUNC('month', CURRENT_DATE)",
                [$user['id']]
            )['count'];
            $assistantUsage = \DatabaseManager::fetchAll(
                "SELECT assistant_id, COUNT(*) as count 
                 FROM assistant_usage 
                 WHERE user_id = ? AND created_at >= CURRENT_DATE - INTERVAL '7 days'
                 GROUP BY assistant_id 
                 ORDER BY count DESC",
                [$user['id']]
            );
            $planLimits = ['basic' => 50, 'pro' => 1000, 'enterprise' => \PHP_INT_MAX];
            $dailyLimit = $planLimits[$user['plan']] ?? $planLimits['basic'];
            echo json_encode([
                'daily_usage'     => (int)$dailyUsage,
                'daily_limit'     => $dailyLimit,
                'monthly_usage'   => (int)$monthlyUsage,
                'assistant_usage' => $assistantUsage,
                'plan'            => $user['plan'],
                'remaining_today' => max(0, $dailyLimit - $dailyUsage)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error obteniendo estadísticas', 'message' => $e->getMessage()]);
        }
    }

    // ========== Privados ==========

    private function effectiveRedirectUri(): string
    {
        // Base según host actual
        $scheme = 'https';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $scheme = 'https';
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO']; // Railway set
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $currentCallback = $scheme . '://' . $host . '/auth/google/callback';

        // Env (si existe)
        $env = \getenv('GOOGLE_REDIRECT_URI') ?: '';
        $redirectUri = $env !== '' ? $env : $currentCallback;

        // Si el host del env NO coincide con el host actual, forzamos el actual
        $envHost = parse_url($redirectUri, PHP_URL_HOST);
        if ($envHost && \strcasecmp($envHost, $host) !== 0) {
            error_log("AuthController: override redirect_uri {$redirectUri} -> {$currentCallback} (host mismatch)");
            $redirectUri = $currentCallback;
        }
        return $redirectUri;
    }

    private function exchangeCodeForToken(string $authCode): ?array
    {
        $clientId     = \getenv('GOOGLE_CLIENT_ID');
        $clientSecret = \getenv('GOOGLE_CLIENT_SECRET');
        $redirectUri  = $this->effectiveRedirectUri(); // usar el mismo que se envió a Google

        $postData = [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $authCode,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri
        ];

        $ch = \curl_init();
        \curl_setopt_array($ch, [
            \CURLOPT_URL            => 'https://oauth2.googleapis.com/token',
            \CURLOPT_POST           => true,
            \CURLOPT_POSTFIELDS     => \http_build_query($postData),
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            \CURLOPT_TIMEOUT        => 30
        ]);
        $response = \curl_exec($ch);
        $httpCode = (int)\curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        \curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Error obteniendo token de Google: HTTP {$httpCode}");
        }

        return \json_decode($response, true);
    }

    private function getGoogleUserInfo(string $accessToken): ?array
    {
        if (empty($accessToken)) return null;

        $ch = \curl_init();
        \curl_setopt_array($ch, [
            \CURLOPT_URL            => 'https://www.googleapis.com/oauth2/v2/userinfo',
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$accessToken}"],
            \CURLOPT_TIMEOUT        => 30
        ]);
        $response = \curl_exec($ch);
        $httpCode = (int)\curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        \curl_close($ch);

        if ($httpCode !== 200) return null;

        return \json_decode($response, true);
    }

    private function createOrUpdateUser(array $perfil, ?array $userInfo): array
    {
        $existingUser = \DatabaseManager::fetchOne(
            "SELECT * FROM users WHERE google_id = ? OR email = ?",
            [$perfil['sub'], $perfil['email']]
        );

        $userData = [
            'email'      => $perfil['email'],
            'google_id'  => $perfil['sub'],
            'name'       => $userInfo['name']    ?? '',
            'avatar_url' => $userInfo['picture'] ?? null,
            'last_login' => date('Y-m-d H:i:s')
        ];

        if ($existingUser) {
            // Si tu DatabaseManager NO es el nuevo, puedes cambiar a WHERE nombrado:
            // \DatabaseManager::update('users', $userData, 'id = :id', ['id' => $existingUser['id']]);
            \DatabaseManager::update('users', $userData, 'id = ?', [$existingUser['id']]);
            $userId = $existingUser['id'];
        } else {
            $userData['tenant_id'] = $this->determineTenantId();
            $userId = \DatabaseManager::insert('users', $userData);
        }

        return \DatabaseManager::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    private function determineTenantId(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $tenantMap = [
            'empresa-a.com' => 'empresa_a',
            'startup-b.com' => 'startup_b',
        ];
        return $tenantMap[$host] ?? 'default';
    }

    private function createSession(int $userId, string $token): int
    {
        return \DatabaseManager::insert('user_sessions', [
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + (24 * 60 * 60)),
            'ip_address' => $_SERVER['REMOTE_ADDR']     ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    private function getTokenFromRequest(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (\preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) return $m[1];
        return $_COOKIE['auth_token'] ?? null;
    }

    private function getCurrentUser(): ?array
    {
        $token = $this->getTokenFromRequest();
        if (!$token) return null;

        $payload = JwtService::validate($token);
        if (!$payload) return null;

        $session = \DatabaseManager::fetchOne(
            "SELECT * FROM user_sessions WHERE token = ? AND expires_at > NOW()",
            [$token]
        );
        if (!$session) return null;

        return \DatabaseManager::fetchOne("SELECT * FROM users WHERE id = ?", [$payload['user_id']]);
    }

    private function requireAuth(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Token de autenticación requerido']);
            exit;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no activo']);
            exit;
        }
        return $user;
    }
}

/**
 * Alias global para routers que instancian "AuthController" sin namespace.
 */
if (!\class_exists('AuthController', false)) {
    \class_alias(__NAMESPACE__ . '\AuthController', 'AuthController');
}
