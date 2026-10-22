<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nome', 120);
            $table->text('descricao')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['ativa', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
