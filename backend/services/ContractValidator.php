<?php
declare(strict_types=1);

namespace Services;

use Exceptions\PreconditionException;
use Exceptions\PostconditionException;
use Exceptions\InvariantException;

/**
 * Valida reglas de contrato declaradas por módulo.
 *
 * Estructura esperada del archivo de contrato (YAML o JSON):
 *
 * pre:
 *   - { path: 'action', op: 'in', value: ['start','step','generate','direct'], message: 'action inválida' }
 * post:
 *   - { path: 'ok', op: 'eq', value: true, message: 'respuesta debe tener ok=true' }
 * invariants:
 *   - { path: '.', op: 'required' }
 *
 * - path: dot-notation
 * - op:   eq, neq, gt, gte, lt, lte, in, nin, required, type, minLength, maxLength, regex, notEmpty
 */
final class ContractValidator
{
    /** Valida PRE-condiciones sobre el payload de entrada del módulo. */
    public static function validatePre(string $module, array $payload): void
    {
        $rules = self::loadRules($module, 'pre');
        if ($rules) {
            ContractGuard::assertPre($payload, $rules, $module . ':pre');
        }
    }

    /** Valida POST-condiciones sobre la respuesta del módulo. */
    public static function validatePost(string $module, array $result): void
    {
        $rules = self::loadRules($module, 'post');
        if ($rules) {
            ContractGuard::assertPost($result, $rules, $module . ':post');
        }
    }

    /** Valida INVARIANTES sobre un estado intermedio o final. */
    public static function assertInvariants(string $module, array $state): void
    {
        $rules = self::loadRules($module, 'invariants');
        if ($rules) {
            ContractGuard::assertInvariant($state, $rules, $module . ':invariants');
        }
    }

    /* ==================== helpers ==================== */

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function loadRules(string $module, string $section): array
    {
        $contract = self::loadContractArray($module);
        $rules = $contract[$section] ?? [];
        return is_array($rules) ? array_values($rules) : [];
    }

    /**
     * Carga el contrato del módulo en array asociativo.
     * Busca primero dentro del módulo y luego en /backend/contracts como fallback.
     *
     * @return array<string,mixed>
     */
    private static function loadContractArray(string $module): array
    {
        $file = self::resolveContractFile($module);
        if (!$file) {
            return [];
        }

        // Intentar Symfony Yaml si existe
        if (\str_ends_with($file, '.yaml') || \str_ends_with($file, '.yml')) {
            if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                /** @var array<string,mixed>|null $parsed */
                $parsed = \Symfony\Component\Yaml\Yaml::parseFile($file);
                return is_array($parsed) ? $parsed : [];
            }
            // Intentar ext-yaml
            if (function_exists('yaml_parse_file')) {
                /** @var array<string,mixed>|false $parsed */
                $parsed = @yaml_parse_file($file);
                return is_array($parsed) ? $parsed : [];
            }
            // Sin parser YAML disponible
            return [];
        }

        if (\str_ends_with($file, '.json')) {
            $raw = @file_get_contents($file);
            if ($raw === false) return [];
            $parsed = json_decode($raw, true);
            return is_array($parsed) ? $parsed : [];
        }

        return [];
    }

    private static function resolveContractFile(string $module): ?string
    {
        // backend/…/modules/<MODULE>/contract.{yaml,yml,json}
        $root = dirname(__DIR__); // .../backend
        $candidates = [
            $root . "/modules/{$module}/contract.yaml",
            $root . "/modules/{$module}/contract.yml",
            $root . "/modules/{$module}/contract.json",
            // fallback legacy (si existiera)
            $root . "/contracts/{$module}.yaml",
            $root . "/contracts/{$module}.yml",
            $root . "/contracts/{$module}.json",
        ];
        foreach ($candidates as $p) {
            if (is_readable($p)) return $p;
        }
        return null;
    }
}
