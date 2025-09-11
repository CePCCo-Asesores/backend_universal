<?php
declare(strict_types=1);

/**
 * Front Controller – CEPCCO Backend
 * - Lee routes.yaml
 * - Despacha controller/action
 * - Valida contratos para acciones activar*
 * - Responde JSON con códigos HTTP adecuados
 */

$env = getenv('ENV') ?: 'development';
if ($env === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

$root = __DIR__;

// Composer autoload (symfony/yaml y PSR-4)
$autoload = $root . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Requiere directos por robustez (puedes omitir si te quedas solo con PSR-4)
@require_once $root . '/controllers/AuthController.php';
@require_once $root . '/controllers/ModuleController.php';
@require_once $root . '/controllers/SystemController.php';
@require_once $root . '/services/GoogleAuthService.php';
@require_once $root . '/services/JwtService.php';
@require_once $root . '/services/DB.php';
@require_once $root . '/middleware/AuthMiddleware.php';
@require_once $root . '/utils/ContractValidator.php';
@require_once $root . '/config/jwt_secret.php';

// ---------- Helpers ----------
function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Resuelve nombre de clase de controlador considerando namespaces.
 */
function resolve_controller_class(string $name): ?string {
    $namespaced = "\\Controllers\\{$name}";
    if (class_exists($namespaced)) return $namespaced;
    $global = "\\{$name}";
    if (class_exists($global)) return $global;
    return null;
}

/**
 * Construye input desde JSON, form-data y query.
 */
function build_input(): array {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $input = [];

    if (!empty($_GET)) $input = array_merge($input, $_GET);

    if (in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $raw = file_get_contents('php://input') ?: '';

        // JSON body
        if ($raw !== '' && stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = array_merge($input, $decoded);
            }
        }

        // x-www-form-urlencoded / multipart
        if (!empty($_POST)) {
            $input = array_merge($input, $_POST);
        }
    }

    return $input;
}

// ---------- Cargar rutas ----------
use Symfony\Component\Yaml\Yaml;

$routesFile = $root . '/routes.yaml';
if (!file_exists($routesFile)) {
    json_response(['error' => 'Archivo de rutas no encontrado', 'file' => 'routes.yaml'], 500);
}

try {
    $routes = Yaml::parseFile($routesFile);
    if (!is_array($routes)) {
        json_response(['error' => 'routes.yaml inválido'], 500);
    }
} catch (Throwable $e) {
    json_response(['error' => 'Error al leer routes.yaml', 'detail' => $e->getMessage()], 500);
}

// ---------- Resolver ruta ----------
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$route = $routes[$path] ?? null;

if ($route === null || !is_array($route)) {
    json_response(['error' => 'Ruta no encontrada', 'path' => $path], 404);
}

$controllerName = $route['controller'] ?? null;
$actionName     = $route['action'] ?? null;

if (!$controllerName || !$actionName) {
    json_response(['error' => 'Ruta mal definida', 'route' => $route], 500);
}

// ---------- Instanciar controlador ----------
$class = resolve_controller_class($controllerName);
if ($class === null) {
    json_response(['error' => 'Controlador no encontrado', 'controller' => $controllerName], 500);
}

try {
    $controller = new $class();
} catch (Throwable $e) {
    json_response(['error' => 'No se pudo instanciar el controlador', 'controller' => $class, 'detail' => $e->getMessage()], 500);
}

// ---------- Input ----------
$input = build_input();

// ---------- Validación por contrato (acciones activar*) ----------
$needsContract = strncasecmp($actionName, 'activar', 7) === 0;
if ($needsContract) {
    $map = [
        'activarAdia'        => 'ADIA_V1',
        'activarFabricador'  => 'FABRICADOR_V1',
    ];
    $module = $map[$actionName] ?? null;

    if ($module) {
        try {
            $res = \Utils\ContractValidator::validate($module, $input);
            if (isset($res['error'])) {
                json_response(['error' => 'Violación de contrato', 'detalle' => $res['error']], 400);
            }
        } catch (Throwable $e) {
            json_response(['error' => 'Error validando contrato', 'module' => $module, 'detail' => $e->getMessage()], 500);
        }
    }
}

// ---------- Ejecutar acción ----------
if (!method_exists($controller, $actionName)) {
    json_response(['error' => 'Acción no encontrada en el controlador', 'controller' => $class, 'action' => $actionName], 500);
}

try {
    $result = $controller->{$actionName}($input);
} catch (Throwable $e) {
    json_response(['error' => 'Excepción durante ejecución', 'detail' => $e->getMessage()], 500);
}

// ---------- Normalizar respuesta ----------
if ($result === null) {
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    exit;
}

if (is_array($result)) {
    json_response($result, 200);
}
if (is_scalar($result)) {
    json_response(['result' => $result], 200);
}

json_response(['result' => $result], 200);
