<?php
declare(strict_types=1);

namespace Modules\NEUROPLAN_360;

final class Planner
{
    /** Construye el plan a partir del payload (modo directo o datos de sesión). */
    public static function build(array $p): array
    {
        // Acepta nombres “planos” o dentro de contexto
        $usuario = $p['usuario'] ?? $p['tipoUsuario'] ?? 'desconocido';
        $nds     = $p['neurodiversidades'] ?? ($p['nds'] ?? []);
        $formato = $p['formato'] ?? $p['formatoPreferido'] ?? 'practico';
        $ctx = $p['contexto'] ?? [
            'grado'    => $p['grado'] ?? null,
            'contenido'=> $p['contenidoTematico'] ?? null,
            'tema'     => $p['temaDetonador'] ?? null,
            'sesiones' => $p['numeroSesiones'] ?? null,
            'duracion' => $p['duracionSesion'] ?? null,
        ];
        $ajustes = [
            'sensibilidades' => $p['sensibilidades'] ?? ($p['sensibilidadesSensoriales'] ?? []),
            'entornos'       => $p['entornos'] ?? [],
            'limitaciones'   => $p['limitaciones'] ?? [],
            'prioridad'      => $p['prioridad'] ?? ($p['prioridadUrgente'] ?? null),
        ];

        return [
            'titulo'            => 'NeuroPlan 360',
            'usuario'           => $usuario,
            'neurodiversidades' => $nds,
            'formato'           => $formato,
            'contexto'          => $ctx,
            'ajustes'           => $ajustes,
            'implementacion'    => [
                'objetivo'   => 'Objetivo ND adaptado al contexto y sensibilidades.',
                'materiales' => [
                    'Apoyos visuales (pictogramas / color)',
                    'Herramientas sensoriales (ruido/luz/textura)',
                    'Instrucciones paso a paso',
                ],
                'pasos'      => ['Preparar entorno', 'Explicar multimodal', 'Pausas de autorregulación'],
                'evaluacion' => 'Checklist breve + observación formativa',
                'tiempo'     => $ctx['duracion'] ? "{$ctx['duracion']} min por sesión" : 'Flexible (con pausas)',
            ],
        ];
    }
}
