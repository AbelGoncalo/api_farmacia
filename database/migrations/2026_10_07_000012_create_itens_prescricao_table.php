<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_prescricao', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('prescricao_id')->constrained('prescricoes')->cascadeOnDelete();
            $table->string('texto_original', 255);
            $table->string('nome_medicamento', 200);
            $table->string('dosagem', 100)->nullable();
            $table->string('apresentacao', 100)->nullable();
            $table->decimal('quantidade', 15, 3)->nullable();
            $table->foreignUuid('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->decimal('confianca_correspondencia', 5, 2)->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index('prescricao_id');
            $table->index('produto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_prescricao');
    }
};
