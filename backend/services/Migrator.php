<?php
declare(strict_types=1);

namespace Services;

final class Migrator
{
    /**
     * Aplica las migraciones de un módulo (backend/modules/<MODULE>/migrations/*.sql)
     * @return array{applied: int, skipped: int, module: string, files: array<int, array{file:string, status:string, ms:int}>}
     * @throws \RuntimeException ante cambios de checksum (forzar renombrar archivo)
     */
    public static function apply(string $module): array
    {
        $module = self::sanitize($module);
        $schema = $module; // tu convención: schema = nombre del módulo en MAYÚSCULAS
        $pdo    = DB::conn();

        // Descubrir carpeta de migraciones
        $base = __DIR__ . "/../modules/{$module}/migrations";
        if (!is_dir($base)) {
            return ['applied'=>0,'skipped'=>0,'module'=>$module,'files'=>[]];
        }

        // Asegurar schema y tabla de control
        self::ensureSchema($pdo, $schema);
        self::ensureMigrationsTable($pdo, $schema);

        // Listar archivos *.sql ordenados
        $files = glob($base . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $applied = 0; $skipped = 0; $details = [];

        foreach ($files as $path) {
            $file = basename($path);
            $id   = $file; // id = nombre del archivo
            $sha  = hash_file('sha256', $path) ?: '';

            // Si ya se aplicó con el mismo checksum → saltar
            $st = $pdo->prepare("SELECT checksum FROM {$schema}.__migrations WHERE id=:id");
            $st->execute([':id'=>$id]);
            $prev = $st->fetchColumn();

            if ($prev !== false) {
                if ($prev !== $sha) {
                    throw new \RuntimeException("Checksum cambiado para {$module}/migrations/{$file}. Renombra el archivo (e.g. 003_fix.sql).");
                }
                $skipped++;
                $details[] = ['file'=>$file, 'status'=>'skipped', 'ms'=>0];
                continue;
            }

            // Ejecutar el SQL dentro de una transacción
            $sql = file_get_contents($path);
            if ($sql === false) { throw new \RuntimeException("No se puede leer {$path}"); }

            $t0 = microtime(true);
            try {
                if (!$pdo->inTransaction()) $pdo->beginTransaction();
                $pdo->exec($sql);
                // Registrar la migración
                $ins = $pdo->prepare("INSERT INTO {$schema}.__migrations(id, checksum) VALUES(:id, :sha)");
                $ins->execute([':id'=>$id, ':sha'=>$sha]);
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            $ms = (int)round((microtime(true)-$t0)*1000);
            $applied++;
            $details[] = ['file'=>$file, 'status'=>'applied', 'ms'=>$ms];
        }

        return ['applied'=>$applied, 'skipped'=>$skipped, 'module'=>$module, 'files'=>$details];
    }

    /**
     * Aplica migraciones de TODOS los módulos que tengan carpeta /modules/*/migrations
     * @return array<string, array> mapa módulo → resultado de apply()
     */
    public static function applyAll(): array
    {
        $base = __DIR__ . '/../modules';
        $out  = [];

        if (!is_dir($base)) return $out;

        $it = new \DirectoryIterator($base);
        foreach ($it as $f) {
            if (!$f->isDir() || $f->isDot()) continue;
            $module = $f->getFilename();
            if (is_dir("{$base}/{$module}/migrations")) {
                $out[$module] = self::apply($module);
            }
        }
        return $out;
    }

    /** Estado de migraciones para un módulo */
    public static function status(string $module): array
    {
        $module = self::sanitize($module);
        $schema = $module;
        $pdo    = DB::conn();
        self::ensureSchema($pdo, $schema);
        self::ensureMigrationsTable($pdo, $schema);

        $st = $pdo->query("SELECT id, checksum, applied_at FROM {$schema}.__migrations ORDER BY applied_at ASC");
        $rows = $st ? $st->fetchAll(\PDO::FETCH_ASSOC) : [];
        return ['module'=>$module, 'migrations'=>$rows];
    }

    /* ---------------- helpers ---------------- */

    private static function ensureSchema(\PDO $pdo, string $schema): void
    {
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS {$schema}");
    }

    private static function ensureMigrationsTable(\PDO $pdo, string $schema): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$schema}.__migrations(
              id TEXT PRIMARY KEY,
              checksum TEXT NOT NULL,
              applied_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )");
    }

    private static function sanitize(string $s): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $s));
    }
}
