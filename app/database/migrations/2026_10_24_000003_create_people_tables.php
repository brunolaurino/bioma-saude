<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Colunas marcadas "cifrado" guardam texto cifrado com a chave do paciente (App\Security\Crypto).
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->ulid('user_id')->primary();
            $table->string('name', 160);
            $table->string('role', 20)->default('member'); // master | member
            $table->string('specialty', 60)->nullable(); // Nutrição, Educação física, Psicologia, Medicina…
            $table->string('council', 10)->nullable(); // CRM, CRN, CRP, CREF
            $table->string('council_number', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->nullable()->unique();
            $table->text('name'); // cifrado
            $table->text('birth_date')->nullable(); // cifrado
            $table->text('phone')->nullable(); // cifrado
            $table->text('height')->nullable(); // cifrado
            $table->text('objective')->nullable(); // cifrado
            $table->date('started_on');
            $table->string('status', 30)->default('invited'); // invited | active | closing | closed
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name', 160)->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Quem da equipe vê o quê de cada paciente. O master vê todos os pacientes do ambiente.
        Schema::create('care_team', function (Blueprint $table) {
            $table->ulid('patient_id');
            $table->ulid('professional_id');
            $table->text('modules'); // lista JSON de módulos liberados
            $table->timestamps();
            $table->primary(['patient_id', 'professional_id']);
            $table->index('professional_id');
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('kind', 20)->default('patient'); // patient | professional
            $table->ulid('patient_id')->nullable()->index();
            $table->ulid('user_id')->nullable()->index();
            $table->char('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
        });

        // Chaves dos pacientes (DEK), cifradas pela KEK. Fica numa conexão separada (keyring).
        Schema::connection(config('bioma.keyring_connection'))->create('patient_keys', function (Blueprint $table) {
            $table->string('subject', 40)->primary(); // id do paciente, ou "env" para dados do ambiente
            $table->text('wrapped_dek');
            $table->unsignedSmallInteger('kek_version');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('destroyed_at')->nullable();
        });

        Schema::create('environment_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('setup_tokens', function (Blueprint $table) {
            $table->char('token_hash', 64)->primary();
            $table->string('purpose', 30); // master
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection(config('bioma.keyring_connection'))->dropIfExists('patient_keys');
        foreach (['setup_tokens', 'environment_settings', 'invitations', 'care_team', 'patients', 'professionals'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
