<?php
/**
 * 📁 backend/utils/database_manager.php
 * Gestor de base de datos PostgreSQL para el sistema universal
 */

class DatabaseManager {
    
    private static $connection = null;
    private static $initialized = false;
    
    /**
     * Obtiene conexión singleton a PostgreSQL
     */
    public static function getConnection() {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }
    
    /**
     * Crea nueva conexión a PostgreSQL
     */
    private static function createConnection() {
        try {
            $host = getenv('PGHOST');
            $port = getenv('PGPORT'); 
            $dbname = getenv('PGDATABASE');
            $user = getenv('PGUSER');
            $password = getenv('PGPASSWORD');
            
            if (!$host || !$port || !$dbname || !$user || !$password) {
                throw new Exception("Variables de base de datos no configuradas correctamente");
            }
            
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 30
            ];
            
            $pdo = new PDO($dsn, $user, $password, $options);
            
            error_log("DatabaseManager: Conexión exitosa a PostgreSQL");
            return $pdo;
            
        } catch (PDOException $e) {
            error_log("DatabaseManager PDO error: " . $e->getMessage());
            throw new Exception("Error conectando a base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("DatabaseManager error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Inicializa el esquema de base de datos
     */
    public static function initializeSchema() {
        if (self::$initialized) {
            return true;
        }
        
        try {
            $db = self::getConnection();
            
            // Tabla de usuarios
            $db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    google_id VARCHAR(100) UNIQUE,
                    name VARCHAR(255),
                    avatar_url TEXT,
                    tenant_id VARCHAR(50) DEFAULT 'default',
                    plan VARCHAR(50) DEFAULT 'basic',
                    status VARCHAR(20) DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP
                )
            ");
            
            // Tabla de sesiones
            $db->exec("
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                    token VARCHAR(255) UNIQUE NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    ip_address INET,
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Tabla de uso de asistentes
            $db->exec("
                CREATE TABLE IF NOT EXISTS assistant_usage (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                    assistant_id VARCHAR(50) NOT NULL,
                    input_data JSONB,
                    output_data JSONB,
                    tokens_used INTEGER DEFAULT 0,
                    processing_time INTEGER DEFAULT 0,
                    success BOOLEAN DEFAULT true,
                    error_message TEXT,
                    ip_address INET,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Tabla de API keys por usuario/tenant
            $db->exec("
                CREATE TABLE IF NOT EXISTS user_api_keys (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                    service VARCHAR(50) NOT NULL,
                    api_key TEXT NOT NULL,
                    is_active BOOLEAN DEFAULT true,
                    monthly_usage INTEGER DEFAULT 0,
                    usage_limit INTEGER DEFAULT 1000,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(user_id, service)
                )
            ");
            
            // Tabla de configuración por tenant
            $db->exec("
                CREATE TABLE IF NOT EXISTS tenant_config (
                    id SERIAL PRIMARY KEY,
                    tenant_id VARCHAR(50) UNIQUE NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    domain VARCHAR(255),
                    branding JSONB,
                    limits JSONB,
                    billing_model VARCHAR(50) DEFAULT 'shared_with_tracking',
                    is_active BOOLEAN DEFAULT true,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Índices para optimización
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_google_id ON users(google_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_token ON user_sessions(token)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON user_sessions(user_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_usage_user_id ON assistant_usage(user_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_usage_assistant_id ON assistant_usage(assistant_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_usage_created_at ON assistant_usage(created_at)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_api_keys_user_id ON user_api_keys(user_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_tenant_config_tenant_id ON tenant_config(tenant_id)");
            
            self::$initialized = true;
            error_log("DatabaseManager: Schema inicializado correctamente");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("DatabaseManager schema error: " . $e->getMessage());
            throw new Exception("Error inicializando schema: " . $e->getMessage());
        }
    }
    
    /**
     * Ejecuta query con parámetros preparados
     */
    public static function query($sql, $params = []) {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("DatabaseManager query error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Error ejecutando query: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene un solo registro
     */
    public static function fetchOne($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene múltiples registros
     */
    public static function fetchAll($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Inserta registro y retorna ID
     */
    public static function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";
        $stmt = self::query($sql, $data);
        
        $result = $stmt->fetch();
        return $result['id'] ?? null;
    }
    
    /**
     * Actualiza registros
     */
    public static function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause}, updated_at = CURRENT_TIMESTAMP WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Elimina registros
     */
    public static function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Verifica estado de conexión
     */
    public static function healthCheck() {
        try {
            $db = self::getConnection();
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            return [
                'status' => 'healthy',
                'connected' => true,
                'test_query' => $result['test'] == 1,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'connected' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Obtiene estadísticas de la base de datos
     */
    public static function getStats() {
        try {
            $stats = [
                'users' => self::fetchOne("SELECT COUNT(*) as count FROM users")['count'],
                'active_sessions' => self::fetchOne("SELECT COUNT(*) as count FROM user_sessions WHERE expires_at > NOW()")['count'],
                'assistant_usage_today' => self::fetchOne("SELECT COUNT(*) as count FROM assistant_usage WHERE created_at >= CURRENT_DATE")['count'],
                'total_usage' => self::fetchOne("SELECT COUNT(*) as count FROM assistant_usage")['count']
            ];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("DatabaseManager stats error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cierra conexión
     */
    public static function closeConnection() {
        self::$connection = null;
        self::$initialized = false;
    }
}
