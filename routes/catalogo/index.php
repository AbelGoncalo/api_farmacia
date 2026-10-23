<?php

use App\Http\Controllers\Api\V1\Catalogo\CatalogoController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalogo')->name('catalogo.')->group(function (): void {
    Route::get('/categorias', [CatalogoController::class, 'categorias'])->name('categorias');
    Route::get('/produtos', [CatalogoController::class, 'produtos'])->name('produtos');
    Route::get('/produtos/{produto}', [CatalogoController::class, 'produto'])->name('produto');
});
