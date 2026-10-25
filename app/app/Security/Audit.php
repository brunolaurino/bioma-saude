<?php

namespace App\Security;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Audit
{
    private const GENESIS = '0000000000000000000000000000000000000000000000000000000000000000';

    private const FIELDS = ['occurred_at', 'actor', 'actor_id', 'action', 'resource', 'patient_id', 'ip', 'details', 'prev_hash'];

    /** Grava um evento em nome de quem está logado. Nunca recebe conteúdo clínico, só o que foi acessado ou mudado. */
    public static function log(string $action, string $resource, ?string $patientId = null, array $details = []): void
    {
        $user = Auth::user();
        self::record($user ? $user->auditName() : 'sistema', $action, $resource, $patientId, $details, $user?->id);
    }

    public static function record(string $actor, string $action, string $resource, ?string $patientId = null, array $details = [], ?string $actorId = null): void
    {
        $ip = app()->runningInConsole() ? null : request()->ip();

        DB::transaction(function () use ($actor, $actorId, $action, $resource, $patientId, $details, $ip) {
            $prev = DB::table('audit_log')->orderByDesc('id')->lockForUpdate()->value('hash') ?? self::GENESIS;
            $row = [
                'occurred_at' => now()->format('Y-m-d H:i:s.u'),
                'actor' => mb_substr($actor, 0, 120),
                'actor_id' => $actorId,
                'action' => $action,
                'resource' => $resource,
                'patient_id' => $patientId,
                'ip' => $ip,
                'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                'prev_hash' => $prev,
            ];
            $row['hash'] = self::hash($row);

            DB::table('audit_log')->insert($row);
        });
    }

    /** Recalcula a corrente inteira. Devolve o id da primeira linha adulterada, ou null se estiver íntegra. */
    public static function verify(): ?int
    {
        $prev = self::GENESIS;

        foreach (DB::table('audit_log')->orderBy('id')->cursor() as $row) {
            $data = (array) $row;
            if ($data['prev_hash'] !== $prev || ! hash_equals($data['hash'], self::hash($data))) {
                return (int) $data['id'];
            }
            $prev = $data['hash'];
        }

        return null;
    }

    private static function hash(array $row): string
    {
        return hash('sha256', implode("\x1f", array_map(fn ($f) => (string) ($row[$f] ?? ''), self::FIELDS)));
    }
}
