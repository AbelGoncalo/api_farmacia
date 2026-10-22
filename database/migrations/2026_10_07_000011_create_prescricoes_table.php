<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('utilizador_id')->constrained('users')->cascadeOnDelete();
            $table->text('caminho_ficheiro');
            $table->string('tipo_ficheiro', 30);
            $table->string('estado', 30)->default('PENDENTE');
            $table->json('resultado_ia')->nullable();
            $table->text('notas_profissional')->nullable();
            $table->foreignId('revisto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisto_em')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['utilizador_id', 'estado']);
            $table->index('revisto_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricoes');
    }
};
