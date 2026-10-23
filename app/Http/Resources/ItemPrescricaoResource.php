<?php

namespace App\Http\Resources;

use App\Models\ItemPrescricao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ItemPrescricao */
class ItemPrescricaoResource extends JsonResource
{
    /**
     * Transforma o item da prescrição para a resposta da API.
     *
     * O produto é devolvido apenas com os campos do catálogo carregados
     * (id, código e nome), correspondentes ao eager loading do repositório.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ItemPrescricao $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'texto_original' => $item->texto_original,
            'nome_medicamento' => $item->nome_medicamento,
            'dosagem' => $item->dosagem,
            'apresentacao' => $item->apresentacao,
            'quantidade' => $item->quantidade,
            'produto_id' => $item->produto_id,
            'estado_correspondencia' => $item->estado_correspondencia,
            'metodo_correspondencia' => $item->metodo_correspondencia,
            'confianca_correspondencia' => $item->confianca_correspondencia,
            'produto' => $this->whenLoaded('produto', fn () => $item->produto === null ? null : [
                'id' => $item->produto->id,
                'codigo' => $item->produto->codigo,
                'nome' => $item->produto->nome,
            ]),
        ];
    }
}
