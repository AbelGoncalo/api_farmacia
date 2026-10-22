<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('encomenda_id')->constrained('encomendas')->cascadeOnDelete();
            $table->string('metodo', 40);
            $table->string('estado', 30)->default('PENDENTE');
            $table->string('id_transacao', 150)->nullable()->unique();
            $table->string('referencia', 150)->nullable();
            $table->decimal('valor', 15, 2);
            $table->timestamp('pago_em')->nullable();
            $table->json('dados_resposta_operador')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['encomenda_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
