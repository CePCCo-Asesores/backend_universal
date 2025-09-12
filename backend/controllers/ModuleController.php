<?php
declare(strict_types=1);

use Services\JwtService;
use Services\ContractValidator;
use Exceptions\ContractException;

final class ModuleController
{
    public function activate(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
            $moduleName = (string)($input['module'] ?? '');
            $payload    = (array)($input['payload'] ?? []);

            if ($moduleName === '') {
                http_response_code(400);
                return ['error' => 'module requerido'];
            }

            // Auth (opcional según tu flujo)
            $auth = [];
            try { $auth = JwtService::verifyFromRequest(); } catch (\Throwable $e) {}

            // PRE: reglas del contrato
            ContractValidator::validatePre($moduleName, $payload);

            // Resolver módulo
            $module = \Modules\ModuleRegistry::resolve($moduleName);
            if (!$module) {
                http_response_code(404);
                return ['error' => 'module not found'];
            }

            // Ejecutar
            $result = $module->run($payload, $auth);

            // POST: reglas del contrato
            ContractValidator::validatePost($moduleName, is_array($result) ? $result : []);

            http_response_code(200);
            return is_array($result) ? $result : ['ok' => true, 'data' => $result];

        } catch (ContractException $ce) {
            http_response_code(422);
            $ctx = method_exists($ce, 'getContext') ? $ce->getContext() : [];
            return [
                'error' => 'contract_violation',
                'message' => $ce->getMessage(),
                'context' => $ctx,
            ];
        } catch (\Throwable $e) {
            http_response_code(500);
            return [
                'error' => 'internal_error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function registerUser(): array
    {
        // (sin cambios)
        try {
            $auth = Services\JwtService::verifyFromRequest();
            if (!$auth) { http_response_code(401); return ['error'=>'unauthorized']; }

            $input = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
            $moduleName = (string)($input['module'] ?? '');
            $payload    = (array)($input['payload'] ?? []);

            if ($moduleName === '') {
                http_response_code(400);
                return ['error' => 'module requerido'];
            }

            // PRE contrato específico de register si lo defines (opcional)
            ContractValidator::validatePre($moduleName, ['_op' => 'registerUser'] + $payload);

            $module = \Modules\ModuleRegistry::resolve($moduleName);
            if (!$module || !method_exists($module, 'registerUser')) {
                http_response_code(404);
                return ['error' => 'module not found or registerUser not implemented'];
            }

            /** @var array $result */
            $result = $module->registerUser($auth, $payload);

            // POST
            ContractValidator::validatePost($moduleName, ['_op'=>'registerUser'] + (is_array($result) ? $result : []));

            http_response_code(200);
            return is_array($result) ? $result : ['ok'=>true];

        } catch (ContractException $ce) {
            http_response_code(422);
            $ctx = method_exists($ce, 'getContext') ? $ce->getContext() : [];
            return [
                'error' => 'contract_violation',
                'message' => $ce->getMessage(),
                'context' => $ctx,
            ];
        } catch (\Throwable $e) {
            http_response_code(500);
            return [
                'error' => 'internal_error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
