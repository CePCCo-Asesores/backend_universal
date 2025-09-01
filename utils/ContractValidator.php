<?php
declare(strict_types=1);

namespace Utils;

use Symfony\Component\Yaml\Yaml;

class ContractValidator
{
    /**
     * Valida payload contra un contrato "simple" (clave 'required') ubicado en /contracts/<nombre>.yaml
     * Devuelve ['status' => 'válido'] o ['error' => [...]] con detalles.
     */
    public static function validate(string $contractName, array $payload): array
    {
        $contractsDir = dirname(__DIR__) . '/contracts/';
        $contractPath = $contractsDir . $contractName . '.yaml';

        if (!file_exists($contractPath)) {
            return ['error' => ["Contrato no encontrado: $contractName"]];
        }

        try {
            $contract = Yaml::parseFile($contractPath);
        } catch (\Throwable $e) {
            return ['error' => ["Error al parsear contrato: " . $e->getMessage()]];
        }

        if (!is_array($contract)) {
            return ['error' => ['Contrato no tiene formato YAML válido']];
        }

        // Licencia ética opcional
        if (isset($contract['licencia']) && is_array($contract['licencia'])) {
            $minimos = $contract['licencia']['principios_minimos'] ?? [];
            if (!empty($minimos) && !is_array($minimos)) {
                return ['error' => ['licencia.principios_minimos debe ser una lista']];
            }
        }

        // Contrato simple
        $required = $contract['required'] ?? null;
        if ($required === null) {
            return ['status' => 'válido'];
        }
        if (!is_array($required)) {
            return ['error' => ['required debe ser una lista']];
        }

        $faltantes = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                $faltantes[] = $field;
            }
        }

        if (!empty($faltantes)) {
            return ['error' => ['Faltan campos requeridos', 'campos' => $faltantes]];
        }

        return ['status' => 'válido'];
    }
}
