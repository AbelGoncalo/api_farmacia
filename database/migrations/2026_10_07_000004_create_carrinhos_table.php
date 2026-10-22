<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrinhos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('utilizador_id')->constrained('users')->cascadeOnDelete();
            $table->string('estado', 20)->default('ATIVO');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['utilizador_id', 'estado']);

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->unsignedBigInteger('utilizador_carrinho_ativo_id')->nullable()
                    ->storedAs("IF(estado = 'ATIVO', utilizador_id, NULL)");
                $table->unique('utilizador_carrinho_ativo_id', 'carrinhos_um_ativo_por_utilizador');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrinhos');
    }
};
