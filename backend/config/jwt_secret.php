<?php
declare(strict_types=1);

// Devuelve el secreto o corta con 500 si no está configurado (producción segura).
$secret = getenv('JWT_SECRET') ?: '';
if ($secret === '') {
    http_response_code(500);
    exit('JWT_SECRET no configurado');
}
return $secret;
