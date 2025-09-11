<?php
declare(strict_types=1);

namespace Controllers;

use Services\Migrator;
// use Services\JwtService; // si quieres validar rol/claim

final class AdminController
{
    // POST /admin/migrate { "module": "NEUROPLAN_360" | "*" }
    public function migrate(array $input): array
    {
        // TODO: valida JWT y rol admin aquí

        $mod = (string)($input['module'] ?? '*');
        if ($mod === '*' || $mod === 'ALL') {
            $r = Migrator::applyAll();
            return ['ok'=>true, 'results'=>$r];
        }
        $r = Migrator::apply($mod);
        return ['ok'=>true, 'result'=>$r];
    }

    // GET /admin/migrate/status?module=NEUROPLAN_360
    public function status(array $input): array
    {
        $mod = (string)($input['module'] ?? '');
        if ($mod === '') { http_response_code(422); return ['error'=>'module requerido']; }
        $r = Migrator::status($mod);
        return ['ok'=>true, 'result'=>$r];
    }
}
