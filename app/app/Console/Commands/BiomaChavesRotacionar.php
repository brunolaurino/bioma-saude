<?php

namespace App\Console\Commands;

use App\Security\Audit;
use App\Security\KeyVault;
use Illuminate\Console\Command;

class BiomaChavesRotacionar extends Command
{
    protected $signature = 'bioma:chaves-rotacionar';

    protected $description = 'Recifra as chaves dos pacientes com a chave mestra (KEK) mais nova. Os dados não precisam ser recifrados.';

    public function handle(): int
    {
        $n = KeyVault::rotate();
        Audit::record('sistema', 'keys.rotate', 'keyring', null, ['count' => $n]);
        $this->info($n ? "$n chaves recifradas com a KEK v".config('bioma.kek_current').'.' : 'Todas as chaves já usam a KEK atual.');

        return self::SUCCESS;
    }
}
