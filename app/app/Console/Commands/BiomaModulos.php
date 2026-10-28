<?php

namespace App\Console\Commands;

use App\Security\Modules;
use Illuminate\Console\Command;

class BiomaModulos extends Command
{
    protected $signature = 'bioma:modulos {acao=listar : listar, ligar ou desligar} {modulo? : chave do módulo, ex.: fotos} {--por=master : quem fez a mudança, para a auditoria}';

    protected $description = 'Lista, liga ou desliga módulos do app para o ambiente inteiro';

    public function handle(): int
    {
        $acao = $this->argument('acao');

        if ($acao === 'listar') {
            $this->table(['Módulo', 'Nome', 'Ligado'], collect(Modules::ALL)->map(
                fn ($nome, $chave) => [$chave, $nome, Modules::enabled($chave) ? 'sim' : 'não']
            )->values());

            return self::SUCCESS;
        }

        $modulo = (string) $this->argument('modulo');
        if (! in_array($acao, ['ligar', 'desligar'], true) || ! Modules::exists($modulo)) {
            $this->error('Uso: bioma:modulos ligar|desligar <modulo>. Módulos: '.implode(', ', array_keys(Modules::ALL)));

            return self::INVALID;
        }

        Modules::set($modulo, $acao === 'ligar', (string) $this->option('por'));
        $this->info(Modules::ALL[$modulo].' '.($acao === 'ligar' ? 'ligado' : 'desligado').'.');

        return self::SUCCESS;
    }
}
