<?php

namespace Tests\Feature;

use App\Security\KeyVault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_rotacao_da_kek_mantem_os_dados_legiveis(): void
    {
        $this->demo();
        config(['bioma.kek' => config('bioma.kek') + [2 => base64_encode(str_repeat('n', 32))], 'bioma.kek_current' => 2]);
        KeyVault::forget();

        $this->artisan('bioma:chaves-rotacionar')->assertSuccessful();
        $this->assertSame(0, DB::table('patient_keys')->where('kek_version', '!=', 2)->whereNull('destroyed_at')->count());

        KeyVault::forget();
        $this->assertSame('Ana Ribeiro', $this->patientNamed('Ana')->name);
    }

    public function test_chave_destruida_torna_os_dados_ilegiveis(): void
    {
        $this->demo();
        $ana = $this->patientNamed('Ana');
        KeyVault::destroy($ana->id);
        KeyVault::forget();

        $this->expectException(\Throwable::class);
        \App\Models\Patient::find($ana->id)->name;
    }
}
