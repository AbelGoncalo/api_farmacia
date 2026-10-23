<?php

namespace App\Http\Resources;

use App\Models\Prescricao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Prescricao */
class PrescricaoResource extends JsonResource
{
    /**
     * Transforma a prescrição para a resposta da API.
     *
     * O caminho interno do ficheiro e o resultado bruto da IA nunca são
     * expostos ao cliente.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Prescricao $prescricao */
        $prescricao = $this->resource;

        return [
            'id' => $prescricao->id,
            'utilizador_id' => $prescricao->utilizador_id,
            'estado' => $prescricao->estado,
            'tipo_ficheiro' => $prescricao->tipo_ficheiro,
            'data_emissao' => $prescricao->data_emissao?->toDateString(),
            'validade_ate' => $prescricao->validade_ate?->toDateString(),
            'criado_em' => $prescricao->criado_em?->toISOString(),
            'itens' => $this->whenLoaded('itens', ItemPrescricaoResource::collection($prescricao->itens)),
        ];
    }
}
