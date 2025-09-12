<?php
declare(strict_types=1);

use Services\DB;
use Modules\ModuleRegistry;

final class HealthController
{
    public function ping(): array
    {
        $status = ['ok' => true, 'time' => date('c')];

        // DB check
        try {
            DB::conn()->query('SELECT 1');
            $status['db'] = 'up';
        } catch (\Throwable $e) {
            $status['db'] = 'down';
            $status['ok'] = false;
        }

        // Módulos disponibles
        $status['modules'] = ModuleRegistry::list();

        http_response_code($status['ok'] ? 200 : 500);
        return $status;
    }
}
