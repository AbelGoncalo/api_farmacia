<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execucoes_sincronizacao', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tipo', 40);
            $table->string('origem', 20);
            $table->string('estado', 20)->default('EM_EXECUCAO');
            $table->timestamp('iniciada_em');
            $table->timestamp('terminada_em')->nullable();
            $table->unsignedInteger('registos_processados')->default(0);
            $table->text('mensagem_erro')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['tipo', 'iniciada_em']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execucoes_sincronizacao');
    }
};
