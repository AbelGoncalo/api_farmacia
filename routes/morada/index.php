<?php

use App\Http\Controllers\Api\V1\Morada\MoradaController;
use Illuminate\Support\Facades\Route;

Route::prefix('moradas')->name('moradas.')->middleware('auth:api')->group(function (): void {
    Route::get('/', [MoradaController::class, 'index'])->name('index');
    Route::post('/', [MoradaController::class, 'store'])->name('store');
    Route::get('/{morada}', [MoradaController::class, 'show'])->name('show');
    Route::patch('/{morada}', [MoradaController::class, 'update'])->name('update');
    Route::delete('/{morada}', [MoradaController::class, 'destroy'])->name('destroy');
});
