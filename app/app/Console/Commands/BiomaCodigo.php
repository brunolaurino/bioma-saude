<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use PragmaRX\Google2FA\Google2FA;

/** Só para a demonstração: mostra o código de 6 dígitos de uma conta fictícia, sem precisar do celular. */
class BiomaCodigo extends Command
{
    protected $signature = 'bioma:codigo {email=marina@demo.bioma.local : conta fictícia (@demo.bioma.local)}';

    protected $description = 'Mostra o código atual do 2FA de uma conta da demonstração (fora de produção)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        if (app()->environment('production') || ! str_ends_with($email, '@demo.bioma.local')) {
            $this->error('Só funciona fora de produção e para as contas fictícias da demonstração.');

            return self::FAILURE;
        }
        $user = User::findByEmail($email);
        if (! $user || ! $user->two_factor_secret) {
            $this->error('Conta não encontrada ou sem 2FA. Rode php artisan bioma:demo antes.');

            return self::FAILURE;
        }

        $g = new Google2FA;
        $left = 30 - (time() % 30);
        $this->line('Código: '.$g->getCurrentOtp($user->two_factor_secret)." (vale por mais {$left} segundos)");

        return self::SUCCESS;
    }
}
