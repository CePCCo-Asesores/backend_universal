#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php'; // si usas composer autoload
require_once __DIR__ . '/../services/DB.php';
require_once __DIR__ . '/../services/Migrator.php';

use Services\Migrator;

echo "== Running module migrations ==\n";
try {
    $results = Migrator::applyAll();
    foreach ($results as $module => $res) {
        printf("[%s] applied=%d skipped=%d\n", $module, $res['applied'], $res['skipped']);
        foreach ($res['files'] as $f) {
            printf("  - %s: %s (%dms)\n", $f['file'], $f['status'], $f['ms']);
        }
    }
    echo "== Done ==\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration error: {$e->getMessage()}\n");
    exit(1);
}
