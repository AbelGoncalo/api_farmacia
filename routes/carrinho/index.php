<?php

use App\Http\Controllers\Api\V1\Carrinho\CarrinhoController;
use Illuminate\Support\Facades\Route;

Route::prefix('carrinho')->name('carrinho.')->middleware('auth:api')->group(function (): void {
    Route::get('/', [CarrinhoController::class, 'ver'])->name('ver');
    Route::delete('/', [CarrinhoController::class, 'esvaziar'])->name('esvaziar');
    Route::post('/itens', [CarrinhoController::class, 'adicionar'])->name('itens.adicionar');
    Route::patch('/itens/{item}', [CarrinhoController::class, 'alterarQuantidade'])->name('itens.alterar');
    Route::delete('/itens/{item}', [CarrinhoController::class, 'remover'])->name('itens.remover');
});
