<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('autenticacao')->name('auth.')->group(function (): void {
    Route::post('/registar', [AuthController::class, 'registar'])->name('registar');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
    Route::patch('/atualizar', [AuthController::class, 'atualizar'])->middleware('auth:api')->name('atualizar');
    Route::post('/sair', [AuthController::class, 'sair'])->middleware('auth:api')->name('sair');
});
