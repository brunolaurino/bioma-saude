<?php

namespace App\Console\Commands;

use App\Domain\Export;
use App\Models\Invitation;
use App\Security\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Limpeza diária: exportações vencidas, convites e links antigos, notificações lidas há muito tempo.
 * Não apaga prontuário: ele é guardado por 20 anos (Lei 13.787/2018) mesmo depois do encerramento.
 */
class BiomaRetencao extends Command
{
    protected $signature = 'bioma:retencao';

    protected $description = 'Apaga exportações vencidas, convites expirados e notificações antigas';

    public function handle(): int
    {
        $exports = Export::purgeExpired();
        $invites = Invitation::where('expires_at', '<', now()->subDays(30))->delete();
        $tokens = DB::table('setup_tokens')->where('expires_at', '<', now()->subDays(30))->delete();
        $notes = DB::table('notifications')->whereNotNull('read_at')->where('read_at', '<', now()->subDays(90))->delete();

        Audit::record('sistema', 'retention.run', 'retention', null, compact('exports', 'invites', 'tokens', 'notes'));
        $this->info("Exportações: $exports · convites: $invites · links: $tokens · notificações: $notes");

        return self::SUCCESS;
    }
}
