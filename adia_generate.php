<?php
declare(strict_types=1);

// Autoload de Composer (symfony/yaml)
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Validador
require_once __DIR__ . '/utils/ContractValidator.php';

header('Content-Type: application/json; charset=utf-8');

function read_input(): array {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $input = [];

    if (!empty($_GET)) $input = array_merge($input, $_GET);

    if (in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $raw = file_get_contents('php://input') ?: '';
        if ($raw !== '' && stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = array_merge($input, $decoded);
            }
        }
        if (!empty($_POST)) $input = array_merge($input, $_POST);
    }
    return $input;
}

$input = read_input();

// Validar contra contrato simple ADIA_V1 (requiere "contexto")
try {
    $res = \Utils\ContractValidator::validate('ADIA_V1', $input);
    if (isset($res['error'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Violación de contrato', 'detalle' => $res['error']], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error validando contrato', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Simulación de generación ADIA
if (empty($input['contexto'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Falta parámetro requerido: contexto'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'modulo'   => 'ADIA_V1',
    'accion'   => 'generar',
    'contexto' => $input['contexto'],
    'resultado'=> 'ADIA generado correctamente'
], JSON_UNESCAPED_UNICODE);
