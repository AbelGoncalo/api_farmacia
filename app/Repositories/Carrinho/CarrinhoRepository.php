<?php

namespace App\Repositories\Carrinho;

use App\Models\Carrinho;
use App\Models\ItemCarrinho;
use App\Models\Produto;
use Illuminate\Support\Str;

class CarrinhoRepository
{
    public function buscarCarrinhoAtivo(int $utilizadorId): ?Carrinho
    {
        return Carrinho::query()
            ->with([
                'itens:id,carrinho_id,produto_id,quantidade,preco_unitario',
                'itens.produto:id,codigo,nome,url_imagem,requer_prescricao',
            ])
            ->where('utilizador_id', $utilizadorId)
            ->where('estado', Carrinho::ESTADO_ATIVO)
            ->first();
    }

    public function criarCarrinho(int $utilizadorId): Carrinho
    {
        $carrinho = new Carrinho;
        $carrinho->id = (string) Str::uuid();
        $carrinho->utilizador_id = $utilizadorId;
        $carrinho->estado = Carrinho::ESTADO_ATIVO;
        $carrinho->save();

        return $carrinho;
    }

    public function buscarProdutoDisponivel(string $produtoId): ?Produto
    {
        return Produto::query()
            ->where('id', $produtoId)
            ->where('ativo', true)
            ->where('disponivel_online', true)
            ->first();
    }

    public function buscarItemDoUtilizador(string $itemId, int $utilizadorId): ?ItemCarrinho
    {
        return ItemCarrinho::query()
            ->where('id', $itemId)
            ->whereHas('carrinho', fn ($query) => $query
                ->where('utilizador_id', $utilizadorId)
                ->where('estado', Carrinho::ESTADO_ATIVO))
            ->first();
    }

    public function quantidadeDoProdutoNoCarrinho(Carrinho $carrinho, string $produtoId): float
    {
        return (float) $carrinho->itens()
            ->where('produto_id', $produtoId)
            ->sum('quantidade');
    }

    public function adicionarItem(Carrinho $carrinho, Produto $produto, int $quantidade): ItemCarrinho
    {
        $item = $carrinho->itens()->where('produto_id', $produto->id)->first();

        if ($item === null) {
            $item = new ItemCarrinho;
            $item->id = (string) Str::uuid();
            $item->carrinho_id = $carrinho->id;
            $item->produto_id = $produto->id;
            $item->quantidade = $quantidade;
            $item->preco_unitario = $produto->preco;
            $item->save();

            return $item;
        }

        $item->quantidade = (float) $item->quantidade + $quantidade;
        $item->save();

        return $item;
    }

    public function atualizarQuantidade(ItemCarrinho $item, int $quantidade): ItemCarrinho
    {
        $item->quantidade = $quantidade;
        $item->save();

        return $item;
    }

    public function removerItem(ItemCarrinho $item): void
    {
        $item->delete();
    }

    public function esvaziarCarrinho(Carrinho $carrinho): void
    {
        $carrinho->itens()->delete();
    }
}
