<?php

namespace App\Http\Resources;

use App\Models\Pagamento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pagamento */
class PagamentoResource extends JsonResource
{
    /**
     * Transforma o pagamento para a resposta da API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pagamento $pagamento */
        $pagamento = $this->resource;

        return [
            'id' => $pagamento->id,
            'encomenda_id' => $pagamento->encomenda_id,
            'metodo' => $pagamento->metodo,
            'estado' => $pagamento->estado,
            'id_transacao' => $pagamento->id_transacao,
            'referencia' => $pagamento->referencia,
            'valor' => $pagamento->valor,
            'pago_em' => $pagamento->pago_em?->toISOString(),
            'criado_em' => $pagamento->criado_em?->toISOString(),
        ];
    }
}
