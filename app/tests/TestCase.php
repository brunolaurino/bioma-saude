<?php

namespace Tests;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    private ?string $storage = null;

    protected function setUp(): void
    {
        parent::setUp();
        // Arquivos cifrados dos testes vão para uma pasta temporária, nunca para o storage real.
        $this->storage = sys_get_temp_dir().'/bioma-test-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->storage.'/app/private');
        File::ensureDirectoryExists($this->storage.'/framework/views');
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        if ($this->storage) {
            File::deleteDirectory($this->storage);
        }
        parent::tearDown();
    }

    /** Ambiente de demonstração com dados fictícios (Ana, Carlos, Beatriz, Pedro e a equipe). */
    protected function demo(): void
    {
        $this->artisan('bioma:demo')->assertSuccessful();
        \App\Security\Modules::flush();
        \App\Security\Access::flush();
        \App\Support\Settings::flush();
    }

    protected function patientNamed(string $first): Patient
    {
        return Patient::all()->first(fn ($p) => str_starts_with((string) $p->name, $first));
    }

    /** Profissional logado e com a verificação em duas etapas já feita nesta sessão. */
    protected function asPro(string $email): User
    {
        $user = User::findByEmail($email);
        $this->actingAs($user)->withSession(['auth.2fa_passed' => true, 'auth.reauth_at' => time()]);

        return $user;
    }

    protected function asPatient(string $email): User
    {
        $user = User::findByEmail($email);
        $this->actingAs($user)->withSession(['auth.reauth_at' => time()]);

        return $user;
    }
}
