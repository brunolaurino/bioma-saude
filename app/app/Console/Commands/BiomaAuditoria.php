<?php

namespace App\Console\Commands;

use App\Security\Audit;
use Illuminate\Console\Command;

class BiomaAuditoria extends Command
{
    protected $signature = 'bioma:auditoria';

    protected $description = 'Confere se a corrente do log de auditoria está íntegra';

    public function handle(): int
    {
        $broken = Audit::verify();

        if ($broken !== null) {
            $this->error("Corrente quebrada na linha $broken do log de auditoria.");

            return self::FAILURE;
        }

        $this->info('Log de auditoria íntegro.');

        return self::SUCCESS;
    }
}
