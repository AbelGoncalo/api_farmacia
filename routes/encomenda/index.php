<?php

use App\Http\Controllers\Api\V1\Encomenda\EncomendaController;
use App\Http\Controllers\Api\V1\Encomenda\EntregaController;
use App\Http\Controllers\Api\V1\Encomenda\PagamentoController;
use Illuminate\Support\Facades\Route;

Route::prefix('encomendas')->name('encomendas.')->middleware('auth:api')->group(function (): void {
    Route::get('/', [EncomendaController::class, 'index'])->name('index');
    Route::post('/', [EncomendaController::class, 'store'])->name('store');
    Route::get('/{encomenda}', [EncomendaController::class, 'show'])->name('show');
    Route::patch('/{encomenda}/cancelar', [EncomendaController::class, 'cancelar'])->name('cancelar');
    Route::post('/{encomenda}/pagamento', [PagamentoController::class, 'registar'])->name('pagamento.registar');
});

Route::prefix('pagamentos')->name('pagamentos.')->middleware(['auth:api', 'can:processar-encomendas'])->group(function (): void {
    Route::patch('/{pagamento}/confirmar', [PagamentoController::class, 'confirmar'])->name('confirmar');
});

Route::prefix('entregas')->name('entregas.')->middleware(['auth:api', 'can:processar-encomendas'])->group(function (): void {
    Route::get('/', [EntregaController::class, 'index'])->name('index');
    Route::patch('/{entrega}/iniciar-transporte', [EntregaController::class, 'iniciarTransporte'])->name('iniciar-transporte');
    Route::patch('/{entrega}/confirmar-levantamento', [EntregaController::class, 'confirmarLevantamento'])->name('confirmar-levantamento');
    Route::patch('/{entrega}/confirmar', [EntregaController::class, 'confirmarEntrega'])->name('confirmar');
});
