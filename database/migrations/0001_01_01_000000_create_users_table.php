<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 150);
            $table->string('email', 180)->unique();
            $table->string('telefone', 30)->nullable();
            $table->string('nif', 30)->nullable();
            $table->timestamp('email_verificado_em')->nullable();
            $table->string('palavra_passe');
            $table->string('papel', 30)->default('CLIENTE');
            $table->string('estado', 20)->default('ATIVO');
            $table->rememberToken();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->index(['papel', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
