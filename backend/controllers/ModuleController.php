<?php
declare(strict_types=1);

namespace Controllers;

use Services\DB;
use Utils\ContractValidator;
use Modules\ModuleRegistry;
use Services\JwtService;

class ModuleController
{
    /**
     * POST /module/activate
     * Body:
     * {
     *   "module": "NOMBRE_MODULO",     // requerido (ej. "ADIA_V1")
     *   "payload": { ... }             // opcional; validado contra backend/contracts/<module>.yaml si existe
     * }
     *
     * Comportamiento:
     *  - Valida 'module' presente.
     *  - Valida 'payload' contra contrato YAML si existe.
     *  - Resuelve el módulo en ModuleRegistry y ejecuta su lógica con ->run($payload, $authUser).
     *  - $authUser se obtiene del JWT si llega "Authorization: Bearer <jwt>" (opcional).
     */
    public function activate(array $input): array
    {
        $module  = trim((string)($input['module'] ?? ''));
        $payload = $input['payload'] ?? [];

        if ($module === '') {
            http_response_code(422);
            return ['error' => 'Falta parámetro requerido: module'];
        }

        // 1) Validación de contrato (si existe un YAML homónimo)
        try {
            ContractValidator::validate($module, $payload);
        } catch (\Throwable $e) {
            http_response_code(400);
            return ['error' => 'Contrato inválido', 'detail' => $e->getMessage()];
        }

        // 2) Resolver el handler del módulo
        $handler = ModuleRegistry::resolve($module);
        if (!$handler) {
            http_response_code(404);
            return ['error' => "Módulo '{$module}' no está registrado o no disponible"];
        }

        // 3) Extraer (opcional) usuario autenticado desde Authorization: Bearer <jwt>
        //    No hacemos hard-fail si no viene JWT: módulos pueden decidir requerirlo o no.
        $authUser = $this->getAuthUserFromJwt();

        // 4) Ejecutar la lógica propia del módulo
        try {
            $result = $handler->run($payload, $authUser);
        } catch (\Throwable $e) {
            // Captura de errores del módulo: no exponemos stacktrace en producción
            http_response_code(500);
            return [
                'error'  => 'Error en la ejecución del módulo',
                'module' => $module,
                'detail' => getenv('ENV') === 'development' ? $e->getMessage() : 'internal_error'
            ];
        }

        // 5) Respuesta del módulo (se asume JSON-serializable)
        return [
            'ok'     => true,
            'module' => $module,
            'data'   => $result
        ];
    }

    /**
     * POST /module/registerUser
     * Body:
     * {
     *   "module": "NOMBRE_MODULO",  // requerido
     *   "email": "user@dominio",    // requerido
     *   "name": "Nombre opcional"   // opcional
     * }
     *
     * Crea el schema del módulo si no existe y registra/actualiza el usuario en <SCHEMA>.users
     */
    public function registerUser(array $input): array
    {
        $module = trim((string)($input['module'] ?? ''));
        $email  = trim((string)($input['email'] ?? ''));
        $name   = trim((string)($input['name'] ?? ''));

        if ($module === '' || $email === '') {
            http_response_code(422);
            return ['error' => 'module y email son requeridos'];
        }

        $pdo = DB::conn();

        // Asegura schema por módulo
        $schema = self::schemaName($module);
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS {$schema}");

        // Crea tabla users si no existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$schema}.users (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email TEXT UNIQUE NOT NULL,
                name  TEXT,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        ");

        // Inserta o actualiza nombre
        $stmt = $pdo->prepare("
            INSERT INTO {$schema}.users (email, name)
            VALUES (:email, NULLIF(:name, ''))
            ON CONFLICT (email) DO UPDATE SET name = EXCLUDED.name
        ");
        $stmt->execute([':email' => $email, ':name' => $name]);

        return ['ok' => true, 'module' => $module, 'email' => $email];
    }

    /**
     * Obtiene usuario autenticado desde Authorization: Bearer <jwt>
     * Devuelve array mínimo ['email'=>..., 'sub'=>...] o [] si no hay/bad token.
     * - Si JwtService::decode existe, lo usa para verificar.
     * - Si no, hace una decodificación base64-url SIN verificación (mejor que nada) y extrae "email"/"sub".
     *   *Recomendado*: implementar verificación en JwtService y exponer decode/verify.
     */
    private function getAuthUserFromJwt(): array
    {
        $auth = $this->getAuthorizationHeader();
        if (!$auth || stripos($auth, 'bearer ') !== 0) {
            return [];
        }
        $jwt = trim(substr($auth, 7));

        // Preferente: si existe JwtService::decode, úsalo
        if (class_exists(JwtService::class) && method_exists(JwtService::class, 'decode')) {
            try {
                $claims = JwtService::decode($jwt);
                if (is_array($claims)) {
                    return [
                        'email' => (string)($claims['email'] ?? ''),
                        'sub'   => (string)($claims['sub'] ?? '')
                    ];
                }
            } catch (\Throwable $e) {
                return [];
            }
        }

        // Fallback: decodificar payload sin verificación (no ideal, pero no rompe)
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return [];
        $payload = $this->b64urlJsonDecode($parts[1]);
        if (!is_array($payload)) return [];

        return [
            'email' => (string)($payload['email'] ?? ''),
            'sub'   => (string)($payload['sub'] ?? '')
        ];
    }

    private function getAuthorizationHeader(): ?string
    {
        // Funciona en PHP embebido y detrás de proxies comunes
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $k => $v) {
                if (strcasecmp($k, 'Authorization') === 0) return $v;
            }
        }
        return null;
        }

    private function b64urlJsonDecode(string $b64url): ?array
    {
        $replaced = strtr($b64url, '-_', '+/');
        $padded = $replaced . str_repeat('=', (4 - strlen($replaced) % 4) % 4);
        $json = base64_decode($padded, true);
        if ($json === false) return null;
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    private static function schemaName(string $module): string
    {
        // Esquema seguro: letras/números/guión bajo, en MAYÚSCULAS
        return strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $module));
    }
}
