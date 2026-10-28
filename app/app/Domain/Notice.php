<?php

namespace App\Domain;

use App\Security\Audit;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/** Aviso de privacidade versionado. Cada publicação é uma versão nova; a anterior fica no histórico. */
class Notice
{
    public static function draft(): string
    {
        $text = (string) file_get_contents(resource_path('legal/aviso-privacidade.txt'));
        $map = [
            '{clinica}' => Settings::get('clinic_name'),
            '{controlador}' => Settings::get('controller_name') ?: Settings::get('clinic_name'),
            '{documento_controlador}' => Settings::get('controller_document') ?: 'CPF/CNPJ a preencher',
            '{encarregado}' => Settings::get('dpo_name') ?: 'a preencher',
            '{contato_encarregado}' => Settings::get('dpo_contact') ?: 'a preencher',
            '{operador}' => Settings::get('operator_name'),
        ];

        return strtr($text, $map);
    }

    public static function current(): ?object
    {
        return DB::table('privacy_notices')->orderByDesc('version')->first();
    }

    public static function publish(string $text, bool $relevant, ?string $by): int
    {
        $version = (int) DB::table('privacy_notices')->max('version') + 1;
        DB::table('privacy_notices')->insert([
            'version' => $version,
            'text' => $text,
            'relevant_change' => $relevant,
            'published_by' => $by,
            'published_at' => now(),
        ]);
        Audit::log('notice.publish', 'notice:'.$version, null, ['relevant' => $relevant]);

        return $version;
    }
}
