<?php
declare(strict_types=1);

/**
 * DatabaseManager - implementación real (PostgreSQL en Railway).
 * - Lee DATABASE_URL (recomendado) o POSTGRES_URL.
 * - Alternativa: DB_DRIVER (pgsql|mysql), DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
 * - Usa PDO con ERRMODE_EXCEPTION.
 *
 * Métodos:
 *   - pdo(), fetchOne(), fetchAll(), insert(), update(), delete()
 *   - healthCheck(), initializeSchema(), getStats()
 */

final class DatabaseManager
{
    private static ?\PDO $pdo = null;

    // ---------------- Conexión ----------------
    private static function connect(): \PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }

        $dbUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: '';
        $driver = 'pgsql';
        $dsn = '';
        $user = '';
        $pass = '';

        if ($dbUrl !== '') {
            // Ej: postgresql://user:pass@postgres.railway.internal:5432/railway?sslmode=require
            $parsed = parse_url($dbUrl);
            if ($parsed === false) {
                throw new \RuntimeException('DATABASE_URL inválido');
            }

            $scheme = $parsed['scheme'] ?? 'postgresql';
            // Normalizar a PDO
            $driver = ($scheme === 'postgres' || $scheme === 'postgresql') ? 'pgsql' : $scheme;

            $host = $parsed['host'] ?? 'localhost';
            $port = (int)($parsed['port'] ?? 5432);
            $user = urldecode($parsed['user'] ?? '');
            $pass = urldecode($parsed['pass'] ?? '');
            $db   = ltrim($parsed['path'] ?? '', '/');

            // Preservar query (sslmode, etc.)
            $query = $parsed['query'] ?? '';
            $qs = [];
            if ($query !== '') {
                parse_str($query, $qs);
            }

            if ($driver === 'pgsql') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
                if (!empty($qs['sslmode'])) {
                    $dsn .= ";sslmode={$qs['sslmode']}";
                }
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            } else {
                throw new \RuntimeException("Driver no soportado: {$driver}");
            }
        } else {
            // Plan B: variables sueltas
            $driver = getenv('DB_DRIVER') ?: 'pgsql';
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = (int)(getenv('DB_PORT') ?: ($driver === 'pgsql' ? 5432 : 3306));
            $db   = getenv('DB_NAME') ?: '';
            $user = getenv('DB_USER') ?: '';
            $pass = getenv('DB_PASS') ?: '';

            if ($driver === 'pgsql') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            } else {
                throw new \RuntimeException("Driver no soportado: {$driver}");
            }
        }

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        self::$pdo = new \PDO($dsn, $user, $pass, $options);
        return self::$pdo;
    }

    public static function pdo(): \PDO
    {
        return self::connect();
    }

    // ---------------- Helpers de consulta ----------------
    public static function fetchOne(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(self::normalizeParams($params));
        $row = $stmt->fetch();
        return $row ?: [];
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(self::normalizeParams($params));
        return $stmt->fetchAll() ?: [];
    }

    /**
     * insert('users', ['email'=>'a@b', 'name'=>'x']) -> int id
     */
    public static function insert(string $table, array $data): int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('insert(): datos vacíos');
        }

        $cols   = array_keys($data);
        $place  = array_map(fn($c) => ':' . $c, $cols);

        $sql = 'INSERT INTO ' . $table
             . ' (' . implode(',', $cols) . ')'
             . ' VALUES (' . implode(',', $place) . ')';

        $pdo    = self::pdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $id     = 0;

        if ($driver === 'pgsql') {
            $sql .= ' RETURNING id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(self::normalizeParams($data));
            $row = $stmt->fetch();
            if (!$row || !isset($row['id'])) {
                throw new \RuntimeException('insert(): no se pudo recuperar id (RETURNING)');
            }
            $id = (int)$row['id'];
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(self::normalizeParams($data));
            $id = (int)$pdo->lastInsertId();
        }

        return $id;
    }

    /**
     * update('users', ['name'=>'Z'], 'id = ?', [1]) -> filas afectadas
     * Corrige mezcla de placeholders: convierte '?' del WHERE a :w1, :w2, ...
     */
    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('update(): datos vacíos');
        }

        // SET con placeholders nombrados
        $sets = [];
        foreach ($data as $k => $v) {
            $sets[] = "{$k} = :set_{$k}";
        }

        $execParams = [];
        foreach ($data as $k => $v) {
            $execParams[":set_{$k}"] = $v;
        }

        // WHERE: si hay '?', convertir a :w1, :w2, ...
        if (strpos($where, '?') !== false) {
            $i = 0;
            $whereSql = preg_replace_callback('/\?/', function () use (&$i) {
                $i++;
                return ":w{$i}";
            }, $where);

            $j = 1;
            foreach (array_values($params) as $v) {
                $execParams[":w{$j}"] = $v;
                $j++;
            }
        } else {
            // WHERE ya nombrado (id = :id)
            $whereSql = $where;
            foreach ($params as $k => $v) {
                $execParams[(str_starts_with((string)$k, ':') ? (string)$k : ':' . $k)] = $v;
            }
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $sets) . ' WHERE ' . $whereSql;
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($execParams);
        return $stmt->rowCount();
    }

    /**
     * delete('user_sessions', 'token = ?', [$token]) -> filas afectadas
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        // Permitir '?' posicionales
        if (strpos($where, '?') !== false) {
            $i = 0;
            $whereSql = preg_replace_callback('/\?/', function () use (&$i) {
                $i++;
                return ":w{$i}";
            }, $where);

            $execParams = [];
            $j = 1;
            foreach (array_values($params) as $v) {
                $execParams[":w{$j}"] = $v;
                $j++;
            }
        } else {
            $whereSql = $where;
            $execParams = self::normalizeParams($params);
        }

        $sql = 'DELETE FROM ' . $table . ' WHERE ' . $whereSql;
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($execParams);
        return $stmt->rowCount();
    }

    private static function normalizeParams(array $params): array
    {
        // Admite params posicionales (['a','b']) o nombrados ([':x'=>...])
        $out = [];
        $i = 1;
        foreach ($params as $k => $v) {
            if (is_int($k)) {
                $out[$i++] = $v; // posicional -> 1,2,3...
            } else {
                $out[ str_starts_with((string)$k, ':') ? (string)$k : ':' . $k ] = $v;
            }
        }
        return $out;
    }

    // ---------------- Salud / Schema / Stats ----------------
    public static function healthCheck(): array
    {
        try {
            $pdo = self::pdo();
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) ?: null;
            $ver = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION) ?: null;
            $row = self::fetchOne('SELECT 1 AS ok');
            return [
                'connected' => isset($row['ok']),
                'driver' => $driver,
                'server_version' => $ver,
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function initializeSchema(): bool
    {
        $pdo = self::pdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'pgsql') {
            throw new \RuntimeException('initializeSchema(): solo implementado para PostgreSQL');
        }

        $ddl = <<<SQL
-- Usuarios
CREATE TABLE IF NOT EXISTS users (
  id           BIGSERIAL PRIMARY KEY,
  email        TEXT NOT NULL UNIQUE,
  google_id    TEXT UNIQUE,
  name         TEXT,
  avatar_url   TEXT,
  tenant_id    TEXT NOT NULL DEFAULT 'default',
  plan         TEXT NOT NULL DEFAULT 'basic',
  status       TEXT NOT NULL DEFAULT 'active',
  last_login   TIMESTAMPTZ,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS users_google_id_unique
  ON users(google_id)
  WHERE google_id IS NOT NULL;

-- Trigger de updated_at
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS \$\$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
\$\$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Sesiones
CREATE TABLE IF NOT EXISTS user_sessions (
  id          BIGSERIAL PRIMARY KEY,
  user_id     BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token       TEXT NOT NULL UNIQUE,
  expires_at  TIMESTAMPTZ NOT NULL,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id    ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires_at ON user_sessions(expires_at);

-- Uso (opcional)
CREATE TABLE IF NOT EXISTS assistant_usage (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  assistant_id TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_assistant_usage_user_id     ON assistant_usage(user_id);
CREATE INDEX IF NOT EXISTS idx_assistant_usage_created_at  ON assistant_usage(created_at);
SQL;

        $pdo->beginTransaction();
        try {
            $pdo->exec($ddl);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getStats(): array
    {
        $users    = (int)(self::fetchOne('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
        $sessions = (int)(self::fetchOne('SELECT COUNT(*) AS c FROM user_sessions WHERE expires_at > NOW()')['c'] ?? 0);
        $usage    = (int)(self::fetchOne('SELECT COUNT(*) AS c FROM assistant_usage')['c'] ?? 0);

        return [
            'users' => $users,
            'active_sessions' => $sessions,
            'total_usage' => $usage,
        ];
    }
}
