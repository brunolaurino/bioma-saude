<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Só inserção. Cada linha guarda o hash da anterior, então qualquer alteração quebra a corrente.
        // Em produção o usuário do banco da aplicação não tem UPDATE nem DELETE nesta tabela.
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at', 6);
            $table->string('actor', 120);
            $table->ulid('actor_id')->nullable()->index();
            $table->string('action', 60);
            $table->string('resource', 120);
            $table->ulid('patient_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            // Texto, não JSON: o MySQL reformata colunas JSON e isso mudaria o hash.
            $table->text('details')->nullable();
            $table->char('prev_hash', 64);
            $table->char('hash', 64);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
