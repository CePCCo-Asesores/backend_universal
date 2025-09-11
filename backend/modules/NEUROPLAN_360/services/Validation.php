<?php
declare(strict_types=1);

namespace Modules\NEUROPLAN_360;

final class Validation
{
    public const TIPOS_USUARIO = ['docente','terapeuta','padre','medico','otro','mixto'];
    public const OPCIONES_MENU = ['adaptar','crear','revisar','consultar','evaluar','universal'];
    public const FORMATOS      = ['practico','completo','nd_plus','sensorial','semaforo'];

    /**
     * Valida el input de un paso específico del wizard.
     * Lanza \InvalidArgumentException si algo no cumple.
     */
    public static function validateStep(int $step, array $input): void
    {
        switch ($step) {
            case 1: // tipoUsuario
                self::assertEnum($input, ['tipoUsuario','usuario'], self::TIPOS_USUARIO, 'tipoUsuario');
                break;

            case 2: // neurodiversidades
                self::assertArrayOfStrings($input, 'neurodiversidades', minItems: 1);
                break;

            case 3: // opcionMenu
                self::assertEnum($input, ['opcionMenu'], self::OPCIONES_MENU, 'opcionMenu');
                break;

            case 35: // contexto (docentes que crean)
                $ctx = $input['contexto'] ?? $input;
                self::assertString($ctx, 'grado');
                self::assertString($ctx, 'contenido');
                self::assertString($ctx, 'tema');
                self::assertInt($ctx, 'sesiones', min: 1);
                self::assertInt($ctx, 'duracion', min: 1);
                break;

            case 4: // sensibilidades (opcional)
                if (array_key_exists('sensibilidades', $input)) {
                    self::assertArrayOfStrings($input, 'sensibilidades');
                }
                break;

            case 5: // personalización
                if (array_key_exists('entornos', $input)) {
                    self::assertArrayOfStrings($input, 'entornos');
                }
                if (array_key_exists('limitaciones', $input)) {
                    self::assertArrayOfStrings($input, 'limitaciones');
                }
                if (array_key_exists('prioridad', $input)) {
                    self::assertString($input, 'prioridad', maxLen: 1000);
                }
                break;

            case 6: // formato
                self::assertEnum($input, ['formato'], self::FORMATOS, 'formato');
                break;

            default:
                throw new \InvalidArgumentException('step no soportado');
        }
    }

    /**
     * Valida el payload “directo” (sin wizard) según el contrato.
     * Lanza \InvalidArgumentException si algo no cumple.
     */
    public static function validateDirect(array $payload): void
    {
        self::assertEnum($payload, ['usuario','tipoUsuario'], self::TIPOS_USUARIO, 'usuario');
        self::assertArrayOfStrings($payload, 'neurodiversidades', minItems: 1);
        self::assertEnum($payload, ['formato','formatoPreferido'], self::FORMATOS, 'formato');

        // contexto puede venir anidado o plano
        $ctx = $payload['contexto'] ?? [
            'grado'     => $payload['grado'] ?? null,
            'contenido' => $payload['contenidoTematico'] ?? null,
            'tema'      => $payload['temaDetonador'] ?? null,
            'sesiones'  => $payload['numeroSesiones'] ?? null,
            'duracion'  => $payload['duracionSesion'] ?? null,
        ];
        self::assertString($ctx, 'grado');
        self::assertString($ctx, 'contenido');
        self::assertString($ctx, 'tema');
        self::assertInt($ctx, 'sesiones', min: 1);
        self::assertInt($ctx, 'duracion', min: 1);

        // campos opcionales
        if (isset($payload['sensibilidades']) || isset($payload['sensibilidadesSensoriales'])) {
            $arr = $payload['sensibilidades'] ?? $payload['sensibilidadesSensoriales'];
            self::assertArrayOfStrings(['sensibilidades' => $arr], 'sensibilidades');
        }
        if (isset($payload['entornos'])) {
            self::assertArrayOfStrings($payload, 'entornos');
        }
        if (isset($payload['limitaciones'])) {
            self::assertArrayOfStrings($payload, 'limitaciones');
        }
        if (isset($payload['prioridad']) || isset($payload['prioridadUrgente'])) {
            $val = $payload['prioridad'] ?? $payload['prioridadUrgente'];
            self::assertString(['prioridad' => $val], 'prioridad', maxLen: 1000);
        }
    }

    /* ================= Helpers ================= */

    private static function assertEnum(array $data, array $keys, array $allowed, string $label): void
    {
        $val = null;
        foreach ($keys as $k) {
            if (isset($data[$k]) && $data[$k] !== '') { $val = $data[$k]; break; }
        }
        if (!is_string($val)) {
            throw new \InvalidArgumentException("$label requerido");
        }
        if (!in_array($val, $allowed, true)) {
            $al = implode(', ', $allowed);
            throw new \InvalidArgumentException("$label inválido (permitidos: $al)");
        }
    }

    private static function assertArrayOfStrings(array $data, string $key, int $minItems = 0, int $maxItems = 0): void
    {
        if (!array_key_exists($key, $data)) {
            if ($minItems > 0) {
                throw new \InvalidArgumentException("$key requerido");
            }
            return;
        }
        $arr = $data[$key];
        if (!is_array($arr)) {
            throw new \InvalidArgumentException("$key debe ser arreglo");
        }
        if ($minItems > 0 && count($arr) < $minItems) {
            throw new \InvalidArgumentException("$key requiere al menos $minItems elementos");
        }
        foreach ($arr as $i => $v) {
            if (!is_string($v)) {
                throw new \InvalidArgumentException("$key[$i] debe ser string");
            }
            if (trim($v) === '') {
                throw new \InvalidArgumentException("$key[$i] no puede estar vacío");
            }
        }
        if ($maxItems > 0 && count($arr) > $maxItems) {
            throw new \InvalidArgumentException("$key admite máximo $maxItems elementos");
        }
    }

    private static function assertString(array $data, string $key, int $minLen = 1, int $maxLen = 0): void
    {
        if (!array_key_exists($key, $data)) {
            throw new \InvalidArgumentException("$key requerido");
        }
        $val = $data[$key];
        if (!is_string($val)) {
            throw new \InvalidArgumentException("$key debe ser string");
        }
        $len = mb_strlen(trim($val));
        if ($len < $minLen) {
            throw new \InvalidArgumentException("$key debe tener al menos $minLen caracteres");
        }
        if ($maxLen > 0 && $len > $maxLen) {
            throw new \InvalidArgumentException("$key excede longitud máxima ($maxLen)");
        }
    }

    private static function assertInt(array $data, string $key, ?int $min = null, ?int $max = null): void
    {
        if (!array_key_exists($key, $data)) {
            throw new \InvalidArgumentException("$key requerido");
        }
        $val = $data[$key];
        if (!is_int($val)) {
            if (is_string($val) && preg_match('/^-?\d+$/', $val)) {
                $val = (int)$val;
            } else {
                throw new \InvalidArgumentException("$key debe ser entero");
            }
        }
        if ($min !== null && $val < $min) {
            throw new \InvalidArgumentException("$key debe ser ≥ $min");
        }
        if ($max !== null && $val > $max) {
            throw new \InvalidArgumentException("$key debe ser ≤ $max");
        }
    }
}
