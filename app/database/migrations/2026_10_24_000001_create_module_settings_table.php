<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Módulos que o usuário master liga e desliga para o ambiente inteiro.
        Schema::create('module_settings', function (Blueprint $table) {
            $table->string('module', 40)->primary();
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        // Exceção de um módulo para um paciente específico.
        Schema::create('patient_module_overrides', function (Blueprint $table) {
            $table->ulid('patient_id');
            $table->string('module', 40);
            $table->boolean('enabled');
            $table->string('reason', 200)->nullable();
            $table->timestamps();
            $table->primary(['patient_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_module_overrides');
        Schema::dropIfExists('module_settings');
    }
};
