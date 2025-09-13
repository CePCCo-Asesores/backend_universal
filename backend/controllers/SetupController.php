<?php
/**
 * 📁 /controllers/SetupController.php
 * Controlador temporal para setup inicial
 * ELIMINAR DESPUÉS DEL SETUP
 */

require_once 'utils/database_manager.php';

class SetupController {
    
    /**
     * Endpoint: GET /setup/database
     * Inicializa la base de datos
     */
    public function initDatabase() {
        try {
            // Verificar que no se ejecute múltiples veces
            $existing = DatabaseManager::fetchOne("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = 'users'");
            
            if ($existing && $existing['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Base de datos ya está inicializada',
                    'status' => 'already_setup'
                ]);
                return;
            }
            
            // Probar conexión
            $healthCheck = DatabaseManager::healthCheck();
            
            if (!$healthCheck['connected']) {
                throw new Exception("No se puede conectar a la base de datos: " . ($healthCheck['error'] ?? 'Error desconocido'));
            }
            
            // Inicializar schema
            $success = DatabaseManager::initializeSchema();
            
            if ($success) {
                // Obtener estadísticas
                $stats = DatabaseManager::getStats();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Base de datos inicializada correctamente',
                    'health_check' => $healthCheck,
                    'stats' => $stats,
                    'next_steps' => [
                        'test_auth' => '/auth/google',
                        'check_health' => '/health/database',
                        'test_assistant' => '/assistant/execute'
                    ]
                ], JSON_PRETTY_PRINT);
                
            } else {
                throw new Exception("Error inicializando schema");
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'suggestions' => [
                    'check_env_vars' => 'Verificar variables de entorno en Railway',
                    'check_credentials' => 'Verificar credenciales de PostgreSQL',
                    'check_network' => 'Verificar conexión de red'
                ]
            ], JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Endpoint: GET /setup/health
     * Verifica estado del sistema
     */
    public function health() {
        try {
            $checks = [
                'database_connection' => DatabaseManager::healthCheck(),
                'environment_vars' => [
                    'PGHOST' => getenv('PGHOST') ? '✅ Set' : '❌ Missing',
                    'PGUSER' => getenv('PGUSER') ? '✅ Set' : '❌ Missing',
                    'GOOGLE_CLIENT_ID' => getenv('GOOGLE_CLIENT_ID') ? '✅ Set' : '❌ Missing',
                    'JWT_SECRET' => getenv('JWT_SECRET') ? '✅ Set' : '❌ Missing'
                ]
            ];
            
            echo json_encode($checks, JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Endpoint: GET /setup/test-auth
     * Prueba el sistema de autenticación
     */
    public function testAuth() {
        try {
            require_once 'utils/auth_manager.php';
            
            $authManager = new AuthManager();
            $googleUrl = $authManager->getGoogleAuthUrl();
            
            echo json_encode([
                'success' => true,
                'google_auth_url' => $googleUrl,
                'message' => 'Sistema de autenticación configurado correctamente',
                'test_steps' => [
                    '1. Visita la URL de Google Auth',
                    '2. Autoriza la aplicación',
                    '3. Serás redirigido de vuelta',
                    '4. Deberías ver tu perfil de usuario'
                ]
            ], JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
