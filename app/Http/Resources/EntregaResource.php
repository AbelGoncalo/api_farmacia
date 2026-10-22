<?php

namespace App\Http\Resources;

use App\Models\Entrega;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Entrega */
class EntregaResource extends JsonResource
{
    /**
     * Transforma a entrega para a resposta da API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Entrega $entrega */
        $entrega = $this->resource;

        return [
            'id' => $entrega->id,
            'encomenda_id' => $entrega->encomenda_id,
            'tipo' => $entrega->tipo,
            'estado' => $entrega->estado,
            'morada_historico' => $entrega->morada_historico,
            'levantamento_confirmado_em' => $entrega->levantamento_confirmado_em?->toISOString(),
            'entregue_em' => $entrega->entregue_em?->toISOString(),
            'criado_em' => $entrega->criado_em?->toISOString(),
        ];
    }
}
