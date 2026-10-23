<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Carrinho\CarrinhoController;
use App\Http\Controllers\Api\V1\Catalogo\CatalogoController;
use App\Http\Controllers\Api\V1\Encomenda\EncomendaController;
use App\Http\Controllers\Api\V1\Encomenda\EntregaController;
use App\Http\Controllers\Api\V1\Encomenda\PagamentoController;
use App\Http\Controllers\Api\V1\Prescricao\PrescricaoController;
use App\Http\Controllers\Api\V1\Prescricao\PrescricaoRevisaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('catalogo')->name('catalogo.')->group(function (): void {
        Route::get('/categorias', [CatalogoController::class, 'categorias'])->name('categorias');
        Route::get('/produtos', [CatalogoController::class, 'produtos'])->name('produtos');
        Route::get('/produtos/{produto}', [CatalogoController::class, 'produto'])->name('produto');
    });

    Route::prefix('carrinho')->name('carrinho.')->middleware('auth:api')->group(function (): void {
        Route::get('/', [CarrinhoController::class, 'ver'])->name('ver');
        Route::delete('/', [CarrinhoController::class, 'esvaziar'])->name('esvaziar');
        Route::post('/itens', [CarrinhoController::class, 'adicionar'])->name('itens.adicionar');
        Route::patch('/itens/{item}', [CarrinhoController::class, 'alterarQuantidade'])->name('itens.alterar');
        Route::delete('/itens/{item}', [CarrinhoController::class, 'remover'])->name('itens.remover');
    });

    Route::prefix('autenticacao')->name('auth.')->group(function (): void {
        Route::post('/registar', [AuthController::class, 'registar'])->name('registar');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
        Route::patch('/atualizar', [AuthController::class, 'atualizar'])->middleware('auth:api')->name('atualizar');
        Route::post('/sair', [AuthController::class, 'sair'])->middleware('auth:api')->name('sair');
    });

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

    Route::prefix('entregas')->name('entregas.')->middleware(['auth:api', 'can:processar-encomendas'])->group(function (): void {
        Route::get('/', [EntregaController::class, 'index'])->name('index');
        Route::patch('/{entrega}/iniciar-transporte', [EntregaController::class, 'iniciarTransporte'])->name('iniciar-transporte');
        Route::patch('/{entrega}/confirmar-levantamento', [EntregaController::class, 'confirmarLevantamento'])->name('confirmar-levantamento');
        Route::patch('/{entrega}/confirmar', [EntregaController::class, 'confirmarEntrega'])->name('confirmar');
    });
});
