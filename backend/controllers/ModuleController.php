<?php
declare(strict_types=1);

use Services\JwtService;
use Services\SecurityValidator;
use Services\ContractValidator;
use Services\Logger;
use Services\EventBus;
use Events\ModuleActivated;
use Exceptions\ContractException;

final class ModuleController
{
    public function activate(): array
    {
        $t0 = microtime(true);
        $moduleName = '';
        $action = '';

        try {
            $input = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
            $moduleName = (string)($input['module'] ?? '');
            $payloadRaw = (array)($input['payload'] ?? []);

            if ($moduleName === '') {
                http_response_code(400);
                return ['error' => 'module requerido'];
            }

            // acción (si viene dentro del payload)
            $action = (string)($payloadRaw['action'] ?? '');

            // Seguridad: rate-limit + sanitización (+JWT opcional)
            $sec = SecurityValidator::validate($moduleName, $action ?: 'direct', $payloadRaw);
            $payload = $sec['sanitized'];
            $auth    = $sec['jwt'] ?? [];

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

            $ms = (int)round((microtime(true) - $t0) * 1000);
            Logger::info('Module activated', [
                'module' => $moduleName,
                'action' => $action,
                'ms'     => $ms,
                'ok'     => true,
            ]);
            EventBus::publish(ModuleActivated::NAME, ModuleActivated::payload($moduleName, $action, $auth, $ms, true));

            http_response_code(200);
            return is_array($result) ? $result : ['ok' => true, 'data' => $result];

        } catch (ContractException $ce) {
            $ms = (int)round((microtime(true) - $t0) * 1000);
            Logger::error('Contract violation', [
                'module' => $moduleName, 'action' => $action, 'ms'=>$ms,
                'err' => $ce->getMessage(), 'ctx' => method_exists($ce, 'getContext') ? $ce->getContext() : null
            ]);
            EventBus::publish(ModuleActivated::NAME, ModuleActivated::payload($moduleName, $action, [], $ms, false));
            http_response_code(422);
            return [
                'error' => 'contract_violation',
                'message' => $ce->getMessage(),
                'context' => method_exists($ce, 'getContext') ? $ce->getContext() : [],
            ];
        } catch (\Throwable $e) {
            $ms = (int)round((microtime(true) - $t0) * 1000);
            Logger::error('Module error', [
                'module' => $moduleName, 'action' => $action, 'ms'=>$ms, 'err'=>$e->getMessage()
            ]);
            EventBus::publish(ModuleActivated::NAME, ModuleActivated::payload($moduleName, $action, [], $ms, false));
            http_response_code(500);
            return [
                'error' => 'internal_error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function registerUser(): array
    {
        $t0 = microtime(true);
        $moduleName = '';
        try {
            $auth = Services\JwtService::verifyFromRequest();
            if (!$auth) { http_response_code(401); return ['error'=>'unauthorized']; }

            $input = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
            $moduleName = (string)($input['module'] ?? '');
            $payloadRaw = (array)($input['payload'] ?? []);
            if ($moduleName === '') { http_response_code(400); return ['error' => 'module requerido']; }

            // Seguridad básica (rate-limit por operación registerUser)
            $sec = SecurityValidator::validate($moduleName, 'registerUser', $payloadRaw);
            $payload = $sec['sanitized'];

            // PRE contract específico si quieres distinguir por _op
            ContractValidator::validatePre($moduleName, ['_op'=>'registerUser'] + $payload);

            $module = \Modules\ModuleRegistry::resolve($moduleName);
            if (!$module || !method_exists($module, 'registerUser')) {
                http_response_code(404);
                return ['error' => 'module not found or registerUser not implemented'];
            }

            /** @var array $result */
            $result = $module->registerUser($auth, $payload);

            ContractValidator::validatePost($moduleName, ['_op'=>'registerUser'] + (is_array($result) ? $result : []));

            $ms = (int)round((microtime(true) - $t0) * 1000);
            Logger::info('User registered to module', ['module'=>$moduleName, 'ms'=>$ms, 'ok'=>true]);

            http_response_code(200);
            return is_array($result) ? $result : ['ok'=>true];

        } catch (\Throwable $e) {
            $ms = (int)round((microtime(true) - $t0) * 1000);
            Services\Logger::error('registerUser error', ['module'=>$moduleName, 'ms'=>$ms, 'err'=>$e->getMessage()]);
            http_response_code(500);
            return ['error'=>'internal_error', 'message'=>$e->getMessage()];
        }
    }
}
