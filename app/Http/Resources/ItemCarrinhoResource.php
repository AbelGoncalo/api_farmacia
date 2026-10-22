<?php

namespace App\Http\Resources;

use App\Models\ItemCarrinho;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ItemCarrinho */
class ItemCarrinhoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var ItemCarrinho $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'produto' => $item->produto === null ? null : [
                'id' => $item->produto->id,
                'codigo' => $item->produto->codigo,
                'nome' => $item->produto->nome,
                'url_imagem' => $item->produto->url_imagem,
                'requer_prescricao' => $item->produto->requer_prescricao,
            ],
            'quantidade' => (int) $item->quantidade,
            'preco_unitario' => $item->preco_unitario,
            'total_item' => number_format((float) $item->quantidade * (float) $item->preco_unitario, 2, '.', ''),
        ];
    }
}
