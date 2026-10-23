<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_prescricao', function (Blueprint $table): void {
            // Valor inicial seguro: nunca presume que um produto já foi
            // confirmado. Registos antigos recebem o default automaticamente.
            $table->string('estado_correspondencia', 30)->default('PENDENTE')->after('produto_id');
            $table->string('metodo_correspondencia', 50)->nullable()->after('estado_correspondencia');
        });
    }

    public function down(): void
    {
        Schema::table('itens_prescricao', function (Blueprint $table): void {
            $table->dropColumn(['estado_correspondencia', 'metodo_correspondencia']);
        });
    }
};
