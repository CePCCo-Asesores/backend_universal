<?php
/**
 * Controlador temporal para diagnosticar PHP
 */

class PhpInfoController {
    
    public function info() {
        echo json_encode([
            'php_version' => phpversion(),
            'loaded_extensions' => get_loaded_extensions(),
            'pdo_drivers' => class_exists('PDO') ? PDO::getAvailableDrivers() : 'PDO no disponible',
            'environment_vars' => [
                'PGHOST' => getenv('PGHOST') ?: 'No set',
                'PGPORT' => getenv('PGPORT') ?: 'No set',
                'PGDATABASE' => getenv('PGDATABASE') ?: 'No set',
                'PGUSER' => getenv('PGUSER') ? 'Set' : 'No set',
                'PGPASSWORD' => getenv('PGPASSWORD') ? 'Set' : 'No set'
            ]
        ], JSON_PRETTY_PRINT);
    }
}
