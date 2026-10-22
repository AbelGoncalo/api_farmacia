<?php

namespace App\Http\Controllers\Api\V1\Catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\BuscarProdutoRequest;
use App\Http\Requests\Catalogo\ListarProdutosRequest;
use App\Http\Resources\CategoriaResource;
use App\Http\Resources\ProdutoResource;
use App\Services\Catalogo\CatalogoService;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    public function __construct(
        private readonly CatalogoService $catalogoService,
    ) {}

    public function categorias(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Categorias carregadas com sucesso.',
            'data' => CategoriaResource::collection($this->catalogoService->listarCategorias()),
        ]);
    }

    public function produtos(ListarProdutosRequest $request): JsonResponse
    {
        $filtros = $request->validated();

        $resultado = $this->catalogoService->listarProdutos(
            array_filter([
                'pesquisa' => $filtros['pesquisa'] ?? null,
                'categoria_id' => $filtros['categoria_id'] ?? null,
                'tipo' => $filtros['tipo'] ?? null,
            ], fn (mixed $valor): bool => $valor !== null && $valor !== ''),
            (int) ($filtros['por_pagina'] ?? 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Produtos carregados com sucesso.',
            'data' => ProdutoResource::collection($resultado->getCollection()),
            'meta' => [
                'pagina' => $resultado->currentPage(),
                'por_pagina' => $resultado->perPage(),
                'total' => $resultado->total(),
                'ultima_pagina' => $resultado->lastPage(),
            ],
        ]);
    }

    public function produto(BuscarProdutoRequest $request): JsonResponse
    {
        $resultado = $this->catalogoService->buscarProduto($request->validated('produto'));

        if ($resultado === null) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado.',
                'errors' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produto carregado com sucesso.',
            'data' => new ProdutoResource($resultado),
        ]);
    }
}
