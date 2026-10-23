<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_prescricao', function (Blueprint $table): void {
            // Quantidade total deste item que já foi consumida por encomendas.
            // Mantém-se um contador derivado para calcular o que resta sem
            // agregar o pivot; a fonte de verdade continua a ser o pivot
            // `itens_encomenda_prescricao` (registos de utilização).
            $table->decimal('quantidade_consumida', 15, 3)->default(0)->after('quantidade');
        });
    }

    public function down(): void
    {
        Schema::table('itens_prescricao', function (Blueprint $table): void {
            $table->dropColumn('quantidade_consumida');
        });
    }
};
