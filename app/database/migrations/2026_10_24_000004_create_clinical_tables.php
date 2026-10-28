<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Toda tabela clínica tem patient_id. Colunas marcadas "cifrado" usam a chave do paciente.
    // Exclusão é lógica (deleted_at) enquanto houver guarda legal do prontuário.
    public function up(): void
    {
        Schema::create('patient_goals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->string('type', 20); // weight | water | protein
            $table->text('target'); // cifrado
            $table->ulid('set_by')->nullable();
            $table->timestamps();
            $table->unique(['patient_id', 'type']);
        });

        Schema::create('goal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id');
            $table->date('day');
            $table->string('type', 20); // water | protein | frutas | verduras | legumes | feeling
            $table->text('value'); // cifrado
            $table->timestamps();
            $table->unique(['patient_id', 'day', 'type']);
        });

        Schema::create('symptom_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id');
            $table->date('day');
            $table->text('symptom'); // cifrado
            $table->string('intensity', 10); // Leve | Moderada | Forte
            $table->text('note')->nullable(); // cifrado
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'day']);
        });

        Schema::create('bioimpedance_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id');
            $table->date('measured_on');
            $table->text('values'); // cifrado: peso, gordura, músculo, visceral, TMB
            $table->ulid('file_id')->nullable();
            $table->ulid('replaces_id')->nullable(); // correção guarda a medição anterior
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'measured_on']);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->string('kind', 10); // laudo | foto | exame | plano
            $table->string('angle', 10)->nullable(); // Frente | Lado | Costas
            $table->text('name'); // cifrado
            $table->text('label')->nullable(); // cifrado: tipo de exame
            $table->string('mime', 60);
            $table->unsignedInteger('size');
            $table->char('sha256', 64);
            $table->string('storage_name', 64)->unique();
            $table->date('taken_on');
            $table->boolean('is_current')->default(false); // plano alimentar vigente
            $table->ulid('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workouts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id');
            $table->date('day');
            $table->string('category', 20); // Força | Aeróbico
            $table->string('modality', 40);
            $table->unsignedSmallInteger('minutes');
            $table->string('intensity', 10);
            $table->text('note')->nullable(); // cifrado
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'day']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id');
            $table->date('day');
            $table->text('mood'); // cifrado
            $table->text('tags')->nullable(); // cifrado
            $table->text('text'); // cifrado
            $table->boolean('shared')->default(false);
            $table->timestamp('shared_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'shared']);
        });

        Schema::create('journal_reads', function (Blueprint $table) {
            $table->ulid('journal_id');
            $table->ulid('professional_id');
            $table->timestamp('read_at');
            $table->primary(['journal_id', 'professional_id']);
        });

        Schema::create('therapy_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->dateTime('starts_at');
            $table->string('status', 10)->default('agendada'); // agendada | feita | faltou
            $table->timestamps();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->ulid('professional_id');
            $table->date('day');
            $table->string('type', 40);
            $table->text('note')->nullable(); // cifrado
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->ulid('author_id');
            $table->text('text'); // cifrado
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->ulid('professional_id');
            $table->dateTime('starts_at')->index();
            $table->string('type', 40);
            $table->string('status', 12)->default('agendada'); // agendada | atendida | cancelada
            $table->timestamps();
        });

        Schema::create('alert_rules', function (Blueprint $table) {
            $table->string('key', 20)->primary(); // strong | moderate | repeat | mood | weight | idle
            $table->boolean('enabled')->default(true);
            $table->text('params')->nullable();
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('patient_id')->index();
            $table->string('rule', 20);
            $table->string('severity', 4); // bad | warn
            $table->string('source_type', 20);
            $table->ulid('source_id')->nullable();
            $table->text('summary'); // cifrado
            $table->date('day');
            $table->ulid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['rule', 'source_type', 'source_id']);
        });

        // Texto sempre genérico: nada de dado de saúde em notificação.
        Schema::create('notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->string('icon', 40);
            $table->string('text', 200);
            $table->string('link', 120)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reminder_prefs', function (Blueprint $table) {
            $table->ulid('patient_id');
            $table->string('key', 20);
            $table->boolean('enabled');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            $table->primary(['patient_id', 'key']);
        });
    }

    public function down(): void
    {
        foreach (['reminder_prefs', 'notifications', 'alerts', 'alert_rules', 'appointments', 'clinical_notes', 'consultations',
            'therapy_sessions', 'journal_reads', 'journal_entries', 'workouts', 'files', 'bioimpedance_records', 'symptom_entries',
            'goal_entries', 'patient_goals'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
