<?php

namespace App\Http\Controllers\Api\V1\Catalogo;

use App\Enums\Catalogo\TipoProduto;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Http\Resources\ProdutoResource;
use App\Services\Catalogo\CatalogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

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

    public function produtos(Request $request): JsonResponse
    {
        $filtros = Validator::make($request->query(), [
            'pesquisa' => ['sometimes', 'string', 'max:100'],
            'categoria_id' => ['sometimes', 'uuid', 'exists:categorias,id'],
            'tipo' => ['sometimes', new Enum(TipoProduto::class)],
            'por_pagina' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ], [
            'categoria_id.uuid' => 'A categoria indicada não é válida.',
            'categoria_id.exists' => 'A categoria indicada não existe.',
            'tipo.enum' => 'O tipo deve ser FARMACO ou COSMETICO.',
            'por_pagina.integer' => 'O número de resultados por página deve ser um número inteiro.',
            'por_pagina.min' => 'O número de resultados por página deve ser pelo menos 1.',
            'por_pagina.max' => 'O número máximo de resultados por página é 50.',
        ])->validate();

        $resultado = $this->catalogoService->listarProdutos(
            array_filter([
                'pesquisa' => isset($filtros['pesquisa']) ? trim($filtros['pesquisa']) : null,
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

    public function produto(string $produto): JsonResponse
    {
        $dados = Validator::make(['id' => $produto], [
            'id' => ['required', 'uuid'],
        ], [
            'id.uuid' => 'O produto indicado não é válido.',
        ])->validate();

        $resultado = $this->catalogoService->buscarProduto($dados['id']);

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
