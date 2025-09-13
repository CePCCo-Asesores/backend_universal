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
            'GOOGLE_CLIENT_ID'     => (getenv('GOOGLE_CLIENT_ID')     !== false && getenv('GOOGLE_CLIENT_ID')     !== ''),
            'GOOGLE_CLIENT_SECRET' => (getenv('GOOGLE_CLIENT_SECRET') !== false && getenv('GOOGLE_CLIENT_SECRET') !== ''),
            'GOOGLE_REDIRECT_URI'  => (getenv('GOOGLE_REDIRECT_URI')  !== false && getenv('GOOGLE_REDIRECT_URI')  !== ''),
            'FRONTEND_URL'         => (getenv('FRONTEND_URL')         !== false && getenv('FRONTEND_URL')         !== ''),
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
            'router_hint' => 'El enrutador puede referirse a "AuthController" gracias al alias global al final del archivo.',
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

    // ==============================
    // POST /auth/login-con-google (token id del lado cliente)
    // ==============================
    public function loginConGoogle(array $input): array
    {
        $tokenGoogle = $input['token_google'] ?? null;

        if (!$tokenGoogle || !is_string($tokenGoogle)) {
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
     * Procesa callback de Google OAuth y registra usuario en DB
     */
    public function googleCallback(): void
    {
        try {
            if (session_status() !== \PHP_SESSION_ACTIVE) { @session_start(); }

            // Validación de CSRF state (lee de sesión y, si no está, de cookie)
            $state    = $_GET['state'] ?? null;
            $expected = $_SESSION['oauth2_state'] ?? ($_COOKIE['oauth2_state'] ?? null);
            if (!$state || !$expected || !hash_equals($expected, $state)) {
                throw new \Exception('Parámetro state inválido o ausente');
            }
            unset($_SESSION['oauth2_state']);
            if (isset($_COOKIE['oauth2_state'])) {
                setcookie('oauth2_state', '', time() - 3600, '/', '', true, true);
            }

            $authCode = $_GET['code'] ?? null;
            if (!$authCode) {
                throw new \Exception('Código de autorización no recibido');
            }

            // Intercambiar código por token
            $tokenData = $this->exchangeCodeForToken($authCode);
            if (!$tokenData || !isset($tokenData['id_token'])) {
                throw new \Exception('No se pudo obtener ID token de Google');
            }

            // Validar ID token usando tu servicio existente
            $perfil = GoogleAuthService::validate($tokenData['id_token']);
            if (!$perfil) {
                throw new \Exception('ID token inválido');
            }

            // Obtener información adicional del usuario
            $userInfo = $this->getGoogleUserInfo($tokenData['access_token'] ?? '');

            // Crear o actualizar usuario en base de datos
            $user = $this->createOrUpdateUser($perfil, $userInfo);

            // Generar JWT usando tu servicio existente
            $jwtToken = JwtService::generate([
                'user_id'   => $user['id'],
                'sub'       => $perfil['sub'],
                'email'     => $perfil['email'],
                'tenant_id' => $user['tenant_id'],
                'plan'      => $user['plan']
            ], 24 * 60 * 60); // 24 horas

            // Crear sesión en base de datos
            $this->createSession($user['id'], $jwtToken);

            // Establecer cookie segura
            setcookie('auth_token', $jwtToken, [
                'expires'  => time() + (24 * 60 * 60),
                'path'     => '/',
                'domain'   => '',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Redirigir al frontend
            $frontendUrl = getenv('FRONTEND_URL') ?: 'https://tu-frontend.com';
            header("Location: {$frontendUrl}/dashboard?auth=success");
            exit;

        } catch (\Exception $e) {
            error_log('Google callback error: ' . $e->getMessage());

            $frontendUrl = getenv('FRONTEND_URL') ?: 'https://tu-frontend.com';
            header("Location: {$frontendUrl}/login?error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * POST /auth/logout
     */
    public function logout(): void
    {
        try {
            $token = $this->getTokenFromRequest();

            if ($token) {
                // Eliminar sesión de la base de datos
                \DatabaseManager::delete('user_sessions', 'token = ?', [$token]);
            }

            // Limpiar cookie
            setcookie('auth_token', '', time() - 3600, '/', '', true, true);

            echo json_encode([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error cerrando sesión',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /auth/me
     */
    public function me(): void
    {
        try {
            $user = $this->getCurrentUser();

            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'No autenticado']);
                return;
            }

            // No exponer datos sensibles
            unset($user['google_id']);

            echo json_encode([
                'user' => $user,
                'authenticated' => true
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error obteniendo usuario',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /auth/status
     */
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
            echo json_encode([
                'authenticated' => false,
                'error'         => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /auth/usage
     */
    public function usage(): void
    {
        try {
            $user = $this->requireAuth();

            // Uso diario
            $dailyUsage = \DatabaseManager::fetchOne(
                "SELECT COUNT(*) as count FROM assistant_usage WHERE user_id = ? AND created_at >= CURRENT_DATE",
                [$user['id']]
            )['count'];

            // Uso mensual
            $monthlyUsage = \DatabaseManager::fetchOne(
                "SELECT COUNT(*) as count FROM assistant_usage 
                 WHERE user_id = ? AND created_at >= DATE_TRUNC('month', CURRENT_DATE)",
                [$user['id']]
            )['count'];

            // Uso por asistente (últimos 7 días)
            $assistantUsage = \DatabaseManager::fetchAll(
                "SELECT assistant_id, COUNT(*) as count 
                 FROM assistant_usage 
                 WHERE user_id = ? AND created_at >= CURRENT_DATE - INTERVAL '7 days'
                 GROUP BY assistant_id 
                 ORDER BY count DESC",
                [$user['id']]
            );

            // Límites por plan
            $planLimits = [
                'basic'      => 50,
                'pro'        => 1000,
                'enterprise' => PHP_INT_MAX
            ];

            $dailyLimit = $planLimits[$user['plan']] ?? $planLimits['basic'];

            echo json_encode([
                'daily_usage'     => intval($dailyUsage),
                'daily_limit'     => $dailyLimit,
                'monthly_usage'   => intval($monthlyUsage),
                'assistant_usage' => $assistantUsage,
                'plan'            => $user['plan'],
                'remaining_today' => max(0, $dailyLimit - $dailyUsage)
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error'   => 'Error obteniendo estadísticas',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ==========================
    // MÉTODOS PRIVADOS DE APOYO
    // ==========================

    private function exchangeCodeForToken(string $authCode): ?array
    {
        $clientId     = getenv('GOOGLE_CLIENT_ID');
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $redirectUri  = getenv('GOOGLE_REDIRECT_URI') ?: 'https://cepcco-backend-production.up.railway.app/auth/google/callback';

        $postData = [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $authCode,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Error obteniendo token de Google: HTTP {$httpCode}");
        }

        return json_decode($response, true);
    }

    private function getGoogleUserInfo(string $accessToken): ?array
    {
        if (empty($accessToken)) return null;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://www.googleapis.com/oauth2/v2/userinfo',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$accessToken}"],
            CURLOPT_TIMEOUT        => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null; // No es crítico si no obtenemos info adicional
        }

        return json_decode($response, true);
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
            // Actualizar usuario existente
            \DatabaseManager::update(
                'users',
                $userData,
                'id = ?',
                [$existingUser['id']]
            );
            $userId = $existingUser['id'];
        } else {
            // Crear nuevo usuario
            $userData['tenant_id'] = $this->determineTenantId();
            $userId = \DatabaseManager::insert('users', $userData);
        }

        // Retornar datos completos del usuario
        return \DatabaseManager::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    private function determineTenantId(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';

        // Mapear dominios a tenants
        $tenantMap = [
            'empresa-a.com' => 'empresa_a',
            'startup-b.com' => 'startup_b',
            // Agregar más mappings según sea necesario
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
        // Desde header Authorization
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // Desde cookie
        return $_COOKIE['auth_token'] ?? null;
    }

    private function getCurrentUser(): ?array
    {
        $token = $this->getTokenFromRequest();

        if (!$token) {
            return null;
        }

        // Validar JWT usando tu servicio existente
        $payload = JwtService::validate($token);

        if (!$payload) {
            return null;
        }

        // Verificar que la sesión existe y no ha expirado
        $session = \DatabaseManager::fetchOne(
            "SELECT * FROM user_sessions WHERE token = ? AND expires_at > NOW()",
            [$token]
        );

        if (!$session) {
            return null;
        }

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

// ==============================
// Alias global para routers sin namespaces
// ==============================
namespace {
    if (!class_exists('AuthController', false)) {
        class_alias(\Controllers\AuthController::class, 'AuthController');
    }
}
