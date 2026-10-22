<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encomendas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('numero_encomenda', 40)->unique();
            $table->foreignId('utilizador_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('morada_id')->nullable()->constrained('moradas')->nullOnDelete();
            $table->string('tipo_entrega', 30);
            $table->string('estado', 40)->default('AGUARDANDO_PAGAMENTO');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('taxa_entrega', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->string('tipo_documento', 50)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['utilizador_id', 'criado_em']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encomendas');
    }
};
