<?php
namespace Modules;

interface ModuleInterface
{
    /**
     * Ejecuta la lógica del módulo.
     * @param array $payload   Datos ya validados contra el contrato YAML del módulo.
     * @param array $authUser  Usuario autenticado (p.ej. ['email'=>..., 'sub'=>...]); puede venir vacío.
     * @return array           Respuesta JSON-serializable.
     */
    public function run(array $payload, array $authUser = []): array;
}

