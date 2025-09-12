<?php
declare(strict_types=1);

namespace Services;

use Exceptions\PreconditionException;
use Exceptions\PostconditionException;
use Exceptions\InvariantException;

/**
 * Guardián de contratos: valida precondiciones, postcondiciones e invariantes.
 *
 * Formato de regla soportado:
 * [
 *   ['path' => 'user.id', 'op' => 'gt',  'value' => 0,           'message' => 'user.id > 0'],
 *   ['path' => 'status',  'op' => 'eq',  'value' => 'success',   'message' => 'status=success'],
 *   ['path' => 'tags',    'op' => 'minLength', 'value' => 1,     'message' => 'al menos 1 tag'],
 *   ['path' => 'obj',     'op' => 'required'],                               // debe existir y no ser null
 *   ['path' => 'email',   'op' => 'regex', 'value' => '/^.+@.+\..+$/' ],
 * ]
 *
 * Operadores: eq, neq, gt, gte, lt, lte, in, nin, required, type, minLength, maxLength, regex, notEmpty
 * - path usa dot-notation (ej. "user.profile.age")
 */
final class ContractGuard
{
    /** @param array<int,array<string,mixed>> $rules */
    public static function assertPre(array $data, array $rules, string $where = 'pre'): void
    {
        $violations = self::validate($data, $rules);
        if ($violations) {
            throw new PreconditionException('Precondition failed', [
                'where' => $where,
                'violations' => $violations,
            ]);
        }
    }

    /** @param array<int,array<string,mixed>> $rules */
    public static function assertPost(array $result, array $rules, string $where = 'post'): void
    {
        $violations = self::validate($result, $rules);
        if ($violations) {
            throw new PostconditionException('Postcondition failed', [
                'where' => $where,
                'violations' => $violations,
            ]);
        }
    }

    /** @param array<int,array<string,mixed>> $rules */
    public static function assertInvariant(array $state, array $rules, string $where = 'invariant'): void
    {
        $violations = self::validate($state, $rules);
        if ($violations) {
            throw new InvariantException('Invariant violated', [
                'where' => $where,
                'violations' => $violations,
            ]);
        }
    }

    /**
     * @param array<string,mixed> $data
     * @param array<int,array<string,mixed>> $rules
     * @return array<int,array{path:string,op:string,expected:mixed,actual:mixed,message?:string}>
     */
    public static function validate(array $data, array $rules): array
    {
        $violations = [];
        foreach ($rules as $rule) {
            $path = (string)($rule['path'] ?? '');
            $op   = (string)($rule['op'] ?? 'required');
            $exp  = $rule['value'] ?? null;

            $exists = self::hasPath($data, $path);
            $val    = $exists ? self::getPath($data, $path) : null;

            $ok = match ($op) {
                'required'  => $exists && $val !== null,
                'notEmpty'  => $exists && !self::isEmpty($val),
                'type'      => $exists && self::isType($val, (string)$exp),
                'eq'        => $exists && $val === $exp,
                'neq'       => $exists && $val !== $exp,
                'gt'        => $exists && is_numeric($val) && is_numeric($exp) && $val > $exp,
                'gte'       => $exists && is_numeric($val) && is_numeric($exp) && $val >= $exp,
                'lt'        => $exists && is_numeric($val) && is_numeric($exp) && $val < $exp,
                'lte'       => $exists && is_numeric($val) && is_numeric($exp) && $val <= $exp,
                'in'        => $exists && is_array($exp) && in_array($val, $exp, true),
                'nin'       => $exists && is_array($exp) && !in_array($val, $exp, true),
                'minLength' => $exists && self::len($val) >= (int)$exp,
                'maxLength' => $exists && self::len($val) <= (int)$exp,
                'regex'     => $exists && is_string($val) && @preg_match((string)$exp, $val) === 1,
                default     => false,
            };

            if (!$ok) {
                $violations[] = [
                    'path'     => $path,
                    'op'       => $op,
                    'expected' => $exp,
                    'actual'   => $val,
                    'message'  => isset($rule['message']) ? (string)$rule['message'] : null,
                ];
            }
        }
        return $violations;
    }

    /* ----------------- helpers ----------------- */

    /** @param array<string,mixed> $arr */
    private static function hasPath(array $arr, string $path): bool
    {
        if ($path === '' || $path === '.') return true;
        $parts = explode('.', $path);
        $cur = $arr;
        foreach ($parts as $p) {
            if (is_array($cur) && array_key_exists($p, $cur)) {
                $cur = $cur[$p];
            } else {
                return false;
            }
        }
        return true;
    }

    /** @param array<string,mixed> $arr */
    private static function getPath(array $arr, string $path): mixed
    {
        if ($path === '' || $path === '.') return $arr;
        $parts = explode('.', $path);
        $cur = $arr;
        foreach ($parts as $p) {
            if (is_array($cur) && array_key_exists($p, $cur)) {
                $cur = $cur[$p];
            } else {
                return null;
            }
        }
        return $cur;
    }

    private static function isEmpty(mixed $v): bool
    {
        if ($v === null) return true;
        if (is_string($v)) return trim($v) === '';
        if (is_array($v)) return count($v) === 0;
        return false;
    }

    private static function isType(mixed $v, string $type): bool
    {
        return match ($type) {
            'string' => is_string($v),
            'int','integer' => is_int($v),
            'number','float','double' => is_int($v) || is_float($v),
            'bool','boolean' => is_bool($v),
            'array' => is_array($v),
            'object' => is_array($v), // representamos objetos JSON como arrays asociativos
            'null' => $v === null,
            default => false,
        };
    }

    private static function len(mixed $v): int
    {
        if (is_string($v)) return mb_strlen($v);
        if (is_array($v))  return count($v);
        return 0;
    }
}
