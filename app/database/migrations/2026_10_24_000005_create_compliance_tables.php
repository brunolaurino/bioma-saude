<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Histórico imutável: aceitar e revogar são linhas novas, nunca uma atualização.
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->ulid('patient_id')->index();
            $table->string('purpose', 30); // privacy_notice | photos | journal_share
            $table->string('action', 10); // accept | revoke
            $table->unsignedInteger('notice_version')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at', 6);
        });

        Schema::create('privacy_notices', function (Blueprint $table) {
            $table->unsignedInteger('version')->primary();
            $table->longText('text');
            $table->boolean('relevant_change')->default(true);
            $table->ulid('published_by')->nullable();
            $table->timestamp('published_at');
        });

        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->string('type', 20); // acesso | correcao | portabilidade | eliminacao | oposicao | informacao
            $table->text('details')->nullable(); // cifrado
            $table->string('status', 12)->default('aberto'); // aberto | atendido | negado
            $table->timestamp('due_at');
            $table->text('response')->nullable(); // cifrado
            $table->ulid('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('data_exports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->char('token_hash', 64)->unique();
            $table->string('storage_name', 64);
            $table->timestamp('expires_at');
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('security_incidents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamp('detected_at');
            $table->text('description'); // cifrado com a chave do ambiente
            $table->unsignedInteger('affected_count')->default(0);
            $table->string('risk', 10)->default('avaliar'); // avaliar | baixo | relevante
            $table->timestamp('anpd_notified_at')->nullable();
            $table->timestamp('patients_notified_at')->nullable();
            $table->ulid('recorded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['security_incidents', 'data_exports', 'data_subject_requests', 'privacy_notices', 'consents'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
