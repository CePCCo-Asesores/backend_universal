<?php
/**
 * 📁 /bin/init_database.php
 * Script para inicializar el schema de la base de datos
 * EJECUTAR SOLO UNA VEZ
 */

// Cargar dependencias
require_once __DIR__ . '/../utils/database_manager.php';

echo "🚀 Inicializando base de datos...\n";

try {
    // Probar conexión
    echo "📡 Probando conexión a PostgreSQL...\n";
    $healthCheck = DatabaseManager::healthCheck();
    
    if (!$healthCheck['connected']) {
        throw new Exception("No se puede conectar a la base de datos: " . $healthCheck['error']);
    }
    
    echo "✅ Conexión exitosa a PostgreSQL\n";
    
    // Inicializar schema
    echo "📋 Creando tablas y estructura...\n";
    $success = DatabaseManager::initializeSchema();
    
    if ($success) {
        echo "✅ Schema inicializado correctamente\n";
        
        // Mostrar estadísticas
        echo "📊 Verificando estructura...\n";
        $stats = DatabaseManager::getStats();
        
        if ($stats) {
            echo "   - Usuarios: {$stats['users']}\n";
            echo "   - Sesiones activas: {$stats['active_sessions']}\n";
            echo "   - Uso total: {$stats['total_usage']}\n";
        }
        
        echo "\n🎉 ¡Base de datos lista para usar!\n";
        echo "\n📋 Próximos pasos:\n";
        echo "   1. Probar autenticación: GET /auth/google\n";
        echo "   2. Verificar salud: GET /health/database\n";
        echo "   3. Usar asistentes con auth: POST /assistant/execute\n";
        
    } else {
        throw new Exception("Error inicializando schema");
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔍 Verificar:\n";
    echo "   - Variables de entorno configuradas en Railway\n";
    echo "   - Credenciales de PostgreSQL correctas\n";
    echo "   - Conexión de red a la base de datos\n";
    exit(1);
}

echo "\n✨ Script completado exitosamente\n";
?>
