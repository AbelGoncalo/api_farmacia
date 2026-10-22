<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_primavera', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('encomenda_id')->unique()->constrained('encomendas')->cascadeOnDelete();
            $table->string('tipo_documento', 50);
            $table->string('id_documento_externo', 150)->nullable()->index();
            $table->string('numero_documento', 100)->nullable();
            $table->string('estado', 30)->default('PENDENTE');
            $table->text('mensagem_erro')->nullable();
            $table->timestamp('ultima_tentativa_em')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_primavera');
    }
};
