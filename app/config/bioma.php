<?php

/*
| Configuração própria do Bioma CD. Os segredos (KEK e chave do índice cego) vêm de
| variáveis de ambiente que o entrypoint lê de /run/secrets; nunca ficam no Git.
*/

$keks = [];
foreach (array_filter(explode(',', (string) env('BIOMA_KEK', ''))) as $pair) {
    [$version, $key] = array_pad(explode(':', trim($pair), 2), 2, null);
    if ($version !== null && $key !== null) {
        $keks[(int) $version] = $key;
    }
}

return [

    // Fuso em que datas são mostradas e em que "hoje" é calculado. O banco guarda UTC.
    'timezone' => 'America/Sao_Paulo',

    // Chaves mestras (KEK) por versão, formato "1:base64,2:base64". A última versão cifra; as
    // anteriores só servem para decifrar DEKs ainda não recifradas por bioma:chaves-rotacionar.
    // Em produção a KEK fica no KMS do provedor (AWS KMS); este é o adaptador local.
    'kek' => $keks,
    'kek_current' => (int) env('BIOMA_KEK_VERSION', $keks ? max(array_keys($keks)) : 1),

    // Chave do índice cego (HMAC-SHA256) usado para achar e-mails sem guardá-los em claro.
    'index_key' => env('BIOMA_INDEX_KEY'),

    // Conexão do cofre de chaves dos pacientes (keyring). Em produção é um banco separado.
    'keyring_connection' => env('BIOMA_KEYRING_CONNECTION'),

    'password_min' => 10,

    'session' => [
        'pro_idle_minutes' => 15,
        'pro_absolute_minutes' => 12 * 60,
        'patient_days' => 30,
        'reauth_minutes' => 10,
    ],

    'invite' => ['days' => 7, 'max_attempts' => 5, 'length' => 8],

    'login' => ['per_minute' => 5, 'lock_after' => 10],

    'uploads' => [
        'max_kb' => 15 * 1024,
        'image_max_px' => 2400,
    ],

    // Antivírus para PDFs (clamd). Sem host configurado, o upload registra que não houve varredura.
    'clamav_host' => env('BIOMA_CLAMAV_HOST'),
    'clamav_port' => (int) env('BIOMA_CLAMAV_PORT', 3310),

    'export_hours' => 24,

    // Dias que um pedido do titular tem para ser atendido (LGPD art. 19).
    'dsr_days' => 15,

];
