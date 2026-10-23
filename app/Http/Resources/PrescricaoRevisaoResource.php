<?php

namespace App\Http\Resources;

use App\Models\Prescricao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Prescricao */
class PrescricaoRevisaoResource extends JsonResource
{
    /**
     * Transforma a prescrição para a vista de revisão de um profissional.
     *
     * Ao contrário da vista do cliente, inclui o resultado bruto da IA e as
     * notas de revisão, para permitir uma decisão humana informada. O caminho
     * interno do ficheiro nunca é exposto (descarrega-se via endpoint próprio).
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
            'resultado_ia' => $prescricao->resultado_ia,
            'mensagem_erro' => $prescricao->mensagem_erro,
            'data_emissao' => $prescricao->data_emissao?->toDateString(),
            'validade_ate' => $prescricao->validade_ate?->toDateString(),
            'notas_profissional' => $prescricao->notas_profissional,
            'revisto_por' => $prescricao->revisto_por,
            'revisto_em' => $prescricao->revisto_em?->toISOString(),
            'criado_em' => $prescricao->criado_em?->toISOString(),
            'itens' => ItemPrescricaoResource::collection($this->whenLoaded('itens')),
            'revisor' => new RevisorResumoResource($this->whenLoaded('revisor')),
        ];
    }
}
