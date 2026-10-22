<?php

namespace App\Http\Controllers\Api\V1\Carrinho;

use App\Http\Controllers\Controller;
use App\Http\Requests\Carrinho\StoreItemRequest;
use App\Http\Requests\Carrinho\UpdateItemRequest;
use App\Http\Resources\CarrinhoResource;
use App\Models\Carrinho;
use App\Models\User;
use App\Services\Carrinho\CarrinhoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarrinhoController extends Controller
{
    public function __construct(
        private readonly CarrinhoService $carrinhoService,
    ) {}

    public function ver(Request $request): JsonResponse
    {
        $carrinho = $this->carrinhoService->obterCarrinho($this->utilizador($request));

        return $this->resposta('Carrinho carregado com sucesso.', $carrinho);
    }

    public function adicionar(StoreItemRequest $request): JsonResponse
    {
        $carrinho = $this->carrinhoService->adicionarItem(
            $this->utilizador($request),
            (string) $request->validated('produto_id'),
            (int) $request->validated('quantidade'),
        );

        return $this->resposta('Item adicionado ao carrinho.', $carrinho, 201);
    }

    public function alterarQuantidade(UpdateItemRequest $request, string $item): JsonResponse
    {
        $carrinho = $this->carrinhoService->alterarQuantidade(
            $this->utilizador($request),
            $item,
            (int) $request->validated('quantidade'),
        );

        return $this->resposta('Quantidade do item atualizada com sucesso.', $carrinho);
    }

    public function remover(Request $request, string $item): JsonResponse
    {
        $carrinho = $this->carrinhoService->removerItem($this->utilizador($request), $item);

        return $this->resposta('Item removido do carrinho.', $carrinho);
    }

    public function esvaziar(Request $request): JsonResponse
    {
        $carrinho = $this->carrinhoService->esvaziar($this->utilizador($request));

        return $this->resposta('Carrinho esvaziado com sucesso.', $carrinho);
    }

    private function utilizador(Request $request): User
    {
        /** @var User $utilizador */
        $utilizador = $request->user();

        return $utilizador;
    }

    private function resposta(string $message, Carrinho $carrinho, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new CarrinhoResource($carrinho),
        ], $status);
    }
}
