<?php

use App\Http\Controllers\Api\V1\Prescricao\PrescricaoController;
use App\Http\Controllers\Api\V1\Prescricao\PrescricaoRevisaoController;
use Illuminate\Support\Facades\Route;

// A fila de revisão fica registada antes das rotas {prescricao} do
// cliente, para que /prescricoes/revisao não caia no parâmetro {prescricao}.
Route::prefix('prescricoes/revisao')->name('prescricoes.revisao.')
    ->middleware(['auth:api', 'can:rever-prescricoes'])->group(function (): void {
        Route::get('/', [PrescricaoRevisaoController::class, 'index'])->name('index');
        Route::get('/{prescricao}', [PrescricaoRevisaoController::class, 'show'])->name('show');
        Route::get('/{prescricao}/documento', [PrescricaoRevisaoController::class, 'documento'])->name('documento');
        Route::patch('/{prescricao}/aprovar', [PrescricaoRevisaoController::class, 'aprovar'])->name('aprovar');
        Route::patch('/{prescricao}/rejeitar', [PrescricaoRevisaoController::class, 'rejeitar'])->name('rejeitar');
    });

Route::prefix('prescricoes')->name('prescricoes.')->middleware('auth:api')->group(function (): void {
    Route::get('/', [PrescricaoController::class, 'index'])->name('index');
    Route::post('/', [PrescricaoController::class, 'store'])->name('store');
    Route::get('/{prescricao}', [PrescricaoController::class, 'show'])->name('show');
    Route::get('/{prescricao}/documento', [PrescricaoController::class, 'documento'])->name('documento');
});
