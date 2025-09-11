<?php
declare(strict_types=1);

namespace Modules\NEUROPLAN_360;

use Modules\ModuleInterface;
use Services\DB;

final class Module implements ModuleInterface
{
    /** Ejecuta NEUROPLAN_360 con el payload validado por el contrato YAML */
    public function run(array $payload, array $authUser = []): array
    {
        $email  = $authUser['email'] ?? ($payload['usuarioEmail'] ?? 'anon@local');
        $schema = 'NEUROPLAN_360';

        $pdo = DB::conn();

        // Asegura el esquema y tabla de planes del módulo
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS {$schema}");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$schema}.plans (
              id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              email TEXT NOT NULL,
              input JSONB NOT NULL,
              plan  JSONB NOT NULL,
              status TEXT NOT NULL DEFAULT 'generated',
              created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        ");

        // Construye el plan (stub determinista; aquí podrías llamar a lógica avanzada/IA si quieres)
        $plan = self::buildPlan($payload);

        // Persiste
        $stmt = $pdo->prepare("INSERT INTO {$schema}.plans (email, input, plan) VALUES (:e, :i, :p) RETURNING id");
        $stmt->execute([
            ':e' => $email,
            ':i' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ':p' => json_encode($plan,    JSON_UNESCAPED_UNICODE),
        ]);
        $id = $stmt->fetchColumn();

        return [
            'ok'       => true,
            'module'   => $schema,
            'plan_id'  => $id,
            'email'    => $email,
            'plan'     => $plan,
        ];
    }

    /** Crea un plan base a partir del payload (acepta nombres antiguos y nuevos de campos) */
    private static function buildPlan(array $p): array
    {
        // Soporta dos formas de payload: la "plana" y la anidada en 'contexto'
        $usuario  = $p['usuario'] ?? $p['tipoUsuario'] ?? 'desconocido';
        $formato  = $p['formato'] ?? $p['formatoPreferido'] ?? 'practico';
        $nds      = $p['neurodiversidades'] ?? ($p['nds'] ?? []);
        $ctx      = $p['contexto'] ?? [
            'grado'    => $p['grado'] ?? null,
            'contenido'=> $p['contenidoTematico'] ?? null,
            'tema'     => $p['temaDetonador'] ?? null,
            'sesiones' => $p['numeroSesiones'] ?? null,
            'duracion' => $p['duracionSesion'] ?? null,
        ];
        $ajustes  = [
            'sensorial'    => $p['sensibilidades'] ?? ($p['sensibilidadesSensoriales'] ?? []),
            'entornos'     => $p['entornos'] ?? [],
            'limitaciones' => $p['limitaciones'] ?? [],
            'prioridad'    => $p['prioridad'] ?? ($p['prioridadUrgente'] ?? null),
        ];

        return [
            'titulo'            => 'NeuroPlan 360',
            'usuario'           => $usuario,
            'neurodiversidades' => $nds,
            'formato'           => $formato,
            'contexto'          => $ctx,
            'ajustes'           => $ajustes,
            'implementacion'    => [
                'objetivo'     => 'Objetivo ND adaptado al contexto y sensibilidades.',
                'materiales'   => [
                    'Apoyos visuales (pictogramas/códigos de color)',
                    'Herramientas sensoriales (ruido/luz/ textura)',
                    'Instrucciones paso a paso',
                ],
                'pasos'        => [
                    'Preparar entorno y apoyos',
                    'Explicar con multimodalidad (visual/auditiva/kinestésica)',
                    'Permitir pausas y autorregulación',
                ],
                'evaluacion'   => 'Checklist breve + observación formativa.',
                'tiempo'       => $ctx['duracion'] ? "{$ctx['duracion']} min por sesión" : 'Flexible (con pausas)',
            ],
        ];
    }
}
