<?php

namespace App\Http\Resources;

use App\Models\Encomenda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Encomenda */
class EncomendaResource extends JsonResource
{
    /**
     * Transforma a encomenda para a resposta da API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Encomenda $encomenda */
        $encomenda = $this->resource;

        return [
            'id' => $encomenda->id,
            'numero_encomenda' => $encomenda->numero_encomenda,
            'estado' => $encomenda->estado,
            'tipo_entrega' => $encomenda->tipo_entrega,
            'morada_id' => $encomenda->morada_id,
            'subtotal' => $encomenda->subtotal,
            'taxa_entrega' => $encomenda->taxa_entrega,
            'total' => $encomenda->total,
            'tipo_documento' => $encomenda->tipo_documento,
            'observacoes' => $encomenda->observacoes,
            'criado_em' => $encomenda->criado_em?->toISOString(),
            'itens' => $this->whenLoaded('itens', ItemEncomendaResource::collection($encomenda->itens)),
            'pagamentos' => $this->whenLoaded('pagamentos', PagamentoResource::collection($encomenda->pagamentos)),
        ];
    }
}
