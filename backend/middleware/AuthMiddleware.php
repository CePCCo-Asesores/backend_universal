<?php

class AuthMiddleware {
    private $jwtService;

    public function __construct($jwtService) {
        $this->jwtService = $jwtService;
    }

    public function handle($request, $next) {
        $token = $request->getHeader('Authorization');
        if (!$token) {
            return ['error' => 'Token no proporcionado'];
        }

        $token = str_replace('Bearer ', '', $token);
        $userId = $this->jwtService->validateToken($token);

        if (!$userId) {
            return ['error' => 'Token inválido o expirado'];
        }

        $request->setAttribute('user_id', $userId);
        return $next($request);
    }
}
