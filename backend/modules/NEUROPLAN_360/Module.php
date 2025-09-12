<?php
declare(strict_types=1);

namespace Modules\NEUROPLAN_360;

use Modules\ModuleInterface;
use Services\DB;
use Services\Migrator;

final class Module implements ModuleInterface
{
    public function run(array $payload, array $authUser = []): array
    {
        $schema = 'NEUROPLAN_360';

        // Migraciones del módulo (idempotente)
        Migrator::apply($schema);

        $email = $authUser['email'] ?? ($payload['usuarioEmail'] ?? 'anon@local');
        $pdo   = DB::conn();

        $action = (string)($payload['action'] ?? 'direct');

        if ($action === 'start') {
            $sid = $this->createSession($pdo, $schema, $email);
            return ['ok'=>true, 'session_id'=>$sid, 'next_step'=>1];
        }

        if ($action === 'step') {
            $sid   = (string)($payload['session_id'] ?? '');
            $step  = (int)($payload['step'] ?? 0);
            $input = (array)($payload['input'] ?? []);
            if ($sid === '' || $step < 1) { http_response_code(422); return ['error'=>'session_id y step requeridos']; }

            if (class_exists(Validation::class)) {
                Validation::validateStep($step, $input);
            }

            $merged = $this->applyStep($pdo, $schema, $sid, $step, $input);
            $next   = $this->nextStep($step, $merged);
            return ['ok'=>true, 'session_id'=>$sid, 'saved_step'=>$step, 'next_step'=>$next];
        }

        if ($action === 'generate') {
            $sid = (string)($payload['session_id'] ?? '');
            if ($sid === '') { http_response_code(422); return ['error'=>'session_id requerido']; }
            $data = $this->getSessionData($pdo, $schema, $sid);
            $plan = Planner::build($data);
            $pid  = $this->savePlan($pdo, $schema, $email, $data, $plan);
            return ['ok'=>true, 'plan_id'=>$pid, 'plan'=>$plan];
        }

        // Modo directo (sin wizard)
        if (class_exists(Validation::class)) {
            Validation::validateDirect($payload);
        }
        $plan = Planner::build($payload);
        $pid  = $this->savePlan($pdo, $schema, $email, $payload, $plan);
        return ['ok'=>true, 'plan_id'=>$pid, 'plan'=>$plan];
    }

    /* ---------- helpers de sesión/plan ---------- */

    private function createSession(\PDO $pdo, string $schema, string $email): string
    {
        $st=$pdo->prepare("INSERT INTO {$schema}.sessions(email) VALUES(:e) RETURNING id");
        $st->execute([':e'=>$email]);
        return (string)$st->fetchColumn();
    }

    private function getSessionRow(\PDO $pdo, string $schema, string $sid): ?array
    {
        $st=$pdo->prepare("SELECT id, step, data FROM {$schema}.sessions WHERE id=:id");
        $st->execute([':id'=>$sid]);
        $r=$st->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    private function getSessionData(\PDO $pdo, string $schema, string $sid): array
    {
        $r = $this->getSessionRow($pdo, $schema, $sid);
        if (!$r) throw new \RuntimeException('session not found');
        return is_array($r['data']) ? $r['data'] : (json_decode((string)$r['data'], true) ?? []);
    }

    private function applyStep(\PDO $pdo, string $schema, string $sid, int $step, array $input): array
    {
        $row = $this->getSessionRow($pdo, $schema, $sid);
        if (!$row) { http_response_code(404); throw new \RuntimeException('session not found'); }
        $data = is_array($row['data']) ? $row['data'] : (json_decode((string)$row['data'], true) ?? []);

        $data['__steps'] = array_values(array_unique(array_merge($data['__steps'] ?? [], [$step])));

        switch ($step) {
          case 1: $data['usuario'] = (string)($input['tipoUsuario'] ?? $input['usuario'] ?? ''); break;
          case 2: $data['neurodiversidades'] = (array)($input['neurodiversidades'] ?? []); break;
          case 3: $data['opcionMenu'] = (string)($input['opcionMenu'] ?? ''); break;
          case 35:
            $data['contexto'] = [
              'grado'    => $input['grado']     ?? $input['contexto']['grado']     ?? null,
              'contenido'=> $input['contenido'] ?? $input['contexto']['contenido'] ?? null,
              'tema'     => $input['tema']      ?? $input['contexto']['tema']      ?? null,
              'sesiones' => (int)($input['sesiones'] ?? $input['contexto']['sesiones'] ?? 0),
              'duracion' => (int)($input['duracion'] ?? $input['contexto']['duracion'] ?? 0),
            ];
            break;
          case 4: $data['sensibilidades'] = (array)($input['sensibilidades'] ?? []); break;
          case 5:
            $data['entornos']     = (array)($input['entornos'] ?? []);
            $data['limitaciones'] = (array)($input['limitaciones'] ?? []);
            $data['prioridad']    = (string)($input['prioridad'] ?? '');
            break;
          case 6: $data['formato'] = (string)($input['formato'] ?? ''); break;
          default: throw new \InvalidArgumentException('step no soportado');
        }

        $st=$pdo->prepare("UPDATE {$schema}.sessions SET data=:d, step=:s, updated_at=now() WHERE id=:id");
        $st->execute([':d'=>json_encode($data, JSON_UNESCAPED_UNICODE), ':s'=>$step, ':id'=>$sid]);

        return $data;
    }

    private function nextStep(int $step, array $data): int
    {
        if ($step === 3 && (($data['usuario'] ?? '')==='docente') && (($data['opcionMenu'] ?? '')==='crear')) return 35;
        return match ($step) { 1=>2, 2=>3, 35=>4, 3=>4, 4=>5, 5=>6, 6=>7, default=>7 };
    }

    private function savePlan(\PDO $pdo, string $schema, string $email, array $input, array $plan): string
    {
        $st=$pdo->prepare("INSERT INTO {$schema}.plans(email,input,plan) VALUES(:e,:i,:p) RETURNING id");
        $st->execute([
          ':e'=>$email,
          ':i'=>json_encode($input, JSON_UNESCAPED_UNICODE),
          ':p'=>json_encode($plan, JSON_UNESCAPED_UNICODE),
        ]);
        return (string)$st->fetchColumn();
    }
}
