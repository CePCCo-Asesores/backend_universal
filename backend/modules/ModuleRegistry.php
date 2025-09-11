<?php
namespace Modules;
class ModuleRegistry {
  /** Registra aquí tus módulos disponibles */
  public static function resolve(string $name): ?ModuleInterface {
    $map = [
      'ADIA_V1'       => \Modules\ADIA_V1\Module::class,
      'FABRICADOR_V1' => \Modules\FABRICADOR_V1\Module::class,
      // agrega los que vayas creando
    ];
    $cls = $map[$name] ?? null;
    return $cls ? new $cls() : null;
  }
}
