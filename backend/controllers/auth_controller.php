<?php
/**
 * 📁 backend/controllers/auth_controller.php
 * Controlador de autenticación Google OAuth
 */

require_once 'utils/auth_manager.php';
require_once 'utils/database_manager.php';

class AuthController {
    
    private $authManager;
    
    public function __construct() {
        $this->authManager = new AuthManager();
    }
    
    /**
     * Endpoint: GET /auth/google
     * Redirige a Google OAuth
     */
    public function googleAuth() {
        try {
            $authUrl = $this->authManager->getGoogleAuthUrl();
            
            header("Location: $authUrl");
            exit;
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error iniciando autenticación',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: GET /auth/google/callback
     * Procesa callback de Google OAuth
     */
    public function googleCallback() {
        try {
            $authCode = $_GET['code'] ?? null;
            
            if (!$authCode) {
                throw new Exception('Código de autorización no recibido');
            }
            
            $result = $this->authManager->handleGoogleCallback($authCode);
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Establecer cookie segura
            setcookie('auth_token', $result['token'], [
                'expires' => time() + (24 * 60 * 60), // 24 horas
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Respuesta exitosa - redirigir al frontend o dashboard
            $frontendUrl = getenv('FRONTEND_URL') ?: 'https://tu-frontend.com';
            header("Location: {$frontendUrl}/dashboard?auth=success");
            exit;
            
        } catch (Exception $e) {
            error_log('AuthController callback error: ' . $e->getMessage());
            
            $frontendUrl = getenv('FRONTEND_URL') ?: 'https://tu-frontend.com';
            header("Location: {$frontendUrl}/login?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
    
    /**
     * Endpoint: POST /auth/logout
     * Cierra sesión del usuario
     */
    public function logout() {
        try {
            $result = $this->authManager->logout();
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error cerrando sesión',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: GET /auth/me
     * Obtiene información del usuario actual
     */
    public function me() {
        try {
            $user = $this->authManager->getCurrentUser();
            
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
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error obteniendo usuario',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: GET /auth/status
     * Verifica estado de autenticación
     */
    public function status() {
        try {
            $user = $this->authManager->getCurrentUser();
            
            echo json_encode([
                'authenticated' => $user !== null,
                'user_id' => $user['id'] ?? null,
                'email' => $user['email'] ?? null,
                'plan' => $user['plan'] ?? null,
                'tenant_id' => $user['tenant_id'] ?? null
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'authenticated' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: GET /auth/usage
     * Obtiene estadísticas de uso del usuario
     */
    public function usage() {
        try {
            $user = $this->authManager->requireAuth();
            
            // Uso diario
            $dailyUsage = DatabaseManager::fetchOne(
                "SELECT COUNT(*) as count FROM assistant_usage WHERE user_id = ? AND created_at >= CURRENT_DATE",
                [$user['id']]
            )['count'];
            
            // Uso por asistente (últimos 7 días)
            $assistantUsage = DatabaseManager::fetchAll(
                "SELECT assistant_id, COUNT(*) as count 
                 FROM assistant_usage 
                 WHERE user_id = ? AND created_at >= CURRENT_DATE - INTERVAL '7 days'
                 GROUP BY assistant_id 
                 ORDER BY count DESC",
                [$user['id']]
            );
            
            // Uso mensual
            $monthlyUsage = DatabaseManager::fetchOne(
                "SELECT COUNT(*) as count FROM assistant_usage 
                 WHERE user_id = ? AND created_at >= DATE_TRUNC('month', CURRENT_DATE)",
                [$user['id']]
            )['count'];
            
            // Límites del plan
            $planLimits = [
                'basic' => 50,
                'pro' => 1000,
                'enterprise' => PHP_INT_MAX
            ];
            
            $dailyLimit = $planLimits[$user['plan']] ?? $planLimits['basic'];
            
            echo json_encode([
                'daily_usage' => intval($dailyUsage),
                'daily_limit' => $dailyLimit,
                'monthly_usage' => intval($monthlyUsage),
                'assistant_usage' => $assistantUsage,
                'plan' => $user['plan'],
                'remaining_today' => max(0, $dailyLimit - $dailyUsage)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error obteniendo estadísticas',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: POST /auth/upgrade
     * Simula upgrade de plan (implementar con Stripe/PayPal más adelante)
     */
    public function upgrade() {
        try {
            $user = $this->authManager->requireAuth();
            $newPlan = $_POST['plan'] ?? $_GET['plan'] ?? null;
            
            if (!in_array($newPlan, ['basic', 'pro', 'enterprise'])) {
                throw new Exception('Plan inválido');
            }
            
            DatabaseManager::update(
                'users',
                ['plan' => $newPlan],
                'id = ?',
                [$user['id']]
            );
            
            echo json_encode([
                'success' => true,
                'message' => "Plan actualizado a {$newPlan}",
                'new_plan' => $newPlan
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error actualizando plan',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: GET /auth/sessions
     * Lista sesiones activas del usuario
     */
    public function sessions() {
        try {
            $user = $this->authManager->requireAuth();
            
            $sessions = DatabaseManager::fetchAll(
                "SELECT id, ip_address, user_agent, created_at, expires_at 
                 FROM user_sessions 
                 WHERE user_id = ? AND expires_at > NOW() 
                 ORDER BY created_at DESC",
                [$user['id']]
            );
            
            echo json_encode([
                'sessions' => $sessions,
                'total' => count($sessions)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error obteniendo sesiones',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: DELETE /auth/sessions/{id}
     * Elimina sesión específica
     */
    public function deleteSession() {
        try {
            $user = $this->authManager->requireAuth();
            $sessionId = $_GET['id'] ?? null;
            
            if (!$sessionId) {
                throw new Exception('ID de sesión requerido');
            }
            
            $deleted = DatabaseManager::delete(
                'user_sessions',
                'id = ? AND user_id = ?',
                [$sessionId, $user['id']]
            );
            
            if ($deleted > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión eliminada'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesión no encontrada'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error eliminando sesión',
                'message' => $e->getMessage()
            ]);
        }
    }
}
