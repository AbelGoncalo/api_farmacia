<?php

namespace App\Http\Resources;

use App\Models\Morada;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Morada */
class MoradaResource extends JsonResource
{
    /**
     * Transforma a morada para a resposta da API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Morada $morada */
        $morada = $this->resource;

        return [
            'id' => $morada->id,
            'etiqueta' => $morada->etiqueta,
            'nome_destinatario' => $morada->nome_destinatario,
            'telefone' => $morada->telefone,
            'provincia' => $morada->provincia,
            'municipio' => $morada->municipio,
            'bairro' => $morada->bairro,
            'rua' => $morada->rua,
            'numero_porta' => $morada->numero_porta,
            'referencia' => $morada->referencia,
            'predefinida' => $morada->predefinida,
            'ativa' => $morada->ativa,
            'criado_em' => $morada->criado_em?->toISOString(),
            'atualizado_em' => $morada->atualizado_em?->toISOString(),
        ];
    }
}
