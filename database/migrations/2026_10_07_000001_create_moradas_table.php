<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moradas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('utilizador_id')->constrained('users')->cascadeOnDelete();
            $table->string('etiqueta', 80)->nullable();
            $table->string('nome_destinatario', 150);
            $table->string('telefone', 30);
            $table->string('provincia', 100);
            $table->string('municipio', 100);
            $table->string('bairro', 150);
            $table->string('rua', 150)->nullable();
            $table->string('numero_porta', 30)->nullable();
            $table->string('referencia', 255)->nullable();
            $table->boolean('predefinida')->default(false);
            $table->boolean('ativa')->default(true);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['utilizador_id', 'ativa']);

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->unsignedBigInteger('utilizador_morada_predefinida_id')->nullable()
                    ->storedAs('IF(predefinida = 1 AND ativa = 1, utilizador_id, NULL)');
                $table->unique('utilizador_morada_predefinida_id', 'moradas_predefinida_por_utilizador_unica');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moradas');
    }
};
