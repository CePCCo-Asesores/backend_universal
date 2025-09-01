<?php
declare(strict_types=1);

namespace Controllers;

class AgentController
{
    public function activarAdia(array $input): array
    {
        if (empty($input['contexto']) || !is_string($input['contexto'])) {
            http_response_code(422);
            return ['error' => 'Falta parámetro requerido: contexto'];
        }

        // Simulación de activación ADIA
        return [
            'modulo'   => 'ADIA_V1',
            'accion'   => 'activar',
            'contexto' => $input['contexto'],
            'resultado'=> 'ADIA activado correctamente'
        ];
    }

    public function activarFabricador(array $input): array
    {
        if (empty($input['blueprint']) || !is_string($input['blueprint'])) {
            http_response_code(422);
            return ['error' => 'Falta parámetro requerido: blueprint'];
        }

        // Simulación de activación FABRICADOR
        return [
            'modulo'    => 'FABRICADOR_V1',
            'accion'    => 'activar',
            'blueprint' => $input['blueprint'],
            'resultado' => 'FABRICADOR activado correctamente'
        ];
    }
}
