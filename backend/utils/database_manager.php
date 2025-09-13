<?php
/**
 * Shim mínimo para que el backend no falle si la capa de DB real aún no está.
 * Sustituir por la implementación real cuando conectes la base de datos.
 */
final class DatabaseManager {
    public static function fetchOne(string $sql, array $params = []): array {
        $s = strtolower($sql);
        if (strpos($s, 'count(') !== false) return ['count' => 0];
        if (strpos($s, 'from users') !== false && strpos($s, 'where id') !== false) {
            $id = $params[0] ?? 1;
            return [
                'id'        => (int)$id,
                'email'     => 'stub@example.com',
                'tenant_id' => 'default',
                'plan'      => 'basic',
                'status'    => 'active',
            ];
        }
        if (strpos($s, 'from user_sessions') !== false) {
            return ['token' => $params[0] ?? null, 'expires_at' => date('Y-m-d H:i:s', time()+86400)];
        }
        return [];
    }
    public static function fetchAll(string $sql, array $params = []): array { return []; }
    public static function insert(string $table, array $data): int { return 1; }
    public static function update(string $table, array $data, string $where, array $params = []): int { return 1; }
    public static function delete(string $table, string $where, array $params = []): int { return 1; }
}
