<?php
declare(strict_types=1);

namespace Controllers;

use Services\DB;
use Services\JwtService;
use Utils\ContractValidator;

class ModuleController
{
    // POST /module/activate  {"module":"ADIA_V1","payload":{...}}
    public function activate(array $input): array
    {
        $module = trim((string)($input['module'] ?? ''));
        $payload = $input['payload'] ?? [];

        if ($module === '') {
            http_response_code(422);
            return ['error' => 'Falta parámetro requerido: module'];
        }

        // Valida contrato si existe un YAML homónimo
        try {
            ContractValidator::validate($module, $payload);
        } catch (\Throwable $e) {
            http_response_code(400);
            return ['error' => 'Contrato inválido', 'detail' => $e->getMessage()];
        }

        // Aquí enrutarías a la lógica particular del módulo (use-cases/handlers)
        // Para mantener el backend universal, lo dejamos como “eco” validado:
        return [
            'ok'      => true,
            'module'  => $module,
            'payload' => $payload,
            'message' => 'Módulo validado/activado correctamente'
        ];
    }

    // POST /module/registerUser  {"module":"ADIA_V1","email":"x@y.z","name":"..."}
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

        // Asegura que el esquema del módulo exista
        $schema = self::schemaName($module);
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS {$schema}");

        // Crea tabla de usuarios si no existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$schema}.users (
              id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              email TEXT UNIQUE NOT NULL,
              name  TEXT,
              created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        ");

        // Inserta/ignora
        $stmt = $pdo->prepare("INSERT INTO {$schema}.users (email, name)
                               VALUES (:email, NULLIF(:name, ''))
                               ON CONFLICT (email) DO UPDATE SET name = EXCLUDED.name");
        $stmt->execute([':email' => $email, ':name' => $name]);

        return ['ok' => true, 'module' => $module, 'email' => $email];
    }

    private static function schemaName(string $module): string
    {
        // Esquema seguro: letras/números/guion bajo
        $base = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $module));
        return $base; // p.ej. ADIA_V1, FABRICADOR_V1
    }
}
