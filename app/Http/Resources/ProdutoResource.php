<?php

namespace App\Http\Resources;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Produto */
class ProdutoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Produto $produto */
        $produto = $this->resource;

        return [
            'id' => $produto->id,
            'codigo' => $produto->codigo,
            'tipo' => $produto->tipo,
            'nome' => $produto->nome,
            'categoria' => $produto->categoria === null ? null : [
                'id' => $produto->categoria->id,
                'nome' => $produto->categoria->nome,
            ],
            'preco' => $produto->preco,
            'quantidade_stock' => $produto->quantidade_stock,
            'unidade' => $produto->unidade,
            'taxa_iva' => $produto->taxa_iva,
            'url_imagem' => $produto->url_imagem,
            'requer_prescricao' => $produto->requer_prescricao,
        ];
    }
}
