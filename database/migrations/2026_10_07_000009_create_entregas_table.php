<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('encomenda_id')->unique()->constrained('encomendas')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->string('estado', 30)->default('PENDENTE');
            $table->json('morada_historico')->nullable();
            $table->timestamp('levantamento_confirmado_em')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};
