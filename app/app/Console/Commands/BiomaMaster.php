<?php

namespace App\Console\Commands;

use App\Models\Professional;
use App\Security\Audit;
use App\Security\BlindIndex;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Gera o link de uso único para criar a conta master do ambiente (vale 24 horas). */
class BiomaMaster extends Command
{
    protected $signature = 'bioma:master';

    protected $description = 'Gera o link de primeira configuração da conta master';

    public function handle(): int
    {
        if (Professional::where('role', 'master')->exists()) {
            $this->error('Este ambiente já tem um master. Para trocar, o master atual transfere em Configurações.');

            return self::FAILURE;
        }

        $token = Str::random(40);
        DB::table('setup_tokens')->where('purpose', 'master')->whereNull('used_at')->update(['expires_at' => now()]);
        DB::table('setup_tokens')->insert([
            'token_hash' => BlindIndex::token($token),
            'purpose' => 'master',
            'expires_at' => now()->addDay(),
            'created_at' => now(),
        ]);
        Audit::record('sistema', 'setup.master_link', 'setup:master');

        $this->info('Link para criar a conta master (vale 24 horas, uso único):');
        $this->line(route('setup', $token));

        return self::SUCCESS;
    }
}
