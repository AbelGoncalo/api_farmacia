<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class RevisorResumoResource extends JsonResource
{
    /**
     * Resumo mínimo do revisor exposto ao próprio revisor.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $utilizador */
        $utilizador = $this->resource;

        return [
            'id' => $utilizador->id,
            'nome' => $utilizador->nome,
        ];
    }
}
