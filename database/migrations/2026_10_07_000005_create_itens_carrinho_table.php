<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_carrinho', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('carrinho_id')->constrained('carrinhos')->cascadeOnDelete();
            $table->foreignUuid('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->decimal('quantidade', 15, 3);
            $table->decimal('preco_unitario', 15, 2);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->unique(['carrinho_id', 'produto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_carrinho');
    }
};
