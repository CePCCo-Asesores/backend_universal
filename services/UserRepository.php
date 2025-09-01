<?php
class UserRepository {
    private $usuarios = [];

    public function registrarUsuario($email, $password = null, $google_id = null, $name = null) {
        if (isset($this->usuarios[$email])) return false;

        $this->usuarios[$email] = [
            'email' => $email,
            'password' => $password,
            'google_id' => $google_id,
            'name' => $name ?? 'SinNombre'
        ];
        return true;
    }

    public function obtenerUsuarioPorEmail($email) {
        return $this->usuarios[$email] ?? null;
    }

    public function obtenerUsuarioPorGoogleId($google_id) {
        foreach ($this->usuarios as $usuario) {
            if ($usuario['google_id'] === $google_id) return $usuario;
        }
        return null;
    }
}
