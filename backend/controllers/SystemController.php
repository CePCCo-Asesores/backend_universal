<?php
declare(strict_types=1);

namespace Controllers;

class SystemController
{
    public function estado(array $input = []): array
    {
        // Respuesta que espera el monitor: {"estado":"activo"}
        return [
            'estado'   => 'activo',
            'entorno'  => getenv('ENV') ?: 'desarrollo',
            'modulos'  => ['ADIA_V1', 'FABRICADOR_V1'],
            'version'  => '1.0.0'
        ];
    }
}
