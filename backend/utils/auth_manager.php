<?php
/**
 * 📁 backend/utils/auth_manager.php
 * Gestor de autenticación Google OAuth y JWT
 */

require_once 'database_manager.php';

class AuthManager {
    
    private $jwtSecret;
    private $googleClientId;
    private $googleClientSecret;
    private $googleRedirectUri;
    
    public function __construct() {
        $this->jwtSecret = getenv('JWT_SECRET') ?: 'your-super-secret-key-change-in-production';
        $this->googleClientId = getenv('GOOGLE_CLIENT_ID');
        $this->googleClientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $this->googleRedirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'https://cepcco-backend-production.up.railway.app/auth/google/callback';
    }
    
    /**
     * Genera URL para autenticación Google OAuth
     */
    public function getGoogleAuthUrl() {
        $params = [
            'client_id' => $this->googleClientId,
            'redirect_uri' => $this->googleRedirectUri,
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * Procesa callback de Google OAuth
     */
    public function handleGoogleCallback($authCode) {
        try {
            // Intercambiar código por token
            $tokenData = $this->exchangeCodeForToken($authCode);
            
            if (!$tokenData || !isset($tokenData['access_token'])) {
                throw new Exception('No se pudo obtener access token de Google');
            }
            
            // Obtener información del usuario
            $userInfo = $this->getGoogleUserInfo($tokenData['access_token']);
            
            if (!$userInfo) {
                throw new Exception('No se pudo obtener información del usuario de Google');
            }
            
            // Crear o actualizar usuario en base de datos
            $user = $this->createOrUpdateUser($userInfo);
            
            // Generar JWT token
            $jwtToken = $this->generateJWT($user);
            
            // Crear sesión en base de datos
            $sessionId = $this->createSession($user['id'], $jwtToken);
            
            return [
                'success' => true,
                'user' => $user,
                'token' => $jwtToken,
                'session_id' => $sessionId
            ];
            
        } catch (Exception $e) {
            error_log('AuthManager Google callback error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Intercambia código de autorización por access token
     */
    private function exchangeCodeForToken($authCode) {
        $postData = [
            'client_id' => $this->googleClientId,
            'client_secret' => $this->googleClientSecret,
            'code' => $authCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->googleRedirectUri
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Error obteniendo token de Google: HTTP {$httpCode}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Obtiene información del usuario desde Google API
     */
    private function getGoogleUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://www.googleapis.com/oauth2/v2/userinfo',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$accessToken}"],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Error obteniendo info de usuario: HTTP {$httpCode}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Crea o actualiza usuario en base de datos
     */
    private function createOrUpdateUser($googleUser) {
        $existingUser = DatabaseManager::fetchOne(
            "SELECT * FROM users WHERE google_id = ? OR email = ?",
            [$googleUser['id'], $googleUser['email']]
        );
        
        $userData = [
            'email' => $googleUser['email'],
            'google_id' => $googleUser['id'],
            'name' => $googleUser['name'] ?? '',
            'avatar_url' => $googleUser['picture'] ?? null,
            'last_login' => date('Y-m-d H:i:s')
        ];
        
        if ($existingUser) {
            // Actualizar usuario existente
            DatabaseManager::update(
                'users',
                $userData,
                'id = ?',
                [$existingUser['id']]
            );
            
            $userId = $existingUser['id'];
        } else {
            // Crear nuevo usuario
            $userData['tenant_id'] = $this->determineTenantId();
            $userId = DatabaseManager::insert('users', $userData);
        }
        
        // Retornar datos completos del usuario
        return DatabaseManager::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }
    
    /**
     * Determina tenant_id basado en dominio o configuración
     */
    private function determineTenantId() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Mapear dominios a tenants
        $tenantMap = [
            'empresa-a.com' => 'empresa_a',
            'startup-b.com' => 'startup_b',
            // Agregar más mappings según sea necesario
        ];
        
        return $tenantMap[$host] ?? 'default';
    }
    
    /**
     * Genera token JWT
     */
    private function generateJWT($user) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'tenant_id' => $user['tenant_id'],
            'plan' => $user['plan'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 horas
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $this->jwtSecret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }
    
    /**
     * Valida y decodifica token JWT
     */
    public function validateJWT($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            [$header, $payload, $signature] = $parts;
            
            // Verificar firma
            $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], 
                base64_encode(hash_hmac('sha256', $header . '.' . $payload, $this->jwtSecret, true))
            );
            
            if ($signature !== $validSignature) {
                return false;
            }
            
            // Decodificar payload
            $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
            
            // Verificar expiración
            if ($payloadData['exp'] < time()) {
                return false;
            }
            
            return $payloadData;
            
        } catch (Exception $e) {
            error_log('JWT validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea sesión en base de datos
     */
    private function createSession($userId, $token) {
        return DatabaseManager::insert('user_sessions', [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + (24 * 60 * 60)),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Verifica si usuario está autenticado
     */
    public function requireAuth() {
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Token de autenticación requerido']);
            exit;
        }
        
        $payload = $this->validateJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido o expirado']);
            exit;
        }
        
        // Verificar que la sesión existe y no ha expirado
        $session = DatabaseManager::fetchOne(
            "SELECT * FROM user_sessions WHERE token = ? AND expires_at > NOW()",
            [$token]
        );
        
        if (!$session) {
            http_response_code(401);
            echo json_encode(['error' => 'Sesión inválida o expirada']);
            exit;
        }
        
        // Obtener datos completos del usuario
        $user = DatabaseManager::fetchOne("SELECT * FROM users WHERE id = ?", [$payload['user_id']]);
        
        if (!$user || $user['status'] !== 'active') {
            http_response_code(401);
            echo json_encode(['error' => 'Usuario no activo']);
            exit;
        }
        
        return $user;
    }
    
    /**
     * Obtiene token desde headers o cookies
     */
    private function getTokenFromRequest() {
        // Desde header Authorization
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        // Desde cookie
        return $_COOKIE['auth_token'] ?? null;
    }
    
    /**
     * Cierra sesión del usuario
     */
    public function logout($token = null) {
        if (!$token) {
            $token = $this->getTokenFromRequest();
        }
        
        if ($token) {
            DatabaseManager::delete('user_sessions', 'token = ?', [$token]);
        }
        
        // Limpiar cookie
        setcookie('auth_token', '', time() - 3600, '/', '', true, true);
        
        return ['success' => true, 'message' => 'Sesión cerrada exitosamente'];
    }
    
    /**
     * Obtiene usuario actual desde token
     */
    public function getCurrentUser() {
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            return null;
        }
        
        $payload = $this->validateJWT($token);
        
        if (!$payload) {
            return null;
        }
        
        return DatabaseManager::fetchOne("SELECT * FROM users WHERE id = ?", [$payload['user_id']]);
    }
    
    /**
     * Registra uso de asistente para usuario
     */
    public function logAssistantUsage($userId, $assistantId, $inputData, $outputData, $tokensUsed = 0, $processingTime = 0, $success = true, $errorMessage = null) {
        return DatabaseManager::insert('assistant_usage', [
            'user_id' => $userId,
            'assistant_id' => $assistantId,
            'input_data' => json_encode($inputData),
            'output_data' => json_encode($outputData),
            'tokens_used' => $tokensUsed,
            'processing_time' => $processingTime,
            'success' => $success,
            'error_message' => $errorMessage,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
    
    /**
     * Verifica límites de uso del usuario
     */
    public function checkUserLimits($userId, $assistantId) {
        $user = DatabaseManager::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return false;
        }
        
        // Obtener uso del día actual
        $dailyUsage = DatabaseManager::fetchOne(
            "SELECT COUNT(*) as count FROM assistant_usage WHERE user_id = ? AND created_at >= CURRENT_DATE",
            [$userId]
        )['count'];
        
        // Límites por plan
        $planLimits = [
            'basic' => 50,
            'pro' => 1000,
            'enterprise' => PHP_INT_MAX
        ];
        
        $userLimit = $planLimits[$user['plan']] ?? $planLimits['basic'];
        
        return $dailyUsage < $userLimit;
    }
}
