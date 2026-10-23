<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricoes', function (Blueprint $table): void {
            // Mensagem técnica de uma falha de análise. Nunca deve conter
            // chaves, tokens, credenciais ou dados sensíveis.
            $table->text('mensagem_erro')->nullable()->after('resultado_ia');
            $table->date('data_emissao')->nullable()->after('mensagem_erro');
            $table->date('validade_ate')->nullable()->after('data_emissao');
        });
    }

    public function down(): void
    {
        Schema::table('prescricoes', function (Blueprint $table): void {
            $table->dropColumn(['mensagem_erro', 'data_emissao', 'validade_ate']);
        });
    }
};
