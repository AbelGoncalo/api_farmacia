<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_encomenda_prescricao', function (Blueprint $table): void {
            $table->foreignUuid('item_encomenda_id')->constrained('itens_encomenda')->cascadeOnDelete();
            $table->foreignUuid('item_prescricao_id')->constrained('itens_prescricao')->restrictOnDelete();
            $table->decimal('quantidade', 15, 3)->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->primary(['item_encomenda_id', 'item_prescricao_id'], 'itens_encomenda_prescricao_pk');
            $table->index('item_prescricao_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_encomenda_prescricao');
    }
};
