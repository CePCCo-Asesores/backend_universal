<?php
declare(strict_types=1);

namespace Controllers;

use Services\GoogleAuthService;
use Services\JwtService;

class AuthController
{
    public function loginConGoogle(array $input): array
    {
        $tokenGoogle = $input['token_google'] ?? null;
        if (!$tokenGoogle || !is_string($tokenGoogle)) {
            http_response_code(400);
            return ['error' => 'token_google es requerido'];
        }

        $perfil = GoogleAuthService::validate($tokenGoogle);
        if ($perfil === null) {
            http_response_code(401);
            return ['error' => 'Token de Google inválido'];
        }

        $jwt = JwtService::generate([
            'sub'   => $perfil['sub'],
            'email' => $perfil['email']
        ], 3600);

        return [
            'autenticado' => true,
            'usuario'     => $perfil['email'],
            'jwt'         => $jwt
        ];
    }
}
