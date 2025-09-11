<?php
namespace Modules;
interface ModuleInterface {
  /** @return array respuesta JSON-serializable */
  public function run(array $payload, array $authUser = []): array;
}
