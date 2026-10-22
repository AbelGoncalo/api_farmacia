<?php

namespace App\Http\Resources;

use App\Models\ItemEncomenda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ItemEncomenda */
class ItemEncomendaResource extends JsonResource
{
    /**
     * Transforma o item da encomenda para a resposta da API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ItemEncomenda $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'produto_id' => $item->produto_id,
            'codigo' => $item->codigo_produto_historico,
            'nome' => $item->nome_produto_historico,
            'quantidade' => $item->quantidade,
            'preco_unitario' => $item->preco_unitario,
            'subtotal' => $item->subtotal,
        ];
    }
}
