<?php
declare(strict_types=1);

use Services\DB;

/**
 * /metrics en formato Prometheus (simple).
 * Métricas actuales: solo uptime y conexión DB.
 */
final class MetricsController
{
    public function index(): string
    {
        $lines = [];
        $lines[] = '# HELP app_up 1 si la app responde';
        $lines[] = '# TYPE app_up gauge';
        $lines[] = 'app_up 1';

        // DB connectivity
        try {
            $pdo = DB::conn();
            $pdo->query('SELECT 1');
            $lines[] = '# HELP db_up 1 si la DB responde';
            $lines[] = '# TYPE db_up gauge';
            $lines[] = 'db_up 1';
        } catch (\Throwable $e) {
            $lines[] = 'db_up 0';
        }

        header('Content-Type: text/plain; version=0.0.4');
        return implode("\n", $lines) . "\n";
    }
}
