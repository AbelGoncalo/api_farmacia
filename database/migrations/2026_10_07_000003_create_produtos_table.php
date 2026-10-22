<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('id_produto_primavera', 100)->nullable()->unique();
            $table->string('codigo', 80)->index();
            $table->string('nome', 200);
            $table->foreignUuid('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->decimal('preco', 15, 2);
            $table->decimal('quantidade_stock', 15, 3)->default(0);
            $table->string('unidade', 30)->nullable();
            $table->decimal('taxa_iva', 5, 2)->nullable();
            $table->text('url_imagem')->nullable();
            $table->boolean('requer_prescricao')->default(false);
            $table->boolean('disponivel_online')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['ativo', 'disponivel_online']);
            $table->index(['categoria_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
