<?php

namespace App\Services\Carrinho;

use App\Exceptions\CarrinhoException;
use App\Models\Carrinho;
use App\Models\ItemCarrinho;
use App\Models\Produto;
use App\Models\User;
use App\Repositories\Carrinho\CarrinhoRepository;
use Illuminate\Support\Facades\DB;

class CarrinhoService
{
    public function __construct(
        private readonly CarrinhoRepository $carrinhoRepository,
    ) {}

    public function obterCarrinho(User $utilizador): Carrinho
    {
        return $this->carrinhoRepository->buscarCarrinhoAtivo($utilizador->id)
            ?? $this->carrinhoRepository->criarCarrinho($utilizador->id);
    }

    public function adicionarItem(User $utilizador, string $produtoId, int $quantidade): Carrinho
    {
        return DB::transaction(function () use ($utilizador, $produtoId, $quantidade): Carrinho {
            $carrinho = $this->obterCarrinho($utilizador);
            $produto = $this->garantirProdutoDisponivel($produtoId, comLock: true);

            $item = $this->carrinhoRepository->buscarItemDoProduto($carrinho, $produtoId);
            $quantidadeAtual = $item === null ? 0.0 : (float) $item->quantidade;
            $this->garantirStock($produto, $quantidadeAtual + $quantidade);

            if ($item === null) {
                $this->carrinhoRepository->criarItem($carrinho, $produto, $quantidade);
            } else {
                $this->carrinhoRepository->atualizarQuantidade($item, $quantidadeAtual + $quantidade);
            }

            return $this->obterCarrinho($utilizador);
        });
    }

    public function alterarQuantidade(User $utilizador, string $itemId, int $quantidade): Carrinho
    {
        return DB::transaction(function () use ($utilizador, $itemId, $quantidade): Carrinho {
            $item = $this->garantirItem($utilizador, $itemId);
            $produto = $this->garantirProdutoDisponivel($item->produto_id, comLock: true);

            $this->garantirStock($produto, $quantidade);

            $this->carrinhoRepository->atualizarQuantidade($item, $quantidade);

            return $this->obterCarrinho($utilizador);
        });
    }

    public function removerItem(User $utilizador, string $itemId): Carrinho
    {
        return DB::transaction(function () use ($utilizador, $itemId): Carrinho {
            $item = $this->garantirItem($utilizador, $itemId);

            $this->carrinhoRepository->removerItem($item);

            return $this->obterCarrinho($utilizador);
        });
    }

    public function esvaziar(User $utilizador): Carrinho
    {
        return DB::transaction(function () use ($utilizador): Carrinho {
            $carrinho = $this->obterCarrinho($utilizador);

            $this->carrinhoRepository->esvaziarCarrinho($carrinho);

            return $this->obterCarrinho($utilizador);
        });
    }

    private function garantirProdutoDisponivel(string $produtoId, bool $comLock = false): Produto
    {
        $produto = $this->carrinhoRepository->buscarProdutoDisponivel($produtoId, $comLock);

        if ($produto === null) {
            throw new CarrinhoException(
                'O produto indicado não está disponível.',
                422,
                ['produto_id' => ['O produto indicado não está disponível.']],
            );
        }

        return $produto;
    }

    private function garantirItem(User $utilizador, string $itemId): ItemCarrinho
    {
        $item = $this->carrinhoRepository->buscarItemDoUtilizador($itemId, $utilizador->id);

        if ($item === null) {
            throw new CarrinhoException('O item indicado não foi encontrado no carrinho.', 404);
        }

        return $item;
    }

    private function garantirStock(Produto $produto, float $quantidadeDesejada): void
    {
        if ($quantidadeDesejada > (float) $produto->quantidade_stock) {
            throw new CarrinhoException(
                'Quantidade pedida superior ao stock disponível.',
                409,
                ['quantidade' => ['Quantidade pedida superior ao stock disponível.']],
            );
        }
    }
}
