<?php

namespace App\Http\Resources;

use App\Models\Carrinho;
use App\Models\ItemCarrinho;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Carrinho */
class CarrinhoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Carrinho $carrinho */
        $carrinho = $this->resource;

        return [
            'id' => $carrinho->id,
            'estado' => $carrinho->estado,
            'total_itens' => (int) $carrinho->itens->sum('quantidade'),
            'subtotal' => number_format(
                (float) $carrinho->itens->sum(
                    fn (ItemCarrinho $item): float => (float) $item->quantidade * (float) $item->preco_unitario
                ),
                2,
                '.',
                '',
            ),
            'itens' => ItemCarrinhoResource::collection($carrinho->itens),
        ];
    }
}
