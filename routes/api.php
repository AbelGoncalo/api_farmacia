<?php

use Illuminate\Support\Facades\Route;

/**
 * Rotas da API agrupadas por domínio, espelhando a estrutura de
 * Services/Repositories: cada domínio tem o seu ficheiro em
 * routes/{dominio}/index.php e é carregado por aqui.
 */
Route::prefix('v1')->group(function (): void {
    require __DIR__.'/catalogo/index.php';
    require __DIR__.'/carrinho/index.php';
    require __DIR__.'/auth/index.php';
    require __DIR__.'/morada/index.php';
    require __DIR__.'/encomenda/index.php';
    require __DIR__.'/prescricao/index.php';
});
