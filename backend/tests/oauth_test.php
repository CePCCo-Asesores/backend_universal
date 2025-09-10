<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../services/GoogleAuthService.php';
require_once __DIR__ . '/../services/JwtService.php';

$header  = \Services\JwtService::base64url_encode(json_encode(['alg'=>'RS256','typ'=>'JWT']));
$payload = \Services\JwtService::base64url_encode(json_encode([
    'sub'   => '1234567890',
    'email' => 'user@example.com',
    'aud'   => getenv('GOOGLE_CLIENT_ID') ?: 'dummy-client-id',
    'iss'   => 'https://accounts.google.com',
    'exp'   => time() + 3600
]));
$fakeSig = \Services\JwtService::base64url_encode('signature');
$idToken = "$header.$payload.$fakeSig";

$auth = new \Controllers\AuthController();
$result = $auth->loginConGoogle(['token_google'=>$idToken]);
echo json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
if (!isset($result['jwt'])) exit(1);
