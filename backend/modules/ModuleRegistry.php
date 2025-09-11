<?php
declare(strict_types=1);

namespace Modules;

/**
 * Registry universal con autodescubrimiento:
 * - Busca subdirectorios en backend/modules/* con un Module.php
 * - Clase esperada: \Modules\<Carpeta>\Module que implemente ModuleInterface
 * - Puedes limitar qué módulos cargar con MODULE_ALLOWLIST="DEMO_V1,ADIA_V1"
 */
final class ModuleRegistry
{
    /** @var array<string,class-string<ModuleInterface>> */
    private static array $map;

    public static function resolve(string $name): ?ModuleInterface
    {
        $key = self::sanitize($name);

        if (!isset(self::$map)) {
            self::$map = self::discover();
        }

        $cls = self::$map[$key] ?? null;
        return $cls ? new $cls() : null;
    }

    private static function sanitize(string $s): string
    {
        // Normaliza el nombre del módulo a MAYÚSCULAS y solo [A-Z0-9_]
        $s = strtoupper($s);
        return preg_replace('/[^A-Z0-9_]/', '_', $s);
    }

    /**
     * Descubre módulos mirando carpetas bajo backend/modules/* que
     * contengan un Module.php con la clase \Modules\<Carpeta>\Module.
     *
     * @return array<string,class-string<ModuleInterface>>
     */
    private static function discover(): array
    {
        $map = [];

        // 1) Registros manuales opcionales (si quieres forzar algunos)
        //    Deja comentados si no los necesitas.
        $manual = [
            // 'DEMO_V1'       => \Modules\DEMO_V1\Module::class,
            // 'ADIA_V1'       => \Modules\ADIA_V1\Module::class,
            // 'FABRICADOR_V1' => \Modules\FABRICADOR_V1\Module::class,
        ];
        foreach ($manual as $k => $cls) {
            if (class_exists($cls) && is_subclass_of($cls, ModuleInterface::class)) {
                $map[self::sanitize($k)] = $cls;
            }
        }

        // 2) Autodescubrimiento por carpeta
        $base = __DIR__; // .../backend/modules
        $it = new \DirectoryIterator($base);

        foreach ($it as $f) {
            if (!$f->isDir() || $f->isDot()) continue;

            $dirName = $f->getFilename();                  // p.ej. "DEMO_V1"
            $moduleKey = self::sanitize($dirName);         // "DEMO_V1"
            $modulePhp = $base . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . 'Module.php';
            if (!is_file($modulePhp)) continue;

            // Incluye el archivo para cargar la clase si aún no existe
            if (!class_exists("\\Modules\\{$dirName}\\Module")) {
                require_once $modulePhp;
            }

            $class = "\\Modules\\{$dirName}\\Module";
            if (!class_exists($class)) continue;
            if (!is_subclass_of($class, ModuleInterface::class)) continue;

            $map[$moduleKey] = $class;
        }

        // 3) Allowlist opcional por ENV (coma-separado)
        //    Ej.: MODULE_ALLOWLIST="DEMO_V1,ADIA_V1"
        $allow = getenv('MODULE_ALLOWLIST');
        if ($allow) {
            $allowed = array_filter(array_map(
                fn($s) => self::sanitize($s),
                explode(',', $allow)
            ));
            $map = array_intersect_key($map, array_flip($allowed));
        }

        return $map;
    }
}
