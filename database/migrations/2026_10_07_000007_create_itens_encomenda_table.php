<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_encomenda', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('encomenda_id')->constrained('encomendas')->cascadeOnDelete();
            $table->foreignUuid('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->string('codigo_produto_historico', 80);
            $table->string('nome_produto_historico', 200);
            $table->decimal('quantidade', 15, 3);
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index('encomenda_id');
            $table->index('produto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_encomenda');
    }
};
